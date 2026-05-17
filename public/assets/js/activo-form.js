/**
 * JavaScript para formularios de activos (registrar y editar)
 * Fusionado con lógica avanzada de gestión de fotos
 */

// Objeto global para acumular los archivos seleccionados
let accumulatedFiles = new DataTransfer();

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('activoForm');
    const nombreField = document.getElementById('nombre');
    const codigoField = document.getElementById('codigo');
    const descripcionField = document.getElementById('descripcion');
    const edificioSelect = document.getElementById('edificio_id');
    const salaSelect = document.getElementById('sala_id');
    const fileInput = document.getElementById('fotos');
    const fileLabel = document.querySelector('.file-input-label');

    // --- 1. VALIDACIÓN DE FORMULARIO AL ENVIAR ---
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = ['tipo_activo_id', 'codigo', 'nombre', 'estado'];
            if (salaSelect) requiredFields.push('sala_id');
            
            let isValid = true;
            let firstError = null;

            requiredFields.forEach(function(fieldName) {
                const field = document.getElementById(fieldName);
                if (field && !field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    if (!firstError) firstError = field;
                } else if (field) {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos obligatorios marcados con *');
                if (firstError) firstError.focus();
            }
        });
    }

    // --- 2. VALIDACIONES EN TIEMPO REAL ---
    document.querySelectorAll('input[required], select[required]').forEach(function(field) {
        field.addEventListener('blur', function() {
            if (!this.value.trim()) this.classList.add('error');
            else this.classList.remove('error');
        });
        field.addEventListener('input', function() {
            if (this.value.trim()) this.classList.remove('error');
        });
    });

    // --- 3. GENERACIÓN DE CÓDIGO COMPLETO (NUEVO FORMATO) ---
    const codigoCuentaField = document.getElementById('codigo_cuenta');
    const tipoActivoField = document.getElementById('tipo_activo_id');
    
    let isManualCuenta = false; // Flag si el usuario editó manualmente el código prefijo

    /**
     * Genera el código completo llamando al backend
     * Formato: [PREFIJO]-[CORRELATIVO(3)]-[AÑO(2)]
     */
    function generarCodigoCompleto() {
        const nombre = nombreField ? nombreField.value.trim() : '';
        const tipoId = tipoActivoField ? parseInt(tipoActivoField.value || '0') : 0;
        let codigoCuenta = codigoCuentaField ? codigoCuentaField.value.trim() : '';
        
        // Si el prefijo no fue manual, limpiarlo para que el backend lo genere fresco
        if (!isManualCuenta && codigoCuentaField) {
            codigoCuentaField.value = '';
            codigoCuenta = '';
        }
        
        // Si el código de cuenta está vacío y hay nombre/tipo, se autogenerará
        if (codigoCuenta === '' && nombre.length >= 2 && tipoId > 0) {
            // Autogenerar: dejamos codigoCuenta vacío para que el backend lo genere
        } else if (codigoCuenta === '') {
            // No hay suficiente información, limpiar código
            if (codigoField) codigoField.value = '';
            return;
        }

        const params = new URLSearchParams({
            nombre: nombre,
            tipo_id: tipoId.toString(),
            codigo_cuenta: codigoCuenta
        });

        fetch('/sigmu/activo/generar-codigo-completo?' + params.toString())
            .then(res => res.json())
            .then(data => {
                if (data.success && data.codigo_completo) {
                    if (codigoField) codigoField.value = data.codigo_completo;
                    // Si se autogeneró el prefijo, reflejarlo en el campo
                    if (codigoCuentaField && data.codigo_cuenta && !isManualCuenta) {
                        codigoCuentaField.value = data.codigo_cuenta;
                    }
                } else {
                    if (codigoField) codigoField.value = '';
                }
            })
            .catch(err => console.error('Error al generar código completo:', err));
    }

    let codigoTimeoutId = null;

    // Evento: cambio en nombre del activo
    if (nombreField) {
        nombreField.addEventListener('input', function() {
            if (this.value.length > 100) {
                this.value = this.value.substring(0, 100);
                showToast('El nombre no puede exceder 100 caracteres', 'error');
                return;
            }
            
            if (codigoTimeoutId) clearTimeout(codigoTimeoutId);
            codigoTimeoutId = setTimeout(generarCodigoCompleto, 500);
        });
    }

    // Evento: cambio en tipo de activo
    if (tipoActivoField) {
        tipoActivoField.addEventListener('change', function() {
            if (codigoTimeoutId) clearTimeout(codigoTimeoutId);
            codigoTimeoutId = setTimeout(generarCodigoCompleto, 300);
        });
    }

    // Evento: cambio en código de cuenta (manual)
    if (codigoCuentaField) {
        codigoCuentaField.addEventListener('input', function() {
            this.value = this.value.replace(/[^A-Za-z0-9\-]/g, '').toUpperCase();
            
            // Si está vacío, permitir autogeneración después
            if (this.value.length === 0) {
                isManualCuenta = false;
                // Regenerar automáticamente si hay nombre y tipo
                if (nombreField && nombreField.value.trim().length >= 2 && tipoActivoField && tipoActivoField.value) {
                    if (codigoTimeoutId) clearTimeout(codigoTimeoutId);
                    codigoTimeoutId = setTimeout(generarCodigoCompleto, 300);
                }
            } else {
                isManualCuenta = true;
                // Si tiene valor manual, generar código completo
                if (codigoTimeoutId) clearTimeout(codigoTimeoutId);
                codigoTimeoutId = setTimeout(generarCodigoCompleto, 300);
            }
        });
    }

    if (descripcionField) {
        descripcionField.addEventListener('input', function() {
            if (this.value.length > 500) {
                this.value = this.value.substring(0, 500);
                alert('La descripción no puede exceder 500 caracteres');
            }
        });
    }

    // --- 4. FILTRADO DE SALAS POR EDIFICIO ---
    if (edificioSelect && salaSelect) {
        const allSalas = Array.from(salaSelect.options);
        function filterSalas() {
            const edificioId = edificioSelect.value;
            const currentSalaId = salaSelect.value;
            salaSelect.innerHTML = '<option value="">Seleccionar sala...</option>';
            allSalas.forEach(option => {
                if (option.value !== "" && option.getAttribute('data-edificio') === edificioId) {
                    salaSelect.appendChild(option.cloneNode(true));
                }
            });
            if (currentSalaId) salaSelect.value = currentSalaId;
        }
        edificioSelect.addEventListener('change', filterSalas);
        if (edificioSelect.value) filterSalas();
    }

    // --- 5. DRAG & DROP PARA FOTOS ---
    if (fileLabel && fileInput) {
        fileLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        fileLabel.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        fileLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                // Usamos la misma lógica de acumulación que el input manual
                handleNewFiles(e.dataTransfer.files);
            }
        });
    }
});

/**
 * Procesa nuevos archivos (vía input o drop) y los acumula
 */
function handleNewFiles(files) {
    const input = document.getElementById('fotos');
    const hasExistingPhotos = window.HAS_EXISTING_PHOTOS || false;
    
    Array.from(files).forEach(file => {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(file.type)) {
            alert(`Archivo "${file.name}" no permitido (Solo JPG, PNG, GIF).`);
            return;
        }
        if (file.size > maxSize) {
            alert(`Archivo "${file.name}" es demasiado grande (Máx 5MB).`);
            return;
        }

        const exists = Array.from(accumulatedFiles.files).some(f => f.name === file.name && f.size === file.size);
        if (!exists) accumulatedFiles.items.add(file);
    });

    if (input) input.files = accumulatedFiles.files;
    renderPreview(hasExistingPhotos);
}

// Vinculada al onchange del input
function previewNewPhotos(input, hasExistingPhotos = false) {
    window.HAS_EXISTING_PHOTOS = hasExistingPhotos;
    if (input.files) handleNewFiles(input.files);
}

function removeTempFile(index, hasExistingPhotos) {
    const input = document.getElementById('fotos');
    const newDT = new DataTransfer();
    const files = accumulatedFiles.files;
    for (let i = 0; i < files.length; i++) {
        if (i !== index) newDT.items.add(files[i]);
    }
    accumulatedFiles = newDT;
    if (input) input.files = accumulatedFiles.files;
    renderPreview(hasExistingPhotos);
}

function renderPreview(hasExistingPhotos) {
    const container = document.getElementById('newPhotosPreview');
    const text = document.getElementById('fileInputText');
    const files = accumulatedFiles.files;
    if (!container) return;
    container.innerHTML = '';
    
    if (files.length > 0) {
        if (text) text.innerText = files.length + ' archivos seleccionados';
        Array.from(files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'temp-photo-item';
                const isFirstNew = index === 0;
                const showPrincipal = !hasExistingPhotos && isFirstNew;
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="btn-remove-temp" onclick="removeTempFile(${index}, ${hasExistingPhotos})">×</button>
                    <span class="label-new">NUEVA</span>
                    ${showPrincipal ? '<span class="label-principal">SERÁ PRINCIPAL</span>' : ''}
                `;
                container.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    } else if (text) {
        text.innerText = 'Seleccionar archivos o arrastrar aquí (puedes elegir varios)';
    }
}

function submitFotoAction(url, fotoId) {
    const form = document.getElementById('fotoActionForm');
    const inputId = document.getElementById('action_foto_id');
    if (form && inputId) {
        form.action = url;
        inputId.value = fotoId;
        form.submit();
    }
}
