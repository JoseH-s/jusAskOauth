import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', function () {
    // ── Dark mode toggle ──────────────────────────────────
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        const btn = document.getElementById('btn-theme-toggle');
        if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
    }

    const savedTheme = localStorage.getItem('jus-theme') || 'light';
    applyTheme(savedTheme);

    $(document).on('click', '#btn-theme-toggle', function () {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        localStorage.setItem('jus-theme', next);
    });
});
