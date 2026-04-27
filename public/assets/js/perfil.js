/**
 * JavaScript para el perfil de usuario
 */

let isEditMode = false;

function toggleEditMode(force) {
    isEditMode = force !== undefined ? force : !isEditMode;
    
    const inputs = ['inputUsername', 'inputNombre', 'inputEmail'];
    const btnToggle = document.getElementById('btnEditToggle');
    const formActions = document.getElementById('formActions');
    const avatarOverlay = document.getElementById('avatarEditOverlay');

    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            if (isEditMode) {
                el.removeAttribute('readonly');
                el.classList.add('active-edit');
            } else {
                el.setAttribute('readonly', true);
                el.classList.remove('active-edit');
            }
        }
    });

    if (isEditMode) {
        if (btnToggle) btnToggle.style.display = 'none';
        if (formActions) formActions.style.display = 'flex';
        if (avatarOverlay) avatarOverlay.style.display = 'flex';
    } else {
        if (btnToggle) btnToggle.style.display = 'flex';
        if (formActions) formActions.style.display = 'none';
        if (avatarOverlay) avatarOverlay.style.display = 'none';
        if (force === false) window.location.reload();
    }
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatarPreview = document.getElementById('avatarPreview');
            if (avatarPreview) {
                let img = avatarPreview.querySelector('img');
                if (!img) {
                    avatarPreview.innerHTML = '<img src="" alt="Avatar"><div class="avatar-edit-overlay" id="avatarEditOverlay" onclick="document.getElementById(\'fotoInput\').click()"><span>📷</span></div>';
                    img = avatarPreview.querySelector('img');
                }
                img.src = e.target.result;
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Global exposure
window.toggleEditMode = toggleEditMode;
window.previewImage = previewImage;
