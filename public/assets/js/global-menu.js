/**
 * Menú lateral global SIGMU — estilos en /assets/css/sigmu-layout.css
 * Navegación según rol; variante visual "admin" si el body tiene .sigmu-shell--admin
 */
(function () {
    "use strict";

    const svg = {
        home: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
        user: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
        building: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"></path></svg>',
        clock: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
        file: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
        wrench: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
        list: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
        plus: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
        key: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>',
    };

    function link(href, label, iconHtml, activePath) {
        const path = window.location.pathname.replace(/\/+$/, "") || "/";
        const normalized = (activePath || href).replace(/\/+$/, "") || "/";
        const active = path === normalized ? " sigmu-sidebar__link--active" : "";
        return (
            '<a href="' +
            href +
            '" class="sigmu-sidebar__link' +
            active +
            '">' +
            iconHtml +
            "<span>" +
            label +
            "</span></a>"
        );
    }

    function buildNavHtml() {
        const u = globalThis.authUser || {};
        const role = u.rol_nombre || "";
        const isAdmin = role === "Administrador";
        const isMantenimiento = role === "Personal Mantenimiento";

        let html = "";
        html += link("/sigmu", "Inicio", svg.home, "/sigmu");
        html += link("/sigmu/perfil", "Mi información", svg.user, "/sigmu/perfil");
        html += link("/sigmu/edificios", "Edificios y salas", svg.building, "/sigmu/edificios");
        html += link("/sigmu/historial", "Historial general", svg.clock, "/sigmu/historial");
        html += link("/sigmu/mantenimiento", "Panel mantenimiento", svg.wrench, "/sigmu/mantenimiento");
        html += link("/sigmu/mantenimiento/listado", "Lista de reparaciones", svg.list, "/sigmu/mantenimiento/listado");

        if (isMantenimiento) {
            html += link("/sigmu/mantenimiento/reportar", "Registrar falla (técnicos)", svg.plus, "/sigmu/mantenimiento/reportar");
        }

        if (isAdmin) {
            html += '<div class="sigmu-sidebar__group-label">Administración</div>';
            html += link("/sigmu/admin/usuarios", "Gestión de usuarios", svg.key, "/sigmu/admin/usuarios");
        }

        return html;
    }

    globalThis.openSidebarMenu = function openSidebarMenu(evt) {
        if (evt && typeof evt.stopPropagation === "function") {
            evt.stopPropagation();
        }

        const existing = document.querySelector(".sigmu-sidebar");
        if (existing) {
            existing.remove();
            return;
        }

        const isAdminShell = document.body.classList.contains("sigmu-shell--admin");

        const sidebar = document.createElement("div");
        sidebar.className = "sigmu-sidebar" + (isAdminShell ? " sigmu-sidebar--admin" : "");
        sidebar.setAttribute("role", "dialog");
        sidebar.setAttribute("aria-modal", "true");
        sidebar.setAttribute("aria-label", "Menú de navegación");

        const name =
            (globalThis.authUser && globalThis.authUser.nombre_completo) || "Usuario";
        let avatarInner =
            '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
        if (globalThis.authUser && globalThis.authUser.foto) {
            avatarInner =
                '<img src="' +
                String(globalThis.authUser.foto).replace(/"/g, "") +
                '" alt="">';
        }

        sidebar.innerHTML =
            '<div class="sigmu-sidebar__brand">' +
            '<div class="sigmu-sidebar__avatar" id="sigmuSidebarAvatar">' +
            avatarInner +
            "</div>" +
            '<div class="sigmu-sidebar__name" id="sigmuSidebarName">' +
            name +
            "</div>" +
            "</div>" +
            '<div class="sigmu-sidebar__toolbar">' +
            "<h3>Menú</h3>" +
            '<button type="button" class="sigmu-sidebar__close" aria-label="Cerrar menú">×</button>' +
            "</div>" +
            '<nav class="sigmu-sidebar__nav">' +
            buildNavHtml() +
            "</nav>";

        document.body.appendChild(sidebar);

        const closeBtn = sidebar.querySelector(".sigmu-sidebar__close");
        if (closeBtn) {
            closeBtn.addEventListener("click", () => sidebar.remove());
        }

        setTimeout(() => {
            function onDocClick(e) {
                const menuBtn = document.getElementById("menuBtn");
                if (sidebar.contains(e.target)) {
                    return;
                }
                if (menuBtn && menuBtn.contains(e.target)) {
                    return;
                }
                sidebar.remove();
                document.removeEventListener("click", onDocClick);
            }
            document.addEventListener("click", onDocClick);
        }, 0);
    };

    document.addEventListener("DOMContentLoaded", () => {
        const menuBtn = document.getElementById("menuBtn");
        if (menuBtn && !menuBtn.dataset.sigmuBound) {
            menuBtn.dataset.sigmuBound = "1";
            menuBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                openSidebarMenu(e);
            });
        }
    });

    /**
     * Alterna el formulario de carga de foto de edificio (panel edificios).
     */
    globalThis.toggleUploadForm = function toggleUploadForm(id) {
        const form = document.getElementById("form-upload-" + id);
        if (!form) {
            return;
        }
        form.style.display = form.style.display === "none" || form.style.display === "" ? "block" : "none";
    };
})();
