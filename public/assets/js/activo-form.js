/**
 * JavaScript para formularios de activos (registrar y editar)
 * Incluye validaciones de datos y funcionalidad de drag & drop
 */

document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.getElementById('activoForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = ['tipo_activo_id', 'codigo', 'nombre', 'estado'];
            // Agregar sala_id solo si existe (para editar)
            const salaIdField = document.getElementById('sala_id');
            if (salaIdField) {
                requiredFields.push('sala_id');
            }
            
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

    // Validación en tiempo real
    document.querySelectorAll('input[required], select[required]').forEach(function(field) {
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });

        field.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('error');
            }
        });
    });

    // Validación de longitud del nombre y generación automática de código
    const nombreField = document.getElementById('nombre');
    const codigoField = document.getElementById('codigo');
    
    if (nombreField) {
        let timeoutId = null;
        
        nombreField.addEventListener('input', function() {
            // Validar longitud
            if (this.value.length > 100) {
                this.value = this.value.substring(0, 100);
                alert('El nombre no puede exceder 100 caracteres');
                return;
            }
            
            // Generar código automáticamente cuando el usuario escriba el nombre
            const nombre = this.value.trim();
            
            // Cancelar petición anterior si existe
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
            
            if (nombre.length >= 3) {
                // Esperar 500ms después de que el usuario deje de escribir
                timeoutId = setTimeout(function() {
                    fetch('/sigmu/activo/generar-codigo?nombre=' + encodeURIComponent(nombre))
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && codigoField) {
                                codigoField.value = data.codigo;
                                codigoField.classList.remove('error');
                            }
                        })
                        .catch(error => {
                            console.error('Error al generar código:', error);
                        });
                }, 500);
            } else if (codigoField && nombre.length === 0) {
                // Si el nombre está vacío, limpiar el código
                codigoField.value = '';
            }
        });
    }

    // Validación de formato del código
    if (codigoField) {
        codigoField.addEventListener('input', function() {
            this.value = this.value.replace(/[^A-Za-z0-9\-]/g, '');
        });
    }

    // Validación de longitud de la descripción
    const descripcionField = document.getElementById('descripcion');
    if (descripcionField) {
        descripcionField.addEventListener('input', function() {
            if (this.value.length > 500) {
                this.value = this.value.substring(0, 500);
                alert('La descripción no puede exceder 500 caracteres');
            }
        });
    }

    // Validación del archivo de imagen
    const fileInput = document.getElementById('foto');
    const fileLabel = document.querySelector('.file-input-label');

    if (fileInput && fileLabel) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB

                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF.');
                    this.value = '';
                    return;
                }

                if (file.size > maxSize) {
                    alert('El archivo es demasiado grande. Tamaño máximo: 5MB.');
                    this.value = '';
                    return;
                }

                updateFileLabel(file.name);
            }
        });

        // Drag and drop para archivo
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
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileLabel(files[0].name);
            }
        });
    }

    function updateFileLabel(fileName) {
        const span = fileLabel?.querySelector('span');
        if (span) {
            span.textContent = fileName;
        }
    }
});