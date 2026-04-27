/**
 * Gestión de Usuarios - Lógica de filtros y búsqueda
 */
document.addEventListener('DOMContentLoaded', function() {
    // VARIABLES GLOBALES
    const searchInput = document.querySelector('.search-box input');
    const allUsers = document.querySelectorAll('.user-item:not(.header)');

    // PANEL DE FILTROS FUNCIONAL
    const filterBtn = document.getElementById('toggleFilterPanel');
    const filterPanel = document.getElementById('filterPanel');
    const filterRol = document.getElementById('filterRol');
    const filterEstado = document.getElementById('filterEstado');
    const resetBtn = document.getElementById('resetFilters');

    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', () => {
            filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
        });
    }

    function aplicarFiltros() {
        const rolSeleccionado = filterRol ? filterRol.value.toLowerCase().trim() : '';
        const estadoSeleccionado = filterEstado ? filterEstado.value.toLowerCase().trim() : '';
        const busquedaTexto = searchInput ? searchInput.value.toLowerCase().trim() : '';

        allUsers.forEach(userRow => {
            const usernameEl = userRow.querySelector('.user-username');
            const userRoleEl = userRow.querySelector('.user-role');
            const userStatusEl = userRow.querySelector('.user-status');

            if (!usernameEl || !userRoleEl || !userStatusEl) return;

            const username = usernameEl.textContent.toLowerCase();
            const userRole = userRoleEl.textContent.toLowerCase();
            const userStatus = userStatusEl.textContent.toLowerCase();

            let coincide = true;

            if (busquedaTexto !== '') {
                coincide = coincide && (username.includes(busquedaTexto) || userRole.includes(busquedaTexto));
            }

            if (rolSeleccionado !== '') {
                coincide = coincide && userRole.includes(rolSeleccionado);
            }

            if (estadoSeleccionado !== '') {
                coincide = coincide && userStatus.includes(estadoSeleccionado);
            }

            userRow.style.display = coincide ? '' : 'none';
        });
    }

    if (filterRol) filterRol.addEventListener('change', aplicarFiltros);
    if (filterEstado) filterEstado.addEventListener('change', aplicarFiltros);
    if (searchInput) searchInput.addEventListener('input', aplicarFiltros);

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (filterRol) filterRol.value = '';
            if (filterEstado) filterEstado.value = '';
            if (searchInput) searchInput.value = '';
            allUsers.forEach(row => row.style.display = '');
        });
    }
});
