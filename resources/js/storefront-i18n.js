/**
 * Storefront bilingual helper — mirrors Next.js L(locale, id, en).
 * Uses shared `evomi_locale` from admin-locale / LanguageSwitcher.
 */
import { readAdminLocale } from './admin-locale';

export function currentLocale() {
    return readAdminLocale() === 'en' ? 'en' : 'id';
}

export function L(id, en) {
    return currentLocale() === 'en' ? en : id;
}

export function registerStorefrontI18n(Alpine) {
    Alpine.magic('L', () => (id, en) => L(id, en));

    Alpine.store('i18n', {
        locale: currentLocale(),
        L(id, en) {
            return this.locale === 'en' ? en : id;
        },
        sync(next) {
            this.locale = next === 'en' ? 'en' : 'id';
        },
    });

    window.addEventListener('evomi-admin-locale', (e) => {
        Alpine.store('i18n').sync(e.detail || currentLocale());
    });
    window.addEventListener('locale-change', () => {
        Alpine.store('i18n').sync(currentLocale());
    });
}
