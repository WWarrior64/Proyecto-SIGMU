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
                    const modo = formData.get('modo');
                    const mensaje = modo === 'editar' ? 'Usuario editado correctamente' : 'Usuario creado correctamente';
                    window.location.href = '/sigmu/administracion_usuarios/gestion_usuarios?success=' + encodeURIComponent(mensaje);
                } else {
                    showToast(result.message || 'Error desconocido al guardar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión al guardar usuario', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});
