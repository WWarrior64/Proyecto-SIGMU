<?php
/** @var array $sessionUser */
/** @var array $edificios */

$sigmuPageTitle = 'REPORTAR FALLA';
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/mantenimiento.css'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>
<style>
        .report-container { padding: 40px 20px; max-width: 700px; margin: 0 auto; }
        .report-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; }
        .report-header { background: #8b0000; color: white; padding: 20px; text-align: center; font-size: 18px; font-weight: 700; }
        .report-body { padding: 30px; }
        .form-section { margin-bottom: 25px; }
        .form-section-title { font-size: 14px; font-weight: 700; color: #8b0000; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; text-transform: uppercase; }
        .loader { display: none; margin-left: 10px; }
    </style>

    <div class="back-btn-container">
        <button class="back-btn" onclick="window.location.href='/sigmu/mantenimiento'" title="Regresar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>

    <div class="report-container">
        <div class="report-card">
            <div class="report-header">FORMULARIO DE REPORTE DE INCIDENCIA</div>
            <form id="formReportarFalla" class="report-body">
                
                <div class="form-section">
                    <div class="form-section-title">Localización del Activo</div>
                    <div class="form-group">
                        <label for="edificio_id">Seleccione Edificio:</label>
                        <select id="edificio_id" name="edificio_id" class="form-control" required>
                            <option value="">-- Seleccione un edificio --</option>
                            <?php foreach ($edificios as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sala_id">Seleccione Sala: <span class="loader" id="loaderSalas">⏳</span></label>
                        <select id="sala_id" name="sala_id" class="form-control" required disabled>
                            <option value="">-- Primero seleccione edificio --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="activo_id">Seleccione Activo: <span class="loader" id="loaderActivos">⏳</span></label>
                        <select id="activo_id" name="activo_id" class="form-control" required disabled>
                            <option value="">-- Primero seleccione sala --</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Detalle de la Falla</div>
                    <div class="form-group">
                        <label for="tipo_falla">Tipo de Falla:</label>
                        <select id="tipo_falla" name="tipo_falla" class="form-control" required>
                            <option value="">-- Seleccione tipo --</option>
                            <option value="hardware">Falla de Hardware</option>
                            <option value="software">Falla de Software</option>
                            <option value="electrico">Problema Eléctrico</option>
                            <option value="fisico">Daño Físico / Estructural</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción detallada del problema:</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="4" placeholder="Describa qué sucede con el activo..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fecha_deteccion">Fecha de detección:</label>
                        <input type="date" id="fecha_deteccion" name="fecha_deteccion" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="button" class="btn-secondary" style="flex: 1;" onclick="window.location.href='/sigmu/mantenimiento'">CANCELAR</button>
                    <button type="submit" class="btn-primary" style="flex: 2; background: #8b0000;">REGISTRAR REPORTE</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const comboEdificio = document.getElementById('edificio_id');
        const comboSala = document.getElementById('sala_id');
        const comboActivo = document.getElementById('activo_id');
        const loaderSalas = document.getElementById('loaderSalas');
        const loaderActivos = document.getElementById('loaderActivos');

        comboEdificio.addEventListener('change', function() {
            const edId = this.value;
            comboSala.innerHTML = '<option value="">-- Seleccione una sala --</option>';
            comboSala.disabled = true;
            comboActivo.innerHTML = '<option value="">-- Primero seleccione sala --</option>';
            comboActivo.disabled = true;

            if (edId) {
                loaderSalas.style.display = 'inline';
                fetch('/sigmu/ajax/salas?edificio_id=' + edId)
                    .then(r => r.json())
                    .then(data => {
                        data.forEach(s => {
                            const opt = document.createElement('option');
                            opt.value = s.id;
                            opt.textContent = 'Piso ' + s.numero_piso + ' - ' + s.nombre;
                            comboSala.appendChild(opt);
                        });
                        comboSala.disabled = false;
                    })
                    .finally(() => loaderSalas.style.display = 'none');
            }
        });

        comboSala.addEventListener('change', function() {
            const salaId = this.value;
            const edificioId = comboEdificio.value;
            comboActivo.innerHTML = '<option value="">-- Seleccione un activo --</option>';
            comboActivo.disabled = true;

            if (salaId) {
                loaderActivos.style.display = 'inline';
                const qs = new URLSearchParams({ sala_id: salaId, edificio_id: edificioId || '' });
                fetch('/sigmu/ajax/activos?' + qs.toString())
                    .then(r => {
                        if (!r.ok) {
                            throw new Error('HTTP ' + r.status);
                        }
                        return r.json();
                    })
                    .then(data => {
                        if (!Array.isArray(data)) {
                            throw new Error('Respuesta inválida');
                        }
                        data.forEach(a => {
                            const opt = document.createElement('option');
                            opt.value = a.id;
                            const codigo = a.codigo != null ? a.codigo : '';
                            const nombre = a.nombre != null ? a.nombre : '';
                            const estado = a.estado != null ? a.estado : '';
                            opt.textContent = '[' + codigo + '] ' + nombre + ' (' + estado + ')';
                            comboActivo.appendChild(opt);
                        });
                        if (data.length === 0) {
                            const opt = document.createElement('option');
                            opt.value = '';
                            opt.textContent = '-- No hay activos en esta sala --';
                            comboActivo.appendChild(opt);
                        }
                        comboActivo.disabled = false;
                    })
                    .catch(err => {
                        console.error(err);
                        comboActivo.innerHTML = '<option value="">-- Error al cargar activos; reintente --</option>';
                        comboActivo.disabled = false;
                    })
                    .finally(() => loaderActivos.style.display = 'none');
            }
        });

        document.getElementById('formReportarFalla').addEventListener('submit', function(e) {
            e.preventDefault();
            const btnSubmit = this.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'REGISTRANDO...';

            fetch('/sigmu/mantenimiento/reportar', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Falla reportada correctamente. El activo ha cambiado a estado "En Reparación".');
                    window.location.href = '/sigmu/mantenimiento';
                } else {
                    alert('Error: ' + data.message);
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'REGISTRAR REPORTE';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al procesar el reporte');
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'REGISTRAR REPORTE';
            });
        });
    </script>
<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
