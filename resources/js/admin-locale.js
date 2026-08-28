/**
 * Penyimpanan locale bersama (`evomi_locale`) untuk storefront maupun admin.
 *
 * Dipisah dari `admin-i18n.js` supaya bundle publik tidak ikut menarik kamus
 * terjemahan admin yang besar — file ini sengaja dijaga tetap ringan.
 */

export const STORAGE_KEY = 'evomi_locale';

export function readAdminLocale() {
    try {
        const v = localStorage.getItem(STORAGE_KEY);
        if (v === 'en' || v === 'id') return v;
    } catch {
        /* ignore */
    }
    return 'id';
}

export function writeAdminLocale(locale) {
    const next = locale === 'en' ? 'en' : 'id';
    try {
        localStorage.setItem(STORAGE_KEY, next);
    } catch {
        /* ignore */
    }
    try {
        const secure = typeof location !== 'undefined' && location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `${STORAGE_KEY}=${next}; Path=/; Max-Age=31536000; SameSite=Lax${secure}`;
    } catch {
        /* ignore */
    }
    if (typeof document !== 'undefined') {
        document.documentElement.lang = next;
    }
    return next;
}
