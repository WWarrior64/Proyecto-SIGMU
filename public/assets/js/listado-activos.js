/**
 * JavaScript for SIGMU Asset Management Dashboard
 * Provides interactive functionality for the asset listing interface
 */

// Global state for active filters
// ✅ ✅ ✅ COMPORTAMIENTO EXACTAMENTE COMO PEDIDO:
// 🔹 AL ABRIR LA PAGINA: TODOS LOS FILTROS SIN MARCAR
// 🔹 CUANDO NINGUNO ESTA MARCADO: MOSTRAR TODOS MENOS DESCARTADOS
// 🔹 CUANDO MARCAS ALGUNO: MOSTRAR SOLO LOS MARCADOS
// 🔹 SI MARCAS SOLO DESCARTADO: VER SOLO LOS DESCARTADOS
// 🔹 SI MARCAS TODOS LOS 4: VER ABSOLUTAMENTE TODOS
let activeStatusFilters = [];

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initSearch();
    initFilter();
    initMenu();
    initAnimations();
    initAlertsAutoHide();
    
    // ✅ Aplicar filtro por defecto al cargar
    filterByStatus(activeStatusFilters);
});

/**
 * Search functionality
 */
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('.table-body .table-row');
    
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.table-cell');
            let found = false;
            
            cells.forEach(cell => {
                const text = cell.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    found = true;
                }
            });
            
            if (found || searchTerm === '') {
                row.style.display = '';
                row.style.opacity = '1';
            } else {
                row.style.display = 'none';
                row.style.opacity = '0';
            }
        });
        
        // Show empty state if no results
        updateEmptyState(searchTerm);
    });
    
    // Clear search on escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });
}

/**
 * Update empty state based on search results
 */
function updateEmptyState(searchTerm) {
    const tableBody = document.querySelector('.table-body');
    const allRows = tableBody.querySelectorAll('.table-row');
    
    // ✅ FORMA CORRECTA: Contar realmente filas visibles (no display: none)
    let visibleCount = 0;
    allRows.forEach(row => {
        const computedStyle = window.getComputedStyle(row);
        if (computedStyle.display !== 'none') {
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
                <p>No se encontraron activos con "${searchTerm}"</p>
            `;
            tableBody.appendChild(emptyState);
        }
    } else if (existingEmpty) {
        existingEmpty.remove();
    }
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

        // Posicionamiento
        const rect = filterBtn.getBoundingClientRect();
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
