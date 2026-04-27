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
                'csv'   => $this->importFromCsv($filePath, $salaId),
                default => throw new RuntimeException("Formato no soportado: .$extension. Use .xlsx o .csv"),
            };
        } catch (Throwable $e) {
            return ['success' => 0, 'errors' => ["Error crítico: " . $e->getMessage()], 'total' => 0];
        } finally {
            $this->recursiveRemove($tmpDir);
        }
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

        $xml = simplexml_load_file($path);
        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        // IDs de formato built-in de Excel que corresponden a fechas
        $builtinDateFmtIds = range(14, 22);

        // Leer formatos personalizados que parezcan fechas
        $customDateFmtIds = [];
        foreach ($xml->numFmts->numFmt ?? [] as $fmt) {
            $id   = (int)    $fmt['numFmtId'];
            $code = (string) $fmt['formatCode'];
            // Heurística: si el formato contiene d, m, y, h (pero no solo #,0,%)
            if (preg_match('/[dmyh]/i', $code) && !preg_match('/^[#0%,.\s]+$/', $code)) {
                $customDateFmtIds[] = $id;
            }
        }

        $allDateIds = array_merge($builtinDateFmtIds, $customDateFmtIds);

        // Mapear cada xf (cell format index) a si es fecha o no
        foreach ($xml->cellXfs->xf ?? [] as $idx => $xf) {
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
     * Excel tiene un bug histórico (año 1900 bisiesto falso) que se
     * corrige restando 1 a seriales >= 60.
     */
    private function excelSerialToDate(float $serial): string
    {
        if ($serial <= 0) {
            return '';
        }

        // Corrección del bug de Excel con el año bisiesto de 1900
        $offset    = $serial >= 60 ? $serial - 1 : $serial;
        $timestamp = ($offset - self::EXCEL_EPOCH) * 86400;

        // Descartamos valores absurdos (antes de 1970 o después de 2100)
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
        $mapping      = ['codigo' => -1, 'nombre' => -1, 'tipo' => -1, 'descripcion' => -1, 'estado' => -1, 'fecha' => -1];
        $headerRowIdx = -1;
        $bestScore    = 0;

        // MEJORA: elegir la fila con MÁS columnas reconocidas (no la primera que pase el umbral)
        foreach ($matrix as $idx => $row) {
            $currentMapping = $this->guessMapping($row);
            $score          = count(array_filter($currentMapping, static fn($v) => $v !== -1));

            $hasMinimum = $currentMapping['nombre'] !== -1
                && ($currentMapping['codigo'] !== -1 || $currentMapping['descripcion'] !== -1);

            if ($hasMinimum && $score > $bestScore) {
                $bestScore    = $score;
                $mapping      = $currentMapping;
                $headerRowIdx = $idx;
            }
        }

        if ($headerRowIdx === -1) {
            throw new RuntimeException(
                "No se pudieron identificar las columnas. " .
                "Asegúrate de tener encabezados como: Código_Activo, Nombre_Activo, Categoría, Estado, Observaciones."
            );
        }

        $results = ['success' => 0, 'errors' => [], 'total' => 0];

        for ($i = $headerRowIdx + 1; $i < count($matrix); $i++) {
            $row = $matrix[$i];

            if (empty(array_filter($row, static fn($v) => trim((string) $v) !== ''))) {
                continue;
            }

            $results['total']++;
            $data = $this->extractDataFromRow($row, $mapping);

            $nombre = $this->cleanString($data['nombre'] ?? '');
            $desc   = $this->cleanString($data['descripcion'] ?? '');

            // Fallback: si no hay nombre, usar los primeros 80 chars de la descripción
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

            // Convertir la fecha del Excel a formato MySQL (Y-m-d H:i:s) o null
            $fechaDb = $this->normalizeFechaParaDb($data['fecha'] ?? '');

            $res = $this->sigmuService->registrarActivo(
                $codigo,
                mb_strimwidth($nombre, 0, 98, '…'),
                $this->resolveTipoActivo($data['tipo'] ?? ''),
                $desc,
                $this->normalizeEstado($data['estado'] ?? ''),
                $salaId,
                [], // 7mo: fotoPaths (vacío en importación)
                $fechaDb // 8vo: fechaCreado (la fecha real del Excel)
            );
            if ($res['success']) {
                $results['success']++;
            } else {
                $results['errors'][] = "Fila " . ($i + 1) . ": " . $res['message'];
            }
        }

        return $results;
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
        $m = ['codigo' => -1, 'nombre' => -1, 'tipo' => -1, 'descripcion' => -1, 'estado' => -1, 'fecha' => -1];

        $ignoreList = '/^(responsable|encargado|departamento|piso|marca|modelo|color|material|'
                    . 'ubicacion|ubicacionfisica|edificio|aula|sala|proveedor|factura|garantia|'
                    . 'fechamantenimiento|fechaultimo|fechaproximo|fechaultimomantenimiento|'
                    . 'fechaproximomantenimiento|vidautil|costo|costoadquisicion|valoractual|'
                    . 'valorcompra|codigobarra|barcode|numero|correlativo)$/i';

        $dict = [
            'codigo' => '/^(codigoactivo|codactivo|codigobien|nroinventario|numeroinventario|'
                      . 'placa|sku|codigopat|codigopatrimonial|codigo|codigointerno|folio|idbien|idactivo|cod|inv|ref|nro|tag|bn)$/',

            'nombre' => '/^(descripciondelbien|descripciondelactivo|descripciondelelemento|'
                      . 'descripciondelequipo|descripcionbien|nombreactivo|nomactivo|nombrearticulo|'
                      . 'nombreelemento|nombreequipo|mobiliario|articulo|elemento|'
                      . 'bien|activo|objeto|item|nombre|nom)$/',

            'tipo'   => '/^(tipodeactivo|tipoactivo|tipobien|categoriaactivo|clasificaciondeactivo|'
                      . 'clasificacion|subcategoria|categoria|tipo|cat|cla|fam|gen)$/',

            'estado' => '/^(estadoactual|estadoactivo|estadofisico|condicionactual|condicion|estado|cond|stat)$/',

            'fecha'  => '/^(fechaingreso|fechaadquisicion|fecharegistro|fechacompra|'
                      . 'fechaalta|fechaincorporacion|fecha|ingreso|adquisicion|adquisi|registro|crea|incorporacion|compra|date)$/',

            // descripcion va AL FINAL con contains (sin ^$) —
            // el ignoreList ya bloqueó los falsos positivos
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

    private function resolveTipoActivo(string $raw): int
    {
        $s = $this->simplifyString($raw);

        if ($s === '') {
            return 1;
        }

        $tipos = $this->repository->typesActive();

        // Búsqueda exacta primero
        foreach ($tipos as $t) {
            if ($this->simplifyString($t['nombre']) === $s) {
                return (int) $t['id'];
            }
        }

        // Búsqueda parcial
        foreach ($tipos as $t) {
            $tn = $this->simplifyString($t['nombre']);
            if (str_contains($s, $tn) || str_contains($tn, $s)) {
                return (int) $t['id'];
            }
        }

        return !empty($tipos) ? (int) $tipos[0]['id'] : 1;
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
     * Convierte una fecha del Excel (dd/mm/yyyy, yyyy-mm-dd, dd-mm-yyyy)
     * al formato que MySQL espera para DATETIME: 'Y-m-d H:i:s'.
     * Retorna null si la fecha está vacía o no es reconocible.
     */
    private function normalizeFechaParaDb(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Formato dd/mm/yyyy  (viene de excelSerialToDate)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw, $m)) {
            $ts = mktime(0, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
            return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        }

        // Formato yyyy-mm-dd hh:ii:ss  (ya correcto para MySQL)
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw)) {
            return $raw;
        }

        // Formato yyyy-mm-dd  (solo fecha, sin hora)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw . ' 00:00:00';
        }

        // Formato dd-mm-yyyy con guiones
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $raw, $m)) {
            $ts = mktime(0, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
            return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        }

        // Formato dd.mm.yyyy con puntos (común en Europa)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $raw, $m)) {
            $ts = mktime(0, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
            return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        }

        return null; // formato no reconocido → MySQL usará CURRENT_TIMESTAMP (por COALESCE en el SP)
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