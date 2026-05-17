<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activo;
use App\Repositories\SigmuRepository;
use RuntimeException;
use Throwable;

final class AssetImportService
{
    private readonly SigmuRepository $repository;
    private readonly SigmuService $sigmuService;

    // Número serial de Excel para el 1 de enero de 1900
    private const EXCEL_EPOCH = 25569; // días desde 1900-01-01 hasta 1970-01-01

    public function __construct(?SigmuRepository $repository = null, ?SigmuService $sigmuService = null)
    {
        $this->repository = $repository ?? new SigmuRepository();
        $this->sigmuService = $sigmuService ?? new SigmuService($this->repository);
    }

    // -------------------------------------------------------------------------
    // PUNTO DE ENTRADA
    // -------------------------------------------------------------------------

    public function importFromFile(string $filePath, string $originalName, int $salaId): array
    {
        set_time_limit(300);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $tmpDir    = sys_get_temp_dir() . '/sigmu_import_' . uniqid();

        try {
            return match ($extension) {
                'xlsx'  => $this->importFromXlsx($filePath, $tmpDir, $salaId),
                'xls'   => $this->importFromExcelXml($filePath, $salaId),
                'csv'   => $this->importFromCsv($filePath, $salaId),
                default => throw new RuntimeException("Formato no soportado: .$extension. Use .xlsx, .xls o .csv"),
            };
        } catch (Throwable $e) {
            return ['success' => 0, 'errors' => ["Error crítico: " . $e->getMessage()], 'total' => 0];
        } finally {
            $this->recursiveRemove($tmpDir);
        }
    }

    // -------------------------------------------------------------------------
    // EXCEL 2003 XML (XLS)
    // -------------------------------------------------------------------------

    private function importFromExcelXml(string $filePath, int $salaId): array
    {
        $xml = simplexml_load_file($filePath);
        if (!$xml) {
            throw new RuntimeException("No se pudo parsear el archivo XML.");
        }

        // Registrar namespaces para el XML de Excel
        $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
        
        $rows = $xml->xpath('//ss:Row');
        $matrix = [];

        foreach ($rows as $row) {
            $rowData = [];
            foreach ($row->Cell as $cell) {
                $rowData[] = (string) $cell->Data;
            }
            if (!empty(array_filter($rowData, static fn($v) => trim($v) !== ''))) {
                $matrix[] = $rowData;
            }
        }

        if (empty($matrix)) {
            throw new RuntimeException("No se encontraron datos en el archivo XML.");
        }

        return $this->processMatrix($matrix, $salaId);
    }

    // -------------------------------------------------------------------------
    // XLSX
    // -------------------------------------------------------------------------

    private function importFromXlsx(string $filePath, string $tmpDir, int $salaId): array
    {
        $this->extractXlsx($filePath, $tmpDir);

        $sharedStrings = $this->parseSharedStrings($tmpDir . '/xl/sharedStrings.xml');
        $numberFormats = $this->parseNumberFormats($tmpDir . '/xl/styles.xml');
        $matrix        = $this->findFirstDataSheet($tmpDir, $sharedStrings, $numberFormats);

        if (empty($matrix)) {
            throw new RuntimeException("No se encontraron datos legibles en el Excel.");
        }

        return $this->processMatrix($matrix, $salaId);
    }

    /**
     * Extrae el ZIP del .xlsx. Prefiere ZipArchive (nativo PHP),
     * con fallback a tar (comando de sistema).
     */
    private function extractXlsx(string $filePath, string $tmpDir): void
    {
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        if (extension_loaded('zip')) {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                throw new RuntimeException("No se pudo abrir el archivo .xlsx.");
            }
            $zip->extractTo($tmpDir);
            $zip->close();
            return;
        }

        // Fallback: tar (funciona en Linux y Windows 10+)
        $cmd    = 'tar -xf ' . escapeshellarg($filePath) . ' -C ' . escapeshellarg($tmpDir) . ' 2>&1';
        $output = shell_exec($cmd);
        if (!file_exists($tmpDir . '/xl/workbook.xml')) {
            throw new RuntimeException("No se pudo descomprimir el archivo. Salida: " . $output);
        }
    }

    /**
     * CORRECCIÓN PRINCIPAL: parsea correctamente sharedStrings.xml
     * contemplando celdas con múltiples <r> (rich text runs).
     */
    private function parseSharedStrings(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $xml     = simplexml_load_file($path);
        $strings = [];

        foreach ($xml->si as $si) {
            // Texto plano: <si><t>...</t></si>
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
                continue;
            }
            // Rich text: <si><r><t>...</t></r><r><t>...</t></r></si>
            // Concatenamos todos los runs para no perder texto parcial
            $parts = [];
            foreach ($si->r as $r) {
                $parts[] = (string) $r->t;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    /**
     * Lee styles.xml para saber qué celdas son fechas (formatId 14-22 son
     * formatos de fecha estándar de Excel).
     * Retorna un array [xfIndex => bool] indicando si ese estilo es fecha.
     */
    private function parseNumberFormats(string $path): array
    {
        $dateFormats = [];
        if (!file_exists($path)) {
            return $dateFormats;
        }

        $ns  = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xml = simplexml_load_file($path);

        // IDs de formato built-in de Excel que corresponden a fechas
        $builtinDateFmtIds = range(14, 22);

        // Leer formatos personalizados que parezcan fechas
        $customDateFmtIds = [];
        $numFmts = $xml->children($ns)->numFmts;
        foreach ($numFmts->children($ns) as $fmt) {
            $id   = (int)    $fmt['numFmtId'];
            $code = (string) $fmt['formatCode'];
            if (preg_match('/[dmyh]/i', $code)
                && !preg_match('/^[#0%,.\s]+$/', $code)
                && !preg_match('/[\$€£¥]|#,##0/', $code)) {
                $customDateFmtIds[] = $id;
            }
        }

        $allDateIds = array_merge($builtinDateFmtIds, $customDateFmtIds);

        // Mapear cada xf con namespace explícito
        $cellXfs = $xml->children($ns)->cellXfs;
        foreach ($cellXfs->children($ns) as $idx => $xf) {
            $fmtId             = (int) $xf['numFmtId'];
            $dateFormats[$idx] = in_array($fmtId, $allDateIds, true);
        }

        return $dateFormats;
    }

    /**
     * Recorre sheet1, sheet2, sheet3 y retorna la primera que tenga datos reales.
     */
    private function findFirstDataSheet(string $tmpDir, array $sharedStrings, array $numberFormats): array
    {
        for ($i = 1; $i <= 3; $i++) {
            $sheetFile = "{$tmpDir}/xl/worksheets/sheet{$i}.xml";
            if (!file_exists($sheetFile)) {
                continue;
            }

            $matrix = $this->parseSheet($sheetFile, $sharedStrings, $numberFormats);

            // Una hoja con cabecera + al menos 1 fila de datos
            if (count($matrix) > 1) {
                return $matrix;
            }
        }

        return [];
    }

    /**
     * Convierte el XML de una hoja en una matriz bidimensional de strings.
     */
    private function parseSheet(string $sheetFile, array $sharedStrings, array $numberFormats): array
    {
        $xml    = simplexml_load_file($sheetFile);
        $matrix = [];

        foreach ($xml->sheetData->row as $row) {
            $rowData = [];

            foreach ($row->c as $c) {
                $cellRef = (string) $c['r'];                         // ej: "B3"
                $colIdx  = $this->columnLetterToIndex(
                    preg_replace('/[0-9]/', '', $cellRef)
                );
                $type    = (string) $c['t'];                         // 's' = shared string, etc.
                $styleId = isset($c['s']) ? (int) $c['s'] : -1;
                $raw     = (string) $c->v;

                if ($type === 's') {
                    // Shared string
                    $value = $sharedStrings[(int) $raw] ?? '';
                } elseif ($type === 'b') {
                    // Booleano
                    $value = $raw === '1' ? 'Sí' : 'No';
                } elseif ($type === 'str' || $type === 'inlineStr') {
                    // Fórmula que devuelve string
                    $value = $raw !== '' ? $raw : (string) ($c->is->t ?? '');
                } elseif ($raw !== '' && $styleId >= 0 && ($numberFormats[$styleId] ?? false)) {
                    // CORRECCIÓN: celda numérica con formato de fecha → convertir serial
                    $value = $this->excelSerialToDate((float) $raw);
                } else {
                    $value = $raw;
                }

                $rowData[$colIdx] = $value;
            }

            if (empty(array_filter($rowData, static fn($v) => trim($v) !== ''))) {
                continue; // ignorar filas completamente vacías
            }

            // Rellenar huecos para que los índices sean contiguos
            if (!empty($rowData)) {
                $max = max(array_keys($rowData));
                for ($j = 0; $j <= $max; $j++) {
                    if (!isset($rowData[$j])) {
                        $rowData[$j] = '';
                    }
                }
                ksort($rowData);
            }

            $matrix[] = $rowData;
        }

        return $matrix;
    }

    /**
     * Convierte el número serial de fecha de Excel a string legible.
     * Excel tiene un bug histórico (año 1900 bisiesto falso).
     */
    private function excelSerialToDate(float $serial): string
    {
        if ($serial <= 0) {
            return '';
        }

        // Excel considera erróneamente 1900 como bisiesto. 
        // Para fechas posteriores al 28/02/1900 (serial 59), restamos 1.
        $offset = ($serial > 60) ? $serial - 1 : $serial;
        
        // Base de Excel: 1899-12-30
        $timestamp = ($offset - self::EXCEL_EPOCH) * 86400;

        // Descartamos valores absurdos (antes de 1900 o después de 2100)
        // 1900-01-01 es aprox -2208988800
        if ($timestamp < -2208988800 || $timestamp > 4102444800) {
            return (string) $serial;
        }

        return date('Y-m-d H:i:s', (int) $timestamp);
    }

    // -------------------------------------------------------------------------
    // PROCESAMIENTO DE LA MATRIZ (COMPARTIDO CSV / XLSX)
    // -------------------------------------------------------------------------

    private function processMatrix(array $matrix, int $salaId): array
    {
        $mapping      = ['codigo' => -1, 'nombre' => -1, 'tipo' => -1, 'descripcion' => -1, 'estado' => -1, 'fecha' => -1, 'valor_adquisicion' => -1];
        $headerRowIdx = -1;
        $bestScore    = 0;

        $candidates = $this->buildHeaderCandidates($matrix);

        foreach ($candidates as ['row' => $row, 'sourceIdx' => $sourceIdx]) {
            $currentMapping = $this->guessMapping($row);
            $score          = count(array_filter($currentMapping, static fn($v) => $v !== -1));

            // Relajar requisitos: permitir nombre O descripción, pero SIEMPRE código
            $hasMinimum = ($currentMapping['nombre'] !== -1 || $currentMapping['descripcion'] !== -1)
                && ($currentMapping['codigo'] !== -1);

            if ($hasMinimum && $score > $bestScore) {
                $bestScore    = $score;
                $mapping      = $currentMapping;
                $headerRowIdx = $sourceIdx;
            }
        }

        if ($headerRowIdx === -1) {
            throw new RuntimeException(
                "No se pudieron identificar las columnas necesarias. " .
                "Asegúrate de tener encabezados como: Código, Nombre (o Descripción), Fecha de Adquisición."
            );
        }

        $results = ['success' => 0, 'errors' => [], 'total' => 0];

        for ($i = $headerRowIdx + 1; $i < count($matrix); $i++) {
            $row = $matrix[$i];

            if (empty(array_filter($row, static fn($v) => trim((string) $v) !== ''))) {
                continue;
            }

            if ($this->isSubtotalRow($row)) {
                continue;
            }

            $results['total']++;
            $data = $this->extractDataFromRow($row, $mapping);

            $nombre = $this->cleanString($data['nombre'] ?? '');
            $desc   = $this->cleanString($data['descripcion'] ?? '');

            if ($nombre === '' && $desc !== '') {
                $nombre = mb_strimwidth($desc, 0, 80, '…');
            }

            if ($nombre === '') {
                $results['errors'][] = "Fila " . ($i + 1) . ": omitida (sin nombre ni descripción).";
                continue;
            }

            $codigo = $this->cleanString($data['codigo'] ?? '');
            if ($codigo === '') {
                $codigo = $this->sigmuService->generarCodigoActivo($nombre);
            }

            // Normalización robusta de fecha
            $fechaDb = $this->normalizeFechaParaDb($data['fecha'] ?? '');
            
            $valorRaw = $data['valor_adquisicion'] ?? null;
            $valorAdquisicion = null;
            if ($valorRaw !== null && $valorRaw !== '') {
                $valorAdquisicion = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $valorRaw));
            }

            $res = $this->sigmuService->registrarActivo(
                $codigo,
                mb_strimwidth($nombre, 0, 98, '…'),
                $this->resolveTipoActivo($data['tipo'] ?? '', $nombre),
                $desc,
                $valorAdquisicion,
                $this->normalizeEstado($data['estado'] ?? ''),
                $salaId,
                [],
                $fechaDb
            );

            if ($res['success']) {
                $results['success']++;
            } else {
                $results['errors'][] = "Fila " . ($i + 1) . ": " . $res['message'];
            }
        }

        return $results;
    }

    /**
     * Detecta filas de subtotal/total que no son activos reales.
     * Ejemplos: "TOTAL ACUMULADO AÑO 2005", "SUBTOTAL", "TOTAL GENERAL", etc.
     */
    private function isSubtotalRow(array $row): bool
    {
        foreach ($row as $val) {
            $s = $this->simplifyString((string) $val);
            if ($s === '') {
                continue;
            }
            // Coincide con patrones de fila de total
            if (preg_match('/^(total|subtotal|acumulado|grandtotal|totalgeneral|totalaño|totalacumulado)/', $s)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Genera candidatos a fila de encabezado evaluando:
     * - Cada fila individual
     * - Cada par de filas consecutivas fusionadas celda a celda (para encabezados partidos en 2 filas)
     *
     * Retorna array de ['row' => array, 'sourceIdx' => int]
     * donde sourceIdx es el índice de la última fila original que forma el candidato.
     */
    private function buildHeaderCandidates(array $matrix): array
    {
        $candidates = [];

        for ($i = 0; $i < count($matrix); $i++) {
            // Candidato individual
            $candidates[] = [
                'row'       => array_values($matrix[$i]),
                'sourceIdx' => $i,
            ];

            // Candidato fusionado con la siguiente fila
            if (isset($matrix[$i + 1])) {
                $candidates[] = [
                    'row'       => $this->mergeTwoRows($matrix[$i], $matrix[$i + 1]),
                    'sourceIdx' => $i + 1, // los datos empiezan después de la segunda fila
                ];
            }
        }

        return $candidates;
    }

    /**
     * Fusiona filas contiguas que parecen ser encabezados partidos en varias líneas.
     * Estrategia: si una fila tiene al menos 1 celda no vacía pero NO tiene
     * suficientes columnas llenas como para ser datos, se intenta concatenar
     * con la siguiente fila celda a celda.
     *
     * Ejemplo:
     *   Fila 4: [null,       null,          "FECHA DE",      "VALOR DE"     ]
     *   Fila 5: ["CODIGO",   "DESCRIPCION", "ADQUISICION $", "ADQUISICION $"]
     *   → merged: ["CODIGO", "DESCRIPCION", "FECHA DE ADQUISICION $", "VALOR DE ADQUISICION $"]
     */
    /**
     * Fusiona dos filas celda a celda:
     * - Si ambas tienen valor: concatena con espacio → "FECHA DE" + "ADQUISICION $" = "FECHA DE ADQUISICION $"
     * - Si solo una tiene valor: toma esa
     */
    private function mergeTwoRows(array $rowA, array $rowB): array
    {
        $maxLen = max(count($rowA), count($rowB));
        $merged = [];

        for ($col = 0; $col < $maxLen; $col++) {
            $top    = trim((string) ($rowA[$col] ?? ''));
            $bottom = trim((string) ($rowB[$col] ?? ''));

            if ($top !== '' && $bottom !== '') {
                $merged[$col] = $top . ' ' . $bottom;
            } elseif ($top !== '') {
                $merged[$col] = $top;
            } else {
                $merged[$col] = $bottom;
            }
        }

        return $merged;
    }

    // -------------------------------------------------------------------------
    // MAPEO DE COLUMNAS
    // -------------------------------------------------------------------------

    /**
     * MEJORA: diccionario ordenado por especificidad (más específico primero)
     * para evitar que "estado" matchee "ubicacion" antes que "estado".
     */
    private function guessMapping(array $row): array
    {
        $m = ['codigo' => -1, 'nombre' => -1, 'tipo' => -1, 'descripcion' => -1, 'estado' => -1, 'fecha' => -1, 'valor_adquisicion' => -1];

        $ignoreList = '/^(responsable|encargado|departamento|piso|marca|modelo|color|material|'
                    . 'ubicacion|ubicacionfisica|edificio|aula|sala|proveedor|factura|garantia|'
                    . 'fechamantenimiento|fechaultimo|fechaproximo|fechaultimomantenimiento|'
                    . 'fechaproximomantenimiento|vidautil|valoractual|'
                    . 'codigobarra|barcode|numero|correlativo)$/i';

        $dict = [
            'codigo' => '/(codigoactivo|codactivo|codigobien|nroinventario|numeroinventario|'
                      . 'placa|sku|codigopat|codigopatrimonial|codigo|codigointerno|folio|idbien|idactivo|cod|inv|ref|nro|tag|bn)/',

            'nombre' => '/(descripciondelbien|descripciondelactivo|descripciondelelemento|'
                      . 'descripciondelequipo|descripcionbien|nombreactivo|nomactivo|nombrearticulo|'
                      . 'nombreelemento|nombreequipo|mobiliario|articulo|elemento|'
                      . 'bien|activo|objeto|item|nombre|nom)/',

            'tipo'   => '/(tipodeactivo|tipoactivo|tipobien|categoriaactivo|clasificaciondeactivo|'
                      . 'clasificacion|subcategoria|categoria|tipo|cat|cla|fam|gen)/',

            'valor_adquisicion' => '/(valoradquisicion|valordeadquisicion|valordecompra|valorcompra|'
                         . 'costoadquisicion|costo_adquisicion|precioneto|precio_neto|costo|precio|valor)/',

            'estado' => '/(estadoactual|estadoactivo|estadofisico|condicionactual|condicion|estado|cond|stat)/',

            // Fecha de adquisición: muy flexible (sin anclas ^ $)
            'fecha'  => '/(fechaingreso|fechaadquisicion|fechadeadquisicion|fechadquisicion|fecharegistro|fechacompra|'
                      . 'fechaalta|fechaincorporacion|ingreso|adquisicion|adquisi|registro|incorporacion|compra|date|fecha)/',

            'descripcion' => '/(descripcion|observacion|observaciones|observ|notas|detalles|'
                           . 'especificacion|comentario|caracteristica|carac|detalle)/',
        ];

        foreach ($row as $idx => $val) {
            $simplified = $this->simplifyString((string) $val);
            if ($simplified === '') {
                continue;
            }

            if (preg_match($ignoreList, $simplified)) {
                continue;
            }

            foreach ($dict as $field => $regex) {
                if ($m[$field] === -1 && preg_match($regex, $simplified)) {
                    $m[$field] = $idx;
                    break;
                }
            }
        }

        return $m;
    }

    // -------------------------------------------------------------------------
    // NORMALIZACIÓN
    // -------------------------------------------------------------------------

    private function normalizeEstado(string $raw): string
    {
        $s = $this->simplifyString($raw);

        if ($s === '') {
            return Activo::ESTADO_DISPONIBLE;
        }

        $map = [
            Activo::ESTADO_DISPONIBLE  => '/(disp|buen|nuev|stock|ok|func|excel|activo)/i',
            Activo::ESTADO_EN_USE      => '/(uso|asig|ocup|enuso)/i',
            Activo::ESTADO_REPARACION  => '/(rep|mant|dan|mal|fall|queb|averi)/i',
            Activo::ESTADO_DESCARTADO  => '/(desc|baj|viej|obso|retir|dado)/i',
        ];

        foreach ($map as $estado => $regex) {
            if (preg_match($regex, $s)) {
                return $estado;
            }
        }

        return Activo::ESTADO_DISPONIBLE;
    }

    private function resolveTipoActivo(string $raw, string $nombreActivo = ''): int
    {
        $tipos = $this->repository->typesActive();
        if (empty($tipos)) {
            return 1;
        }

        $sRaw    = $this->simplifyString($raw);
        $sNombre = $this->simplifyString($nombreActivo);

        // --- 1. DICCIONARIO DE SINÓNIMOS (Corrección ortográfica y regionalismos) ---
        $synonyms = [
            'pizarron'     => 'pizarra', 'pizarrones'   => 'pizarra', 'pizarras'     => 'pizarra',
            'computador'   => 'computadora', 'ordenador'    => 'computadora', 'pc'           => 'computadora',
            'escritorios'  => 'escritorio', 'mesas'        => 'mesa', 'sillas'       => 'silla',
            'asiento'      => 'silla', 'libreros'     => 'librero', 'librera'      => 'librero',
            'estantes'     => 'estante', 'archivos'     => 'archivo', 'archiveros'   => 'archivero', 
            'camaras'      => 'camara', 'laptops'      => 'laptop', 'monitores'    => 'monitor', 
            'impresoras'   => 'impresora', 'ventiladores' => 'ventilador', 'extintores'   => 'extintor', 
            'podios'       => 'podio', 'telefonos'    => 'telefono', 'proyectores'  => 'proyector', 
            'escaneres'    => 'escaner', 'sofás'        => 'sofa', 'sillones'     => 'sillon', 
            'lámparas'     => 'lampara', 'grabadora'    => 'equipoelectronico', 'televisor'    => 'television',
            'pantalla'     => 'monitor', 'pizarra'      => 'pizarra'
        ];

        // Función de ayuda para buscar coincidencias (Exacta, Prefijo o Sinónimo)
        $findMatch = function(string $input) use ($tipos, $synonyms) {
            if ($input === '') return null;
            
            // A. Probar con sinónimos primero
            foreach ($synonyms as $bad => $good) {
                if (str_starts_with($input, $bad) || $input === $bad) {
                    foreach ($tipos as $t) {
                        if ($this->simplifyString($t['nombre']) === $good) return (int)$t['id'];
                    }
                }
            }

            // B. Probar coincidencia de prefijo con tipos reales
            foreach ($tipos as $t) {
                $tn = $this->simplifyString($t['nombre']);
                if ($tn !== '' && (str_starts_with($input, $tn) || str_starts_with($tn, $input))) {
                    return (int) $t['id'];
                }
            }
            return null;
        };

        // --- PRIORIDAD 1: Coincidencia en el NOMBRE (Prefijo/Sinónimos) ---
        $match = $findMatch($sNombre);
        if ($match) return $match;

        // --- PRIORIDAD 2: Coincidencia en la COLUMNA de tipo ---
        $match = $findMatch($sRaw);
        if ($match) return $match;

        // --- PRIORIDAD 3: Coincidencia de CONTENIDO en el nombre ---
        foreach ($tipos as $t) {
            $tn = $this->simplifyString($t['nombre']);
            if ($tn !== '' && str_contains($sNombre, $tn)) return (int) $t['id'];
        }

        // --- PRIORIDAD 4: Heurísticas Tecnológicas (Marcas y términos específicos) ---
        $keywordsIT = [
            'hp', 'dell', 'lenovo', 'toshiba', 'acer', 'asus', 'intel', 'amd', 'cpu', 'laptop',
            'workstation', 'server', 'servidor', 'computador', 'desktop', 'monitor', 'teclado',
            'mouse', 'ups', 'apc', 'cisco', 'switch', 'router', 'modem', 'hub', 'wifi', 'accesspoint',
            'disco', 'memoria', 'procesador', 'tablet', 'ipad', 'impresora', 'laserjet', 'epson',
            'canon', 'brother', 'pantalla', 'scanner', 'escaner', 'hikvision', 'dahua', 'camara',
            'nvr', 'dvr', 'phone', 'telefono', 'avaya', 'grandstream', 'polycom', 'sony', 'lg',
            'samsung', 'kyocera', 'logitech', 'linksys', 'tp-link', 'tivo', 'roku', 'smart', 'tv'
        ];
        
        foreach ($keywordsIT as $kw) {
            if (str_contains($sNombre, $kw) || str_contains($sRaw, $kw)) {
                // Primero intentar "Equipo informático"
                foreach ($tipos as $t) {
                    if ($this->simplifyString($t['nombre']) === 'equipoinformatico') return (int) $t['id'];
                }
                // Si no, "Equipo electrónico"
                foreach ($tipos as $t) {
                    if ($this->simplifyString($t['nombre']) === 'equipoelectronico') return (int) $t['id'];
                }
            }
        }

        // --- PRIORIDAD 5: Heurísticas de Otros Equipos (Aire, Extintor, Ventilador) ---
        $keywordsOther = [
            'aire', 'split', 'btu', 'refrigeraci', 'acondicionado', 'extintor', 'co2', 'fuego',
            'ventilador', 'techo', 'industrial', 'pedestal', 'proyector', 'poyec',
            'television', 'televisor', 'smart', 'tv'
        ];

        foreach ($keywordsOther as $kw) {
            if (str_contains($sNombre, $kw)) {
                // Intentar buscar el tipo específico (ej: "Aire acondicionado")
                foreach ($tipos as $t) {
                    if (str_contains($this->simplifyString($t['nombre']), $kw)) return (int) $t['id'];
                }
            }
        }

        // --- FALLBACK FINAL ---
        // Si parece mueble pero no sabemos cuál
        if (str_contains($sNombre, 'mueble') || str_contains($sNombre, 'madera') || str_contains($sNombre, 'metal')) {
            foreach ($tipos as $t) {
                if ($this->simplifyString($t['nombre']) === 'mueble') return (int) $t['id'];
            }
        }

        return (int) $tipos[0]['id'];
    }

    // -------------------------------------------------------------------------
    // CSV
    // -------------------------------------------------------------------------

    private function importFromCsv(string $filePath, int $salaId): array
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException("No se pudo leer el archivo CSV.");
        }

        $enc = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($enc && $enc !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $enc);
        }

        $content = str_replace("\xEF\xBB\xBF", '', $content);
        $lines   = explode("\n", str_replace("\r", '', $content));

        $delimiter = ';';
        if (!empty($lines[0]) && substr_count($lines[0], ',') > substr_count($lines[0], ';')) {
            $delimiter = ',';
        }

        $matrix = [];
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $matrix[] = str_getcsv($line, $delimiter);
            }
        }

        return $this->processMatrix($matrix, $salaId);
    }

    // -------------------------------------------------------------------------
    // UTILIDADES
    // -------------------------------------------------------------------------

    /**
     * Convierte una fecha del Excel (múltiples formatos)
     * al formato que MySQL espera para DATETIME: 'Y-m-d H:i:s'.
     * Retorna null si la fecha está vacía o no es reconocible.
     */
    private function normalizeFechaParaDb(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // 1. ¿Es un número serial de Excel que se coló como string? (ej: "38470")
        // Los seriales de fechas válidas (1980-2050) están entre 29000 y 55000.
        if (is_numeric($raw) && (float)$raw > 20000 && (float)$raw < 60000) {
            return $this->excelSerialToDate((float)$raw);
        }

        // 2. Detectar si es un AÑO aislado (ej: "2005")
        if (preg_match('/^\d{4}$/', $raw)) {
            return $raw . '-01-01 00:00:00';
        }

        // 3. Limpiar la cadena de caracteres basura pero mantener números y separadores
        // Maneja casos como "19/1082006" intentando rescatar los números
        $clean = preg_replace('/[^0-9\/\-\. :]/', '', $raw);
        
        // 4. Intentar parseo con DateTime para formatos comunes (ordenados por probabilidad)
        $formats = [
            'd/m/Y', 'd-m-Y', 'd.m.Y',
            'j/n/Y', 'j-n-Y', 'j.n.Y',
            'Y-m-d H:i:s', 'Y-m-d',
            'm/d/Y', 'Y/m/d',
            'd/m/y', 'd-m-y', 'j/n/y'
        ];

        foreach ($formats as $fmt) {
            try {
                $d = \DateTime::createFromFormat($fmt, $clean);
                if ($d && $d->format($fmt) === $clean) {
                    // Si el año es de 2 dígitos, PHP lo maneja automáticamente (00-69 -> 2000-2069)
                    return $d->format('Y-m-d H:i:s');
                }
            } catch (Throwable) {
                continue;
            }
        }

        // 5. Fallback con strtotime (último recurso para formatos locos)
        // Reemplazar '/' por '-' para que strtotime asuma formato europeo d-m-y
        $ts = strtotime(str_replace(['/', '.'], '-', $clean));
        if ($ts !== false && $ts > 0) {
            // Evitar fechas por defecto de sistema (como 1970) si no tienen sentido
            $year = (int)date('Y', $ts);
            if ($year > 1900 && $year < 2100) {
                return date('Y-m-d H:i:s', $ts);
            }
        }

        return null; 
    }

    private function columnLetterToIndex(string $col): int
    {
        $index = 0;
        $col   = strtoupper($col);

        for ($i = 0, $len = strlen($col); $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - 64);
        }

        return $index - 1;
    }

    private function cleanString(string $str): string
    {
        return trim(strip_tags($str));
    }

    private function simplifyString(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'à', 'è', 'ì', 'ò', 'ù'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u'],
            $str
        );
        // Mantener solo letras y números para comparaciones de encabezados
        return preg_replace('/[^a-z0-9]/', '', $str);
    }

    private function extractDataFromRow(array $row, array $mapping): array
    {
        $data = [];
        foreach ($mapping as $field => $index) {
            $data[$field] = ($index !== -1 && isset($row[$index])) ? (string) $row[$index] : '';
        }
        return $data;
    }

    private function recursiveRemove(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->recursiveRemove($path) : unlink($path);
        }
        rmdir($dir);
    }
}