/**
 * Mobadir main.js — Theme toggle, flash auto-dismiss
 */

// ─── Theme Toggle ───────────────────────────────────────────
(function () {
    const btn        = document.getElementById('themeToggleBtn');
    const iconLight  = document.getElementById('themeIconLight');
    const iconDark   = document.getElementById('themeIconDark');
    const KEY        = 'mobadir_theme';

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(KEY, theme);
        if (iconLight && iconDark) {
            iconLight.style.display = theme === 'dark' ? 'none' : '';
            iconDark.style.display  = theme === 'dark' ? ''     : 'none';
        }
    }

    // Init icons based on current theme
    const current = localStorage.getItem(KEY) ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    applyTheme(current);

    if (btn) {
        btn.addEventListener('click', function () {
            const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        });
    }
})();

// ─── Flash Auto-Dismiss ────────────────────────────────────
(function () {
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (el) {
            el.style.transition = 'opacity .4s ease';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 450);
        });
    }, 5000);
})();

// ─── Image upload preview (delegated) ─────────────────────
document.addEventListener('change', function (e) {
    if (e.target.type !== 'file') return;
    const input   = e.target;
    const previewId = input.dataset.preview;
    if (!previewId) return;
    const img = document.getElementById(previewId);
    if (!img) return;
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (ev) {
        img.src = ev.target.result;
        img.style.display = 'block';
    };
    reader.readAsDataURL(file);
});

// ─── Algeria Wilayas & Communes Dynamic Cascading Selector ─────────────────
window.updateCommunes = function(wilayaSelectId, communeSelectId, selectedCommune) {
    const wilayaEl = document.getElementById(wilayaSelectId);
    const communeEl = document.getElementById(communeSelectId);
    if (!wilayaEl || !communeEl || !window.ALGERIA_COMMUNES) return;

    const wilaya = wilayaEl.value;
    const communes = window.ALGERIA_COMMUNES[wilaya] || ['الوسط'];

    communeEl.innerHTML = '';
    communes.forEach(function(c) {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        if (selectedCommune && selectedCommune === c) {
            opt.selected = true;
        }
        communeEl.appendChild(opt);
    });
};
