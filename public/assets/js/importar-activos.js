/**
 * JavaScript para la vista de Importar Activos
 */
document.addEventListener('DOMContentLoaded', () => {
    // Animación simple para el input de archivo
    const fileInput = document.querySelector('.file-input');
    const fileLabel = document.querySelector('.file-input-label span');
    
    if (fileInput && fileLabel) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                fileLabel.textContent = e.target.files[0].name;
            }
        });
    }
});
