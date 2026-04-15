/**
 * ✅ HISTORIAL ACTIVO - BUSQUEDA EN TIEMPO REAL
 * 
 * @author SIGMU UNICAES
 */
document.addEventListener('DOMContentLoaded', function() {
    // Menu lateral
    const menuBtn = document.getElementById('menuBtn');
    if (menuBtn) {
        menuBtn.addEventListener('click', function() {
            document.body.classList.toggle('menu-open');
        });
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
            }, 450); // Aumentado un poco para dar tiempo al usuario a escribir
        });
    }

    // ✅ APLICAR FILTROS AUTOMATICAMENTE AL CAMBIAR SELECTS
    const selectAccion = document.querySelector('select[name="accion"]');
    const selectEstado = document.querySelector('select[name="estado"]');

    if (selectAccion) {
        selectAccion.addEventListener('change', aplicarFiltros);
    }

    if (selectEstado) {
        selectEstado.addEventListener('change', aplicarFiltros);
    }

    // ✅ BOTON LIMPIAR FILTROS
    const botonLimpiar = document.getElementById('limpiarFiltrosBtn');
    if (botonLimpiar) {
        botonLimpiar.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Limpiar todos los campos
            searchInput.value = '';
            selectAccion.selectedIndex = 0;
            selectEstado.selectedIndex = 0;

            // Redirigir sin parametros
            const urlParams = new URLSearchParams(window.location.search);
            window.location.href = window.location.pathname + '?id=' + urlParams.get('id');
        });
    }

    /**
     * Aplica los filtros y actualiza la pagina
     */
    function aplicarFiltros() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Mantener siempre el id del activo
        const activoId = urlParams.get('id');
        
        // Construir nueva URL
        let nuevaUrl = window.location.pathname + '?id=' + activoId;

        // Agregar busqueda si existe
        if (searchInput && searchInput.value.trim() !== '') {
            nuevaUrl += '&busqueda=' + encodeURIComponent(searchInput.value.trim());
        }

        // Agregar filtro accion
        if (selectAccion && selectAccion.value !== '') {
            nuevaUrl += '&accion=' + encodeURIComponent(selectAccion.value);
        }

        // Agregar filtro estado
        if (selectEstado && selectEstado.value !== '') {
            nuevaUrl += '&estado=' + encodeURIComponent(selectEstado.value);
        }

        // Solo actualizar si la URL cambio
        if (window.location.href !== nuevaUrl) {
            window.location.href = nuevaUrl;
        }
    }
});