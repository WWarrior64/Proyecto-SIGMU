/**
 * JS para Reportar Falla
 */

document.addEventListener('DOMContentLoaded', () => {
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

    const formReportarFalla = document.getElementById('formReportarFalla');
    if (formReportarFalla) {
        formReportarFalla.addEventListener('submit', function(e) {
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
                    showToast('Falla reportada correctamente. El activo ha cambiado a estado "En Reparación".');
                    window.location.href = '/sigmu/mantenimiento';
                } else {
                    showToast('Error: ' + data.message, 'error');
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'REGISTRAR REPORTE';
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error al procesar el reporte', 'error');
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'REGISTRAR REPORTE';
            });
        });
    }
});
