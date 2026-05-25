/**
 * HISTORIAL ACTIVO - BUSQUEDA EN TIEMPO REAL (AJAX)
 * 
 * @author SIGMU UNICAES
 */
document.addEventListener('DOMContentLoaded', function() {
    // Menu lateral
    const menuBtn = document.getElementById('menuBtn');
    if (menuBtn) {
        if (!menuBtn.onclick) {
            menuBtn.addEventListener('click', function() {
                document.body.classList.toggle('menu-open');
            });
        }
    }

    // BUSQUEDA - AJAX con debounce
    const searchInput = document.getElementById('searchInputHistorial');
    
    if (searchInput) {
        let searchTimer = null;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                buscarHistorialAjax(1);
            }, 300);
        });

        // Limpiar al presionar Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                clearTimeout(searchTimer);
                buscarHistorialAjax(1);
            }
        });

        // Evitar recarga al presionar Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    }

    // APLICAR FILTROS AUTOMATICAMENTE AL CAMBIAR SELECTS (AJAX)
    const selectAccion = document.querySelector('select[name="accion"]');
    const selectEstado = document.querySelector('select[name="estado"]');
    const selectUsuario = document.querySelector('select[name="usuario"]');

    if (selectAccion) selectAccion.addEventListener('change', function() { buscarHistorialAjax(1); });
    if (selectEstado) selectEstado.addEventListener('change', function() { buscarHistorialAjax(1); });
    if (selectUsuario) selectUsuario.addEventListener('change', function() { buscarHistorialAjax(1); });

    // BOTON LIMPIAR FILTROS (AJAX)
    const botonLimpiar = document.getElementById('limpiarFiltrosBtn');
    if (botonLimpiar) {
        botonLimpiar.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Limpiar inputs
            if (searchInput) searchInput.value = '';
            if (selectAccion) selectAccion.selectedIndex = 0;
            if (selectEstado) selectEstado.selectedIndex = 0;
            if (selectUsuario) selectUsuario.selectedIndex = 0;

            // Recargar via AJAX sin filtros
            buscarHistorialAjax(1);
        });
    }

    // PAGINACION AJAX (para clase .ajax-page-historial)
    document.addEventListener('click', function(e) {
        const target = e.target.closest('.ajax-page-historial');
        if (!target) return;
        
        e.preventDefault();
        
        const pagina = target.getAttribute('data-pagina');
        if (pagina) {
            buscarHistorialAjax(parseInt(pagina));
        }
    });
});

/**
 * Realiza la búsqueda/paginación del historial vía AJAX
 */
function buscarHistorialAjax(pagina) {
    const searchInput = document.getElementById('searchInputHistorial');
    const busqueda = searchInput ? searchInput.value.trim() : '';
    const tableBody = document.getElementById('historialTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (!tableBody) return;
    
    // Mostrar indicador de carga
    tableBody.innerHTML = '<div class="empty-state"><p>Cargando...</p></div>';
    
    // Construir parámetros
    const urlParams = new URLSearchParams(window.location.search);
    const params = new URLSearchParams();
    
    params.set('pagina', String(pagina));
    
    // ID del activo (para historial individual)
    const activoId = urlParams.get('id');
    if (activoId) {
        params.set('id', activoId);
    }
    
    // Búsqueda
    if (busqueda) {
        params.set('busqueda', busqueda);
    }
    
    // Filtros de selects
    const selectAccion = document.querySelector('select[name="accion"]');
    const selectEstado = document.querySelector('select[name="estado"]');
    const selectUsuario = document.querySelector('select[name="usuario"]');
    
    if (selectAccion && selectAccion.value !== '') {
        params.set('accion', selectAccion.value);
    }
    if (selectEstado && selectEstado.value !== '') {
        params.set('estado', selectEstado.value);
    }
    if (selectUsuario && selectUsuario.value !== '') {
        params.set('usuario', selectUsuario.value);
    }
    
    // Orden (desde la URL actual)
    if (urlParams.get('ordenar_por')) {
        params.set('ordenar_por', urlParams.get('ordenar_por'));
    }
    if (urlParams.get('orden_direccion')) {
        params.set('orden_direccion', urlParams.get('orden_direccion'));
    }
    
    // Determinar URL del endpoint
    let endpoint = '/sigmu/historial/ajax';
    if (activoId) {
        endpoint = '/sigmu/activo/historial/ajax';
    }
    
    fetch(endpoint + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.htmlRows) {
                    tableBody.innerHTML = data.htmlRows;
                }
                if (data.htmlPagination && paginationContainer) {
                    paginationContainer.innerHTML = data.htmlPagination;
                }
            } else {
                tableBody.innerHTML = '<div class="empty-state"><p>Error al cargar datos</p></div>';
            }
        })
        .catch(err => {
            console.error('Error en búsqueda AJAX de historial:', err);
            tableBody.innerHTML = '<div class="empty-state"><p>Error de conexión</p></div>';
        });
}
