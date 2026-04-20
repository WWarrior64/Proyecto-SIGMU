/**
 * ✅ HISTORIAL ACTIVO - BUSQUEDA EN TIEMPO REAL
 * 
 * @author SIGMU UNICAES
 */
document.addEventListener('DOMContentLoaded', function() {
    // Menu lateral
    const menuBtn = document.getElementById('menuBtn');
    if (menuBtn) {
        // En algunas vistas el menu se maneja por global-menu.js y en otras por toggle de clase
        if (!menuBtn.onclick) {
            menuBtn.addEventListener('click', function() {
                document.body.classList.toggle('menu-open');
            });
        }
    }

    // ✅ BUSQUEDA EN TIEMPO REAL - SIN PERDER FOCO
    const searchInput = document.getElementById('searchInputHistorial');
    let searchTimeout;
    
    // Restaurar foco y posicion del cursor despues de recargar
    if (searchInput) {
        // Obtener ultima posicion guardada
        const ultimaPosicion = sessionStorage.getItem('searchCursorPos');
        if (ultimaPosicion !== null) {
            searchInput.focus();
            searchInput.setSelectionRange(+ultimaPosicion, +ultimaPosicion);
            sessionStorage.removeItem('searchCursorPos');
        }
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            // Guardar posicion actual del cursor antes de recargar
            sessionStorage.setItem('searchCursorPos', searchInput.selectionStart);
            
            searchTimeout = setTimeout(() => {
                aplicarFiltros();
            }, 450);
        });
    }

    // ✅ APLICAR FILTROS AUTOMATICAMENTE AL CAMBIAR SELECTS
    const selectAccion = document.querySelector('select[name="accion"]');
    const selectEstado = document.querySelector('select[name="estado"]');
    const selectUsuario = document.querySelector('select[name="usuario"]');

    if (selectAccion) selectAccion.addEventListener('change', aplicarFiltros);
    if (selectEstado) selectEstado.addEventListener('change', aplicarFiltros);
    if (selectUsuario) selectUsuario.addEventListener('change', aplicarFiltros);

    // ✅ BOTON LIMPIAR FILTROS
    const botonLimpiar = document.getElementById('limpiarFiltrosBtn');
    if (botonLimpiar) {
        botonLimpiar.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Limpiar todos los campos
            if (searchInput) searchInput.value = '';
            if (selectAccion) selectAccion.selectedIndex = 0;
            if (selectEstado) selectEstado.selectedIndex = 0;
            if (selectUsuario) selectUsuario.selectedIndex = 0;

            // Redirigir sin parametros (pero manteniendo el ID si existe)
            const urlParams = new URLSearchParams(window.location.search);
            const activoId = urlParams.get('id');
            
            if (activoId) {
                window.location.href = window.location.pathname + '?id=' + activoId;
            } else {
                window.location.href = window.location.pathname;
            }
        });
    }

    /**
     * Aplica los filtros y actualiza la pagina
     */
    function aplicarFiltros() {
        const urlParams = new URLSearchParams(window.location.search);
        const params = new URLSearchParams();
        
        // 1. Mantener ID del activo si existe (para historial individual)
        const activoId = urlParams.get('id');
        if (activoId) {
            params.set('id', activoId);
        }

        // 2. Agregar busqueda
        if (searchInput && searchInput.value.trim() !== '') {
            params.set('busqueda', searchInput.value.trim());
        }

        // 3. Agregar filtro accion
        if (selectAccion && selectAccion.value !== '') {
            params.set('accion', selectAccion.value);
        }

        // 4. Agregar filtro estado
        if (selectEstado && selectEstado.value !== '') {
            params.set('estado', selectEstado.value);
        }

        // 5. Agregar filtro usuario (para historial general)
        if (selectUsuario && selectUsuario.value !== '') {
            params.set('usuario', selectUsuario.value);
        }

        // Construir nueva URL
        const queryStr = params.toString();
        const nuevaUrl = window.location.pathname + (queryStr ? '?' + queryStr : '');

        // Solo actualizar si la URL cambio (evitar bucles)
        if (window.location.search !== (queryStr ? '?' + queryStr : '')) {
            window.location.href = nuevaUrl;
        }
    }
});
