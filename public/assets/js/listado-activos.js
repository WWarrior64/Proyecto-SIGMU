/**
 * JavaScript for SIGMU Asset Management Dashboard
 * Provides interactive functionality for the asset listing interface
 */

// Global state for active filters
let activeStatusFilters = [];

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initSearch();
    initFilter();
    initMenu();
    initAnimations();
    initAlertsAutoHide();
    initSorting();
    initAjaxPagination();
    
    // ✅ Aplicar filtro por defecto al cargar
    filterByStatus(activeStatusFilters);
});

/**
 * Sorting functionality
 */
function initSorting() {
    const sortableHeaders = document.querySelectorAll('.sortable');
    console.log('🔍 Encontrados', sortableHeaders.length, 'encabezados ordenables');
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            
            const sortField = this.getAttribute('data-sort');
            console.log('👉 Click en ordenar por:', sortField);
            
            // Usar URLSearchParams para capturar todos los parámetros GET actuales
            let params = new URLSearchParams(window.location.search);
            
            // Si ya estamos ordenando por este campo, invertir la dirección
            if (params.get('ordenar_por') === sortField) {
                params.set('orden_direccion', params.get('orden_direccion') === 'ASC' ? 'DESC' : 'ASC');
            } else {
                params.set('ordenar_por', sortField);
                params.set('orden_direccion', 'ASC');
            }
            
            // Resetear a página 1 al cambiar ordenamiento
            params.set('pagina', '1');
            
            // Redirigir conservando todos los parámetros (filtros, búsqueda, sala_id, etc.)
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    });
}

/**
 * Search functionality - now uses AJAX for server-side search
 */
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    
    if (!searchInput) return;
    
    // Debounce timer to avoid excessive AJAX calls
    let searchTimer = null;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            buscarActivosAjax(1);
        }, 300);
    });
    
    // Clear search on escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            clearTimeout(searchTimer);
            buscarActivosAjax(1);
        }
    });
}

/**
 * Realiza la búsqueda/paginación de activos vía AJAX
 */
function buscarActivosAjax(pagina) {
    const searchInput = document.getElementById('searchInput');
    const busqueda = searchInput ? searchInput.value.trim() : '';
    const tableBody = document.getElementById('activosTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (!tableBody) return;
    
    // Mostrar indicador de carga
    tableBody.innerHTML = '<div class="empty-state"><p>Cargando...</p></div>';
    
    // Construir URL con los parámetros actuales
    const urlParams = new URLSearchParams(window.location.search);
    const params = new URLSearchParams();
    
    params.set('pagina', String(pagina));
    
    if (busqueda) {
        params.set('busqueda', busqueda);
    }
    
    // Mantener filtros de estado y tipo
    const estados = urlParams.getAll('estados[]');
    estados.forEach(e => params.append('estados[]', e));
    
    const tipos = urlParams.getAll('tipos[]');
    tipos.forEach(t => params.append('tipos[]', t));
    
    // Mantener orden
    if (urlParams.get('sala_id')) {
        params.set('sala_id', urlParams.get('sala_id'));
    }
    if (urlParams.get('ordenar_por')) {
        params.set('ordenar_por', urlParams.get('ordenar_por'));
    }
    if (urlParams.get('orden_direccion')) {
        params.set('orden_direccion', urlParams.get('orden_direccion'));
    }
    
    fetch('/sigmu/sala/ajax?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.htmlRows) {
                    tableBody.innerHTML = data.htmlRows;
                }
                if (data.htmlPagination && paginationContainer) {
                    paginationContainer.innerHTML = data.htmlPagination;
                }
                
                // Re-inicializar las animaciones para las nuevas filas
                initAnimations();
                
                // Re-inicializar el menú de acciones (delete-modal)
                if (window.SIGMU) {
                    // Disparar evento para que delete-modal se reinicialice
                    document.dispatchEvent(new CustomEvent('ajax-content-loaded'));
                }
            } else {
                tableBody.innerHTML = '<div class="empty-state"><p>Error al cargar datos</p></div>';
            }
        })
        .catch(err => {
            console.error('Error en búsqueda AJAX:', err);
            tableBody.innerHTML = '<div class="empty-state"><p>Error de conexión</p></div>';
        });
}

/**
 * Inicializa la paginación AJAX
 * Intercepta clics en los botones de paginación con clase .ajax-page
 */
function initAjaxPagination() {
    document.addEventListener('click', function(e) {
        const target = e.target.closest('.ajax-page');
        if (!target) return;
        
        e.preventDefault();
        
        const pagina = target.getAttribute('data-pagina');
        if (pagina) {
            buscarActivosAjax(parseInt(pagina));
        }
    });
}

/**
 * Filter functionality: Uses server-provided data and redirects for persistence
 */
function initFilter() {
    const filterBtn = document.getElementById('filterBtn');
    if (!filterBtn) return;

    filterBtn.addEventListener('click', function() {
        let dropdown = document.querySelector('.filter-dropdown');
        if (dropdown) {
            dropdown.remove();
            return;
        }

        dropdown = document.createElement('div');
        dropdown.className = 'filter-dropdown';

        // Obtener datos desde las variables globales inyectadas por PHP
        const data = window.SIGMU_DATA || { tiposDisponibles: [], estadosSeleccionados: [], tiposSeleccionados: [] };
        
        const isEstadoChecked = (val) => data.estadosSeleccionados.includes(val);
        const isTipoChecked = (val) => data.tiposSeleccionados.includes(parseInt(val));

        // Construir HTML de tipos de activo usando los datos del servidor
        let tiposHtml = '';
        data.tiposDisponibles.forEach(tipo => {
            tiposHtml += `
                <label class="filter-option">
                    <input type="checkbox" value="${tipo.id}" class="tipo-checkbox" ${isTipoChecked(tipo.id) ? 'checked' : ''}>
                    <span>${tipo.nombre}</span>
                </label>
            `;
        });

        dropdown.innerHTML = `
            <div class="filter-columns">
                <div class="filter-column">
                    <h5 style="margin: 8px 0 12px 0; color: #424242; font-size: 14px;">Estado</h5>
                    <label class="filter-option">
                        <input type="checkbox" value="disponible" ${isEstadoChecked('disponible') ? 'checked' : ''} class="estado-checkbox">
                        <span class="status-badge status-disponible">Disponible</span>
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" value="en_uso" ${isEstadoChecked('en_uso') ? 'checked' : ''} class="estado-checkbox">
                        <span class="status-badge status-en_uso">En uso</span>
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" value="reparacion" ${isEstadoChecked('reparacion') ? 'checked' : ''} class="estado-checkbox">
                        <span class="status-badge status-reparacion">Reparación</span>
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" value="descartado" ${isEstadoChecked('descartado') ? 'checked' : ''} class="estado-checkbox">
                        <span class="status-badge status-descartado">Descartado</span>
                    </label>
                </div>
                
                <div class="filter-column">
                    <h5 style="margin: 8px 0 12px 0; color: #424242; font-size: 14px;">Tipo de Activo</h5>
                    <div id="tipos-container" style="max-height: 200px; overflow-y: auto;">
                        ${tiposHtml || '<label class="filter-option"><span style="color:#999;">No hay tipos</span></label>'}
                    </div>
                </div>
            </div>
            <div class="filter-actions">
                <button class="filter-apply">Aplicar</button>
                <button class="filter-clear">Limpiar</button>
            </div>
        `;

        // Agregar clase para CSS responsive
        dropdown.className = 'filter-dropdown';
        
        // Posicionamiento
        const rect = filterBtn.getBoundingClientRect();
        const windowWidth = window.innerWidth;
        const isMobile = windowWidth <= 768;
        
        if (isMobile) {
            dropdown.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                border: 1px solid #E0E0E0;
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                z-index: 1000;
                width: calc(100% - 24px);
                max-width: 420px;
                max-height: 80vh;
                overflow-y: auto;
            `;
        } else {
            dropdown.style.cssText = `
                position: absolute;
                top: ${rect.bottom + 8}px;
                right: ${window.innerWidth - rect.right}px;
                background: white;
                border: 1px solid #E0E0E0;
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 4px 16px rgba(0,0,0,0.12);
                z-index: 1000;
                min-width: 450px;
            `;
        }

        document.body.appendChild(dropdown);

        // Estilos internos
        if (!document.getElementById('filter-dropdown-styles')) {
            const style = document.createElement('style');
            style.id = 'filter-dropdown-styles';
            style.textContent = `
                .filter-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 16px; }
                .filter-column { display: flex; flex-direction: column; }
                .filter-option { display: flex; align-items: center; gap: 8px; padding: 6px 0; cursor: pointer; }
                .filter-option input { margin: 0; }
                .filter-actions { display: flex; gap: 8px; justify-content: flex-end; padding-top: 12px; border-top: 1px solid #E0E0E0; }
                .filter-apply, .filter-clear {
                    padding: 8px 16px; border: 1px solid #E0E0E0; border-radius: 8px; background: white;
                    cursor: pointer; font-size: 12px; font-weight: 500;
                }
                .filter-apply { background: #9C1C1C; color: white; border-color: #9C1C1C; }
                .filter-apply:hover { background: #B71C1C; }
                .filter-clear:hover { background: #F5F5F5; }
            `;
            document.head.appendChild(style);
        }

        // APLICAR FILTROS (REDIECCIÓN)
        const applyBtn = dropdown.querySelector('.filter-apply');
        applyBtn.addEventListener('click', function() {
            const params = new URLSearchParams(window.location.search);
            
            // Limpiar filtros anteriores para reconstruirlos
            params.delete('estados[]');
            params.delete('tipos[]');
            
            // Agregar estados seleccionados
            dropdown.querySelectorAll('.estado-checkbox:checked').forEach(cb => {
                params.append('estados[]', cb.value);
            });
            
            // Agregar tipos seleccionados
            dropdown.querySelectorAll('.tipo-checkbox:checked').forEach(cb => {
                params.append('tipos[]', cb.value);
            });
            
            // Resetear a página 1 al filtrar
            params.set('pagina', '1');
            
            window.location.href = window.location.pathname + '?' + params.toString();
        });

        // LIMPIAR FILTROS
        const clearBtn = dropdown.querySelector('.filter-clear');
        clearBtn.addEventListener('click', function() {
            const params = new URLSearchParams(window.location.search);
            params.delete('estados[]');
            params.delete('tipos[]');
            params.set('pagina', '1');
            window.location.href = window.location.pathname + '?' + params.toString();
        });

        // Cerrar al clic fuera
        setTimeout(() => {
            const closeDropdown = (e) => {
                if (!dropdown.contains(e.target) && e.target !== filterBtn) {
                    dropdown.remove();
                    document.removeEventListener('click', closeDropdown);
                }
            };
            document.addEventListener('click', closeDropdown);
        }, 100);
    });
}

/**
 * Filter table rows (DEPRECATED: Now handled by server)
 */
function filterByStatus(filters) {
    // Esta función ya no es necesaria pues el filtrado es por servidor
    console.log('Filtros aplicados desde el servidor.');
}

/**
 * Menu functionality
 */
function initMenu() {
    // Funcionalidad de menu ya integrada en el layout
}

/**
 * Initialize animations
 */
function initAnimations() {
    // Animate table rows on load
    const tableRows = document.querySelectorAll('.table-row');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 50);
    });
}

/**
 * Utility function to show notifications
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 80px;
            right: 24px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            animation: slideInRight 0.3s ease;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .notification-success { background: #28a745; }
        .notification-error { background: #dc3545; }
        .notification-info { background: #1E88E5; }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Auto hide alert messages after 5 seconds with smooth fade animation
 */
function initAlertsAutoHide() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // Set timeout to hide after 5 seconds
        setTimeout(() => {
            // Add fade out animation
            alert.style.transition = 'opacity 0.4s ease, transform 0.3s ease, margin 0.3s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            
            // Remove element after animation completes
            setTimeout(() => {
                alert.style.margin = '0';
                alert.style.height = '0';
                alert.style.padding = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 400);
        }, 5000);
    });
}

// Export for global use
window.SIGMU = {
    showNotification,
    filterByStatus
};
