/**
 * HISTORIAL ACTIVO - BUSQUEDA EN TIEMPO REAL
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

    // BUSQUEDA - CLIENT-SIDE FILTERING (IDENTICO A LISTADO_ACTIVOS)
    const searchInput = document.getElementById('searchInputHistorial');
    // Capture the original rows when loading (as in active-listing)
    const tableRows = document.querySelectorAll('.table-body .table-row');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            tableRows.forEach(row => {
                // No procesar el estado vacío original de la BD si existiera
                if (row.classList.contains('empty-state') && !row.classList.contains('search-empty-state')) return;

                const cells = row.querySelectorAll('.table-cell');
                let found = false;
                
                cells.forEach(cell => {
                    const text = cell.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        found = true;
                    }
                });
                
                if (found || searchTerm === '') {
                    // Usar setProperty con important para sobreescribir el CSS del historial
                    row.style.setProperty('display', 'grid', 'important');
                    row.style.opacity = '1';
                } else {
                    // Forzar ocultamiento absoluto para que la tabla se redimensione
                    row.style.setProperty('display', 'none', 'important');
                    row.style.opacity = '0';
                }
            });
            
            // Mostrar estado vacío si no hay resultados (como en listado-activos)
            updateEmptyStateHistorial(searchTerm);
        });

        // Limpiar al presionar Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.dispatchEvent(new Event('input'));
            }
        });

        // Evitar recarga al presionar Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    }

    /**
     * Actualiza el estado vacío (Copiado de listado-activos.js)
     */
    function updateEmptyStateHistorial(searchTerm) {
        const tableBody = document.querySelector('.table-body');
        if (!tableBody) return;

        const allRows = tableBody.querySelectorAll('.table-row');
        
        // Contar realmente filas visibles
        let visibleCount = 0;
        allRows.forEach(row => {
            const computedStyle = window.getComputedStyle(row);
            if (computedStyle.display !== 'none' && !row.classList.contains('search-empty-state')) {
                visibleCount++;
            }
        });
        
        const existingEmpty = tableBody.querySelector('.search-empty-state');
        
        if (visibleCount === 0 && searchTerm !== '') {
            if (!existingEmpty) {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state search-empty-state';
                emptyState.innerHTML = `
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <p>No se encontraron registros con "${searchTerm}"</p>
                `;
                tableBody.appendChild(emptyState);
            }
        } else if (existingEmpty) {
            existingEmpty.remove();
        }
    }

    // APLICAR FILTROS AUTOMATICAMENTE AL CAMBIAR SELECTS
    const selectAccion = document.querySelector('select[name="accion"]');
    const selectEstado = document.querySelector('select[name="estado"]');
    const selectUsuario = document.querySelector('select[name="usuario"]');

    if (selectAccion) selectAccion.addEventListener('change', aplicarFiltros);
    if (selectEstado) selectEstado.addEventListener('change', aplicarFiltros);
    if (selectUsuario) selectUsuario.addEventListener('change', aplicarFiltros);

    // BOTON LIMPIAR FILTROS
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
