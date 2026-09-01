/**
 * Blokir klik kanan + shortcut keyboard (view source / DevTools) kecuali
 * email admin yang diizinkan. Lapisan UX saja — bukan proteksi server-side.
 */
export function initSourceGuard(getAuthUser) {
    const cfg = window.EVOMI_SOURCE_GUARD || {};
    if (cfg.enabled === false) {
        return;
    }

    const exemptEmail = String(cfg.exemptEmail || 'admin@evomi.id')
        .trim()
        .toLowerCase();

    function isExempt() {
        const user = typeof getAuthUser === 'function' ? getAuthUser() : null;
        const email = String(user?.email || '')
            .trim()
            .toLowerCase();

        return email !== '' && email === exemptEmail;
    }

    function shouldBlockShortcut(event) {
        const key = String(event.key || '').toLowerCase();
        const code = String(event.code || '');

        if (key === 'f12' || code === 'F12') {
            return true;
        }

        if (key === 'contextmenu') {
            return true;
        }

        const ctrl = event.ctrlKey;
        const meta = event.metaKey;
        const shift = event.shiftKey;
        const alt = event.altKey;
        const mod = ctrl || meta;

        // View source — Windows/Linux: Ctrl+U
        if (mod && !shift && !alt && key === 'u') {
            return true;
        }

        // View source — macOS: Cmd+Option+U
        if (meta && alt && !shift && key === 'u') {
            return true;
        }

        // DevTools / inspect — Ctrl+Shift+I/J/C/K (Chrome, Edge, Firefox)
        if (mod && shift && !alt && ['i', 'j', 'c', 'k'].includes(key)) {
            return true;
        }

        // DevTools — macOS: Cmd+Option+I/J/C
        if (meta && alt && !shift && ['i', 'j', 'c'].includes(key)) {
            return true;
        }

        return false;
    }

    if (initSourceGuard._bound) {
        return;
    }
    initSourceGuard._bound = true;

    document.addEventListener(
        'contextmenu',
        (event) => {
            if (isExempt()) {
                return;
            }
            event.preventDefault();
        },
        { capture: true },
    );

    document.addEventListener(
        'keydown',
        (event) => {
            if (isExempt() || !shouldBlockShortcut(event)) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
        },
        { capture: true },
    );
}
