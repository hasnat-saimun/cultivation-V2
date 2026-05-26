const root = document.documentElement;

function setTheme(theme) {
    root.setAttribute("data-theme", theme);
    localStorage.setItem("admin-modern-theme", theme);
}

function initTheme() {
    const stored = localStorage.getItem("admin-modern-theme");
    if (stored === "dark" || stored === "light") {
        setTheme(stored);
        return;
    }

    const prefersDark = window.matchMedia(
        "(prefers-color-scheme: dark)",
    ).matches;
    setTheme(prefersDark ? "dark" : "light");
}

function initSidebarToggle() {
    const sidebar = document.getElementById("adminModernSidebar");
    const trigger = document.querySelector("[data-am-sidebar-toggle]");

    if (!sidebar || !trigger) {
        return;
    }

    trigger.addEventListener("click", () => {
        sidebar.classList.toggle("is-open");
    });
}

function initThemeToggle() {
    const trigger = document.querySelector("[data-am-theme-toggle]");

    if (!trigger) {
        return;
    }

    trigger.addEventListener("click", () => {
        const current =
            root.getAttribute("data-theme") === "dark" ? "dark" : "light";
        setTheme(current === "dark" ? "light" : "dark");
    });
}

function initFlashClose() {
    document.querySelectorAll("[data-am-flash-close]").forEach((button) => {
        button.addEventListener("click", () => {
            const alert = button.closest("[data-am-flash]");
            if (alert) {
                alert.remove();
            }
        });
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initTheme();
    initSidebarToggle();
    initThemeToggle();
    initFlashClose();
});
