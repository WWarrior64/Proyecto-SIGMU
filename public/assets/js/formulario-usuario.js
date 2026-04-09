/* =============================================
   FORMULARIO USUARIO - SIGMU
   Logica JavaScript para el formulario unificado
   ============================================= */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formUsuario');
    const fotoInput = document.getElementById('fotoUsuario');
    const avatarEditBtn = document.querySelector('.avatar-edit-btn');
    
    // Abrir selector de foto al clickear el lapiz
    if (avatarEditBtn && fotoInput) {
        avatarEditBtn.addEventListener('click', () => {
            fotoInput.click();
        });
    }

    // Previsualizar foto seleccionada
    if (fotoInput) {
        fotoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const avatar = document.querySelector('.avatar');
                    avatar.innerHTML = `<img src="${e.target.result}" alt="Foto perfil" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Deshabilitar boton mientras se procesa
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    window.location.href = '/sigmu/administracion_usuarios/gestion_usuarios?mensaje=guardado';
                } else {
                    // Mostrar mensaje de error estilo toast durante 6 segundos
                    const mensajeError = result.message || 'Error desconocido al guardar';
                    
                    // Crear toast temporal
                    const toast = document.createElement('div');
                    toast.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #dc3545;
                        color: white;
                        padding: 18px 24px;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                        z-index: 9999;
                        font-weight: 500;
                        font-size: 15px;
                        max-width: 450px;
                        animation: slideIn 0.3s ease;
                    `;
                    toast.textContent = mensajeError;
                    document.body.appendChild(toast);
                    
                    // Auto desaparecer despues de 6 segundos
                    setTimeout(() => {
                        toast.style.animation = 'slideOut 0.3s ease';
                        setTimeout(() => toast.remove(), 300);
                    }, 6000);
                    
                    // Agregar animaciones si no existen
                    if (!document.getElementById('toastAnimations')) {
                        const style = document.createElement('style');
                        style.id = 'toastAnimations';
                        style.textContent = `
                            @keyframes slideIn {
                                from { transform: translateX(100%); opacity: 0; }
                                to { transform: translateX(0); opacity: 1; }
                            }
                            @keyframes slideOut {
                                from { transform: translateX(0); opacity: 1; }
                                to { transform: translateX(100%); opacity: 0; }
                            }
                        `;
                        document.head.appendChild(style);
                    }
                }
            } catch (error) {
                alert('Error de conexión al guardar usuario');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});
