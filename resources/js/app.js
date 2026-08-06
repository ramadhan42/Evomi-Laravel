import Alpine from 'alpinejs';
import { registerAdminCrud } from './admin-crud';
import {
    createAdminI18nApi,
    readAdminLocale,
    writeAdminLocale,
} from './admin-i18n';
import { L as storefrontL, currentLocale, registerStorefrontI18n } from './storefront-i18n';

window.Alpine = Alpine;
registerStorefrontI18n(Alpine);

let softNavBusy = false;
let localeRevealTimer = 0;

function beginLocaleSwitchFx() {
    const root = document.documentElement;
    root.dataset.localeLoading = 'true';
    root.removeAttribute('data-locale-reveal');
}

function finishLocaleSwitchFx() {
    const root = document.documentElement;
    root.removeAttribute('data-locale-loading');
    root.dataset.localeReveal = 'true';
    if (localeRevealTimer) window.clearTimeout(localeRevealTimer);
    localeRevealTimer = window.setTimeout(() => {
        root.removeAttribute('data-locale-reveal');
    }, 520);
}

function pathKey(url) {
    const u = typeof url === 'string' ? new URL(url, window.location.origin) : url;
    return u.pathname.replace(/\/$/, '') || '/';
}

function isBerandaPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/' || p === '/beranda';
}

function isBelanjaListPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/belanja';
}

function isArtikelPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/artikel' || p.startsWith('/artikel/');
}

function isAuthPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/login' || p === '/register';
}

function isBelanjaDetailPath(pathname) {
    return /^\/belanja\/\d+/.test((pathname || '').replace(/\/$/, '') || '/');
}

function isBlueSurfacePath(pathname) {
    return isBerandaPath(pathname) || isBelanjaListPath(pathname) || isArtikelPath(pathname) || isAuthPath(pathname);
}

const DEFAULT_THEME_BLUE = '#1172BA';

function applyProductTheme(color) {
    const c = color || DEFAULT_THEME_BLUE;
    const header = document.getElementById('evomi-header');
    const spacer = document.getElementById('evomi-header-spacer');
    const chrome = header?.querySelector('.nav-chrome');
    const footerWrap = document.getElementById('evomi-footer-wrap');
    const footer = footerWrap?.querySelector('footer') || document.querySelector('footer');

    document.body?.style.setProperty('--evomi-theme', c);

    if (header) {
        header.style.backgroundColor = c;
        header.style.setProperty('--nav-color', c);
    }
    if (chrome) chrome.style.background = c;
    if (spacer) spacer.style.backgroundColor = c;
    if (footerWrap) footerWrap.style.backgroundColor = c;
    if (footer) {
        footer.style.backgroundColor = c;
        footer.style.setProperty('--footer-accent', c);
    }
}

function restoreProductTheme() {
    applyProductTheme(DEFAULT_THEME_BLUE);
}

/** Paint Evomi blue under soft-nav so fade never flashes white into blue pages */
function setSurfaceForPath(pathname) {
    const blue = isBlueSurfacePath(pathname);
    const auth = isAuthPath(pathname);
    const detail = isBelanjaDetailPath(pathname);

    document.body.classList.toggle('evomi-surface-blue', blue);
    document.body.classList.toggle('evomi-auth-mode', auth);
    document.body.classList.toggle('evomi-detail-seamless', detail);

    const main = document.getElementById('evomi-main');
    const footerWrap = document.getElementById('evomi-footer-wrap');
    if (main) {
        main.classList.toggle('overflow-visible', detail);
        main.classList.toggle('overflow-x-hidden', !detail);
    }
    if (footerWrap) {
        footerWrap.classList.toggle('belanja-detail-footer-seam', detail);
    }

    if (auth) {
        applyProductTheme(DEFAULT_THEME_BLUE);
        document.body.style.backgroundColor = '#2B92DE';
        const site = document.querySelector('.evomi-site');
        if (site) site.style.backgroundColor = '#2B92DE';
        return;
    }

    document.body.style.backgroundColor = '';
    const site = document.querySelector('.evomi-site');
    if (site) site.style.backgroundColor = '';
}

function wait(ms) {
    return new Promise((r) => setTimeout(r, ms));
}

/* ——— Full-page loader (parity with Next.js LoadingScreen) ——— */
const LOADER_MIN_MS = 1200;
const LOADER_MAX_MS = 2400;

function unlockEvomiLoaderScroll() {
    document.documentElement.classList.remove('evomi-loading');
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
}

function initEvomiLoader() {
    const root = document.getElementById('evomi-loader');
    const bar = document.getElementById('evomi-loader-bar');
    if (!root) {
        unlockEvomiLoaderScroll();
        return;
    }

    const startedAt = Date.now();
    let raf = 0;
    let done = false;
    let hideTimer = 0;
    let pollTimer = 0;
    let maxTimer = 0;

    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';

    const setProgress = (pct) => {
        if (bar) bar.style.width = `${Math.max(0, Math.min(100, pct))}%`;
    };

    const finish = () => {
        if (done) return;
        done = true;
        setProgress(100);
        root.classList.add('is-fading');
        unlockEvomiLoaderScroll();
        window.dispatchEvent(new CustomEvent('evomi:loader-done'));
        hideTimer = window.setTimeout(() => {
            root.classList.add('is-hidden');
            root.setAttribute('aria-busy', 'false');
            root.removeAttribute('aria-live');
        }, 500);
    };

    const tick = () => {
        if (done) return;
        const elapsed = Date.now() - startedAt;
        const soft = Math.min(92, 8 + (elapsed / LOADER_MAX_MS) * 84);
        setProgress(soft);
        raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);

    const tryFinish = () => {
        const elapsed = Date.now() - startedAt;
        const ready = document.readyState === 'complete' || elapsed >= LOADER_MAX_MS;
        if (ready && elapsed >= LOADER_MIN_MS) {
            finish();
            return;
        }
        pollTimer = window.setTimeout(tryFinish, 120);
    };

    const onLoad = () => tryFinish();
    if (document.readyState === 'complete') {
        tryFinish();
    } else {
        window.addEventListener('load', onLoad, { once: true });
        maxTimer = window.setTimeout(tryFinish, LOADER_MAX_MS);
    }

    // Safety: if the tab is hidden mid-load, still unlock eventually
    window.addEventListener(
        'pageshow',
        (e) => {
            if (e.persisted && !done) finish();
        },
        { once: true },
    );

    return () => {
        done = true;
        cancelAnimationFrame(raf);
        window.clearTimeout(hideTimer);
        window.clearTimeout(pollTimer);
        window.clearTimeout(maxTimer);
        window.removeEventListener('load', onLoad);
        unlockEvomiLoaderScroll();
    };
}

// Module scripts run after parse — loader markup is already in the DOM
initEvomiLoader();

function isProfilePath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/profile' || p.startsWith('/profile/');
}

function isHistoryDetailPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return /^\/profile\/history\/[^/]+$/.test(p);
}

function isArtikelDetailPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return /^\/artikel\/[^/]+$/.test(p);
}

/** Soft-nav / hard-load routes that should feel instant (no leave fade / full loader). */
function shouldSkipPageLoadingFeel(pathname) {
    return isArtikelDetailPath(pathname) || isHistoryDetailPath(pathname);
}

const HISTORY_GROUPS_CACHE_KEY = 'evomi_history_groups_v1';

function cacheHistoryGroups(groups) {
    try {
        sessionStorage.setItem(HISTORY_GROUPS_CACHE_KEY, JSON.stringify(groups || []));
    } catch {
        /* ignore quota / private mode */
    }
}

function findCachedHistoryGroup(orderId) {
    try {
        const raw = sessionStorage.getItem(HISTORY_GROUPS_CACHE_KEY);
        if (!raw) return null;
        const groups = JSON.parse(raw);
        if (!Array.isArray(groups)) return null;
        const id = String(orderId);
        return (
            groups.find((g) => String(g.groupId) === id) ||
            groups.find((g) => (g.items || []).some((i) => String(i.id) === id)) ||
            null
        );
    } catch {
        return null;
    }
}

function isDashboardPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/dashboard' || p.startsWith('/dashboard/');
}

function getAuthToken() {
    try {
        return localStorage.getItem('auth_token') || '';
    } catch {
        return '';
    }
}

function getAuthUser() {
    try {
        const raw = localStorage.getItem('auth_user') || localStorage.getItem('user');
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function setAuthSession(token, user) {
    localStorage.setItem('auth_token', token);
    localStorage.setItem('auth_user', JSON.stringify(user));
    if (user?.is_admin) {
        localStorage.setItem('user', JSON.stringify(user));
    } else {
        localStorage.removeItem('user');
    }
    window.dispatchEvent(new Event('auth-change'));
}

function clearAuthSession() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    localStorage.removeItem('user');
    window.dispatchEvent(new Event('auth-change'));
}

function authHeaders(json = true) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (json) headers['Content-Type'] = 'application/json';
    const token = getAuthToken();
    if (token) headers.Authorization = `Bearer ${token}`;
    return headers;
}

async function readApiJson(res) {
    const text = await res.text();
    if (!text) return {};
    try {
        return JSON.parse(text);
    } catch {
        return {};
    }
}

function apiErrorMessage(data, fallback = 'Terjadi kesalahan.') {
    if (!data || typeof data !== 'object') return fallback;
    if (data.errors && typeof data.errors === 'object') {
        const flat = Object.values(data.errors).flat().filter(Boolean);
        if (flat.length) return flat.join(' ');
    }
    if (typeof data.message === 'string' && data.message) return data.message;
    if (typeof data.email === 'string') return data.email;
    return fallback;
}

function orderGrandTotal(order) {
    if (order?.grand_total != null && order.grand_total !== '') {
        const g = Number(order.grand_total);
        if (Number.isFinite(g)) return Math.max(0, g);
    }
    const product = Number(order?.total_price || 0) || 0;
    const shipping = Number(order?.shipping_cost ?? order?.ongkir_price ?? 0) || 0;
    const promo = Number(order?.promo_discount || 0) || 0;
    return Math.max(0, product + shipping - promo);
}

function normalizePaymentStatus(value) {
    const v = String(value || '')
        .toLowerCase()
        .trim();
    if (v === 'success' || v === 'paid' || v === 'settlement') return 'success';
    if (v === 'cancelled' || v === 'canceled' || v === 'failed' || v === 'expire' || v === 'expired') {
        return 'cancelled';
    }
    return 'pending';
}

function isSuccessfulPayment(value) {
    return normalizePaymentStatus(value) === 'success';
}

function formatRupiah(value) {
    const numberValue = Number(value) || 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(numberValue);
}

function fulfillmentStatusConfig(status) {
    const normalizedStatus = String(status || '').toLowerCase();
    switch (normalizedStatus) {
        case 'menunggu_konfirmasi':
            return {
                label: storefrontL('Menunggu Konfirmasi', 'Awaiting Confirmation'),
                class: 'bg-orange-50 text-orange-600 border-orange-200',
                dot: 'bg-orange-500',
            };
        case 'pengemasan':
            return {
                label: storefrontL('Dikemas', 'Packing'),
                class: 'bg-purple-50 text-purple-600 border-purple-200',
                dot: 'bg-purple-500',
            };
        case 'dalam_perjalanan':
            return {
                label: storefrontL('Dalam Perjalanan', 'In Transit'),
                class: 'bg-gray-100 text-gray-700 border-gray-300',
                dot: 'bg-gray-500',
            };
        case 'diterima':
            return {
                label: storefrontL('Diterima', 'Received'),
                class: 'bg-emerald-50 text-emerald-600 border-emerald-200',
                dot: 'bg-emerald-500',
            };
        case 'selesai':
            return {
                label: storefrontL('Selesai', 'Completed'),
                class: 'bg-emerald-50 text-emerald-600 border-emerald-200',
                dot: 'bg-emerald-500',
            };
        default:
            return {
                label: status || storefrontL('Diproses', 'Processing'),
                class: 'bg-slate-50 text-slate-700 border-slate-200',
                dot: 'bg-slate-400',
            };
    }
}

function paymentStatusBadgeClass(status) {
    if (status === 'success') return 'bg-emerald-50 text-emerald-700 border-emerald-100';
    if (status === 'cancelled') return 'bg-rose-50 text-rose-700 border-rose-100';
    return 'bg-amber-50 text-amber-700 border-amber-100';
}

function paymentStatusLabel(status) {
    if (status === 'success') return storefrontL('Berhasil', 'Success');
    if (status === 'cancelled') return storefrontL('Dibatalkan', 'Cancelled');
    return storefrontL('Pending', 'Pending');
}

/**
 * Resolve any stored media reference to a browsable URL.
 * Seeded records point at files shipped in /public (e.g. src/images/articles/a.jpg),
 * while uploads live on the public disk and need the /storage prefix.
 */
function mediaUrl(path) {
    if (!path) return '';
    const raw = String(path).trim();
    if (!raw || raw === 'null' || raw === 'undefined') return '';
    if (/^(https?:|blob:|data:)/i.test(raw)) return raw;

    const cleaned = raw.replace(/^\/+/, '');
    if (!cleaned) return '';
    if (/^storage\//i.test(cleaned)) return `/${cleaned}`;
    if (/^(src|images|img|assets|build|fonts)\//i.test(cleaned)) return `/${cleaned}`;

    return `/storage/${cleaned}`;
}

function storageUrl(path) {
    return mediaUrl(path);
}

/** Only return a usable avatar URL; empty/null/"null" → null (show initial letter). */
function resolveAvatarUrl(path, cacheKey = null) {
    if (path == null) return null;
    const raw = String(path).trim();
    if (!raw || raw === 'null' || raw === 'undefined' || raw === '/') return null;
    if (/^(blob:|data:)/i.test(raw)) return raw;
    let url = '';
    if (/^https?:\/\//i.test(raw)) {
        url = raw;
    } else {
        const cleaned = raw.replace(/^\/+/, '');
        if (!cleaned || cleaned === 'storage' || cleaned === 'storage/') return null;
        url = mediaUrl(cleaned) || '';
    }
    if (!url) return null;
    if (cacheKey != null && String(cacheKey).trim() !== '') {
        const sep = url.includes('?') ? '&' : '?';
        return `${url}${sep}v=${encodeURIComponent(String(cacheKey))}`;
    }
    return url;
}

/** Resolve navbar/profile avatar from a user payload (avatar_profile or avatar). */
function avatarUrlFromUser(user) {
    if (!user || typeof user !== 'object') return null;
    const path = user.avatar_profile || user.avatar || null;
    const cacheKey = user.updated_at || user.avatar_updated_at || null;
    return resolveAvatarUrl(path, cacheKey);
}

function syncNavbarAuth() {
    try {
        window.__evomiNav?.readAuth?.();
    } catch {
        /* ignore */
    }
    window.dispatchEvent(new Event('auth-change'));
}

function userDisplayInitial(userOrName, email = '') {
    let name = '';
    if (userOrName && typeof userOrName === 'object') {
        name = userOrName.name || userOrName.nama_lengkap || '';
        email = email || userOrName.email || '';
    } else {
        name = userOrName || '';
    }
    const base = String(name || email || '?').trim();
    return base.charAt(0).toUpperCase() || '?';
}

function formatBadge(n) {
    const num = Number(n) || 0;
    if (num <= 0) return '';
    return num > 99 ? '99+' : String(num);
}

function emitEvomiEvent(name) {
    window.dispatchEvent(new Event(name));
}

function productTitle(product) {
    return product?.title || product?.name || 'Produk';
}

function productPrice(product) {
    return Number(product?.price || product?.harga || 0) || 0;
}

function productAccent(product) {
    if (product?.color) return String(product.color);
    const personality = String(product?.personality_type || '').toLowerCase();
    const map = {
        prestige: '#1172BA',
        purpose_prestige: '#1172BA',
        peaceful_calm: '#5EA14A',
        rebel_brave: '#E33D35',
        sweet_shy: '#DD74A5',
    };
    return map[personality] || DEFAULT_THEME_BLUE;
}

function productImageFallback(product) {
    const personality = String(product?.personality_type || '').toLowerCase();
    const fallbacks = {
        prestige: '/src/images/section%205/purpose-prestige.png',
        purpose_prestige: '/src/images/section%205/purpose-prestige.png',
        peaceful_calm: '/src/images/section%205/peaceful-calm.png',
        rebel_brave: '/src/images/section%205/rabel-brave.png',
        sweet_shy: '/src/images/section%205/sweet-shy.png',
    };
    return fallbacks[personality] || '/src/images/section%205/purpose-prestige.png';
}

function productImage(product, prefer = 'default') {
    let path = '';
    if (prefer === 'wishlist') {
        path = product?.image_2 || product?.image_1 || product?.image || '';
    } else if (prefer === 'cart') {
        path = product?.image_1 || product?.image_2 || product?.image || '';
    } else {
        path =
            product?.image_produk_belanja ||
            product?.image_1 ||
            product?.image ||
            '';
    }
    const url = storageUrl(path);
    return url || productImageFallback(product);
}

function groupOrdersByCreatedAt(orders) {
    const map = new Map();
    for (const order of orders || []) {
        // Prefer grouping by identical created_at timestamp (checkout batch)
        const batchKey = String(order.created_at || order.id);
        if (!map.has(batchKey)) {
            map.set(batchKey, []);
        }
        map.get(batchKey).push(order);
    }

    return Array.from(map.entries())
        .map(([batchKey, items]) => {
            const first = items[0];
            const quantity = items.reduce((s, o) => s + (Number(o.quantity) || 0), 0);
            const subtotal = items.reduce((s, o) => s + (Number(o.total_price) || 0), 0);
            const shipping = items.reduce((s, o) => s + (Number(o.shipping_cost) || 0), 0);
            const promo = items.reduce((s, o) => s + (Number(o.promo_discount) || 0), 0);
            const total = items.reduce((s, o) => s + orderGrandTotal(o), 0);
            const status = first.status;
            const pay = normalizePaymentStatus(first.payment_status);
            const fulfill = fulfillmentStatusConfig(status);
            const extraCount = Math.max(0, items.length - 1);
            const idStr = String(first.id);
            const invoice = /^\d+$/.test(idStr) ? `#INV-${idStr}` : `#${idStr.toUpperCase()}`;

            return {
                groupId: first.id,
                batchKey,
                items: items.map((o) => ({
                    ...o,
                    title: productTitle(o.product),
                    description:
                        o.product?.description ||
                        o.product?.deskripsi ||
                        o.product?.desc ||
                        '',
                    priceLabel: formatRupiah(
                        productPrice(o.product) ||
                            Number(o.total_price) / Math.max(1, Number(o.quantity) || 1),
                    ),
                    lineTotalLabel: formatRupiah(orderGrandTotal(o)),
                    imageUrl: productImage(o.product, 'cart'),
                    accent: productAccent(o.product),
                    canDeleteItem: ['diterima', 'selesai', ''].includes(
                        String(o.status || status || '').toLowerCase(),
                    ),
                })),
                invoice,
                productTitle: productTitle(first.product),
                extraCount,
                imageUrl: productImage(first.product, 'cart'),
                accent: productAccent(first.product),
                quantity,
                dateLabel: new Date(first.created_at).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                }),
                dateTimeLabel: new Date(first.created_at).toLocaleString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                }),
                totalLabel: formatRupiah(total),
                subtotalLabel: formatRupiah(subtotal),
                shippingLabel: formatRupiah(shipping),
                promoLabel: formatRupiah(promo),
                status,
                statusLabel: fulfill.label,
                statusClass: fulfill.class,
                statusDot: fulfill.dot,
                paymentLabel: paymentStatusLabel(pay),
                paymentClass: paymentStatusBadgeClass(pay),
                paymentMethod: first.metode_pembayaran || '',
                canConfirm: String(status).toLowerCase() === 'dalam_perjalanan',
                canDelete: ['diterima', 'selesai'].includes(String(status).toLowerCase()),
            };
        })
        .sort(
            (a, b) =>
                new Date(b.batchKey).getTime() - new Date(a.batchKey).getTime(),
        );
}

async function fetchBadgeCounts() {
    const token = getAuthToken();
    if (!token) {
        return { cart: 0, wishlist: 0, history: 0, unread: 0 };
    }
    try {
        const res = await fetch('/api/badges', {
            headers: authHeaders(true),
            credentials: 'same-origin',
        });
        if (!res.ok) return { cart: 0, wishlist: 0, history: 0, unread: 0 };
        const data = await readApiJson(res);
        return {
            cart: Number(data?.data?.cart || 0) || 0,
            wishlist: Number(data?.data?.wishlist || 0) || 0,
            history: Number(data?.data?.history || 0) || 0,
            unread: Number(data?.data?.unread || 0) || 0,
        };
    } catch {
        return { cart: 0, wishlist: 0, history: 0, unread: 0 };
    }
}

window.evomiAuthApi = {
    getAuthToken,
    getAuthUser,
    setAuthSession,
    clearAuthSession,
    authHeaders,
};

function waitFrames(n = 2) {
    return new Promise((resolve) => {
        const step = () => {
            if (n <= 0) return resolve();
            n -= 1;
            requestAnimationFrame(step);
        };
        step();
    });
}

document.addEventListener('alpine:init', () => {
    registerAdminCrud(Alpine, {
        authHeaders,
        readApiJson,
        apiErrorMessage,
        formatRupiah,
        storageUrl,
        mediaUrl,
        resolveAvatarUrl,
        fulfillmentStatusConfig,
        normalizePaymentStatus,
        paymentStatusLabel,
        paymentStatusBadgeClass,
        orderGrandTotal,
        clearAuthSession,
        getAuthUser,
    });

    Alpine.data('evomiNavbar', (activeIndex = 0) => ({
        open: false,
        isNavHidden: false,
        activeIndex: Number(activeIndex),
        lastScrollY: 0,
        _scrollTicking: false,
        _activeClassTimer: null,
        indicator: { left: 0, width: 0, opacity: 0 },
        isLoggedIn: false,
        isAdmin: false,
        userEmail: null,
        userName: null,
        userAvatar: null,
        accountMenuOpen: false,
        logoutLoading: false,
        logoutConfirmOpen: false,
        badges: { cart: 0, wishlist: 0, history: 0, unread: 0 },
        locale: 'id',
        _localeRevealTimer: null,

        get userInitial() {
            return userDisplayInitial(
                { name: this.userName, email: this.userEmail },
                this.userEmail,
            );
        },

        badgeLabel(key) {
            return formatBadge(this.badges?.[key] || 0);
        },

        badgeDesc(key, filled, empty) {
            return (this.badges?.[key] || 0) > 0 ? filled : empty;
        },

        setLocale(next) {
            const code = next === 'en' ? 'en' : 'id';
            if (this.locale === code) return;
            this.locale = writeAdminLocale(code);
            window.dispatchEvent(new CustomEvent('evomi-admin-locale', { detail: this.locale }));
            window.dispatchEvent(new Event('locale-change'));
            beginLocaleSwitchFx();
            window.setTimeout(() => {
                softNavigate(window.location.href, { push: false, force: true }).finally(() => {
                    finishLocaleSwitchFx();
                });
            }, 380);
        },

        get indicatorStyle() {
            return {
                transform: `translate3d(${this.indicator.left}px, 0, 0)`,
                width: `${Math.max(this.indicator.width, 0)}px`,
                opacity: this.indicator.opacity,
            };
        },

        init() {
            window.__evomiNav = this;
            this.locale = readAdminLocale();
            writeAdminLocale(this.locale);
            this.lastScrollY = window.scrollY || document.documentElement.scrollTop || 0;
            this.readAuth();
            this._onAuthChange = () => this.readAuth();
            this._onBadgeRefresh = () => this.refreshBadges();
            this._onLocaleChange = (e) => {
                this.locale = e?.detail || readAdminLocale();
            };
            window.addEventListener('auth-change', this._onAuthChange);
            window.addEventListener('storage', this._onAuthChange);
            window.addEventListener('cart_updated', this._onBadgeRefresh);
            window.addEventListener('wishlist_updated', this._onBadgeRefresh);
            window.addEventListener('history_updated', this._onBadgeRefresh);
            window.addEventListener('messages_read', this._onBadgeRefresh);
            window.addEventListener('evomi-admin-locale', this._onLocaleChange);

            this.$nextTick(() => {
                this.syncSpacer();
                this.moveIndicator(this.activeIndex, false);
                this.syncActiveClasses(this.activeIndex);
                requestAnimationFrame(() => {
                    this.moveIndicator(this.activeIndex, true);
                    this.syncActiveClasses(this.activeIndex);
                    this.syncSpacer();
                });
                // Fonts/images can change header height after first paint
                window.setTimeout(() => {
                    this.moveIndicator(this.activeIndex, false);
                    this.syncActiveClasses(this.activeIndex);
                    this.syncSpacer();
                }, 120);
            });

            this._onResize = () => {
                this.moveIndicator(this.activeIndex, false);
                this.syncSpacer();
            };
            window.addEventListener('resize', this._onResize);

            if (typeof ResizeObserver !== 'undefined' && this.$el) {
                this._spacerObserver = new ResizeObserver(() => this.syncSpacer());
                this._spacerObserver.observe(this.$el);
            }

            // Hide whole navbar on scroll down, show on scroll up (CSS transform only)
            this._onScroll = () => {
                if (this._scrollTicking) return;
                this._scrollTicking = true;
                requestAnimationFrame(() => {
                    this.updateScrollVisibility();
                    this._scrollTicking = false;
                });
            };
            window.addEventListener('scroll', this._onScroll, { passive: true });

            this.$watch('open', (isOpen) => {
                if (isOpen) this.isNavHidden = false;
                this.$nextTick(() => this.syncSpacer());
            });

            this.$watch('accountMenuOpen', () => {
                this.$nextTick(() => this.syncSpacer());
            });
        },

        readAuth() {
            const token = getAuthToken();
            const user = getAuthUser();

            if (token && user) {
                this.isLoggedIn = true;
                this.userEmail = user.email || null;
                this.userName = user.name || user.nama_lengkap || null;
                this.isAdmin = Boolean(user.is_admin);
                this.userAvatar = avatarUrlFromUser(user);
                this.refreshBadges();
            } else {
                this.isLoggedIn = false;
                this.isAdmin = false;
                this.userEmail = null;
                this.userName = null;
                this.userAvatar = null;
                this.accountMenuOpen = false;
                this.badges = { cart: 0, wishlist: 0, history: 0, unread: 0 };
            }

            this.$nextTick(() => {
                if (typeof bindSoftLinks === 'function') {
                    bindSoftLinks(this.$el);
                }
                this.syncSpacer();
            });
        },

        async refreshBadges() {
            if (!this.isLoggedIn) {
                this.badges = { cart: 0, wishlist: 0, history: 0, unread: 0 };
                return;
            }
            this.badges = await fetchBadgeCounts();
        },

        toggleAccountMenu() {
            this.accountMenuOpen = !this.accountMenuOpen;
        },

        closeAccountMenu() {
            this.accountMenuOpen = false;
        },

        askLogout() {
            this.open = false;
            this.accountMenuOpen = false;
            this.logoutConfirmOpen = true;
        },

        cancelLogout() {
            this.logoutConfirmOpen = false;
        },

        async confirmLogout() {
            if (this.logoutLoading) return;
            this.logoutLoading = true;
            this.logoutConfirmOpen = false;
            try {
                const token = getAuthToken();
                if (token) {
                    await fetch('/api/logout', {
                        method: 'POST',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                }
            } catch {
                /* ignore network errors — still clear local session */
            } finally {
                clearAuthSession();
                this.readAuth();
                this.logoutLoading = false;
                softNavigate('/');
            }
        },

        syncSpacer() {
            const spacer = document.getElementById('evomi-header-spacer');
            if (!spacer || !this.$el) return;

            // getBoundingClientRect stays accurate even with transform (hide-on-scroll)
            const h = Math.ceil(this.$el.getBoundingClientRect().height);
            if (h <= 0) return;

            spacer.style.height = `${h}px`;
            spacer.style.minHeight = `${h}px`;
            document.documentElement.style.setProperty('--evomi-nav-h', `${h}px`);
        },

        updateScrollVisibility() {
            const y = window.scrollY || document.documentElement.scrollTop || 0;
            const prev = this.lastScrollY;
            const delta = y - prev;

            // Keep visible while mobile menu is open
            if (this.open) {
                this.isNavHidden = false;
                this.lastScrollY = y;
                return;
            }

            // Ignore tiny jitter so the slow slide can finish cleanly
            if (Math.abs(delta) < 6) {
                return;
            }

            if (delta > 0 && y > 90) {
                this.isNavHidden = true;
            } else if (delta < 0 || y <= 70) {
                this.isNavHidden = false;
            }

            this.lastScrollY = y;
        },

        setActive(index, animate = true) {
            // Tentang (#about) keeps Beranda pill active — same as Next isActive
            const pillIndex = index === 1 ? 0 : Number(index);
            if (Number.isNaN(pillIndex) || pillIndex < 0) {
                this.clearActive(animate);
                return;
            }

            if (this._activeClassTimer) {
                clearTimeout(this._activeClassTimer);
                this._activeClassTimer = null;
            }

            this.activeIndex = pillIndex;
            // Blue on selected + white on others immediately; shared pill slides under
            this.syncActiveClasses(pillIndex);
            this.moveIndicator(pillIndex, animate);
        },

        clearActive(animate = true) {
            if (this._activeClassTimer) {
                clearTimeout(this._activeClassTimer);
                this._activeClassTimer = null;
            }

            this.activeIndex = -1;
            this.indicator = { ...this.indicator, opacity: 0 };
            this.syncActiveClasses(-1);
        },

        syncActiveClasses(pillIndex) {
            const roots = [this.$refs.track, this.$refs.mobileMenu].filter(Boolean);

            roots.forEach((root) => {
                root.querySelectorAll('[data-nav-index]').forEach((el) => {
                    const i = Number(el.dataset.navIndex);
                    const on = pillIndex >= 0 && i === pillIndex;

                    el.classList.toggle('is-active', on);
                    el.classList.remove(
                        'is-pill-moving',
                        'is-entering-active',
                        'is-leaving-active',
                        'text-[var(--nav-color)]',
                        'text-white',
                    );
                });
            });
        },

        moveIndicator(index, animate = true) {
            const track = this.$refs.track;
            if (!track) return;

            if (index < 0) {
                this.indicator = { ...this.indicator, opacity: 0 };
                return;
            }

            const items = track.querySelectorAll('[data-nav-index]');
            const item = items[index];
            if (!item) {
                this.indicator = { ...this.indicator, opacity: 0 };
                return;
            }

            const trackRect = track.getBoundingClientRect();
            const itemRect = item.getBoundingClientRect();

            if (!animate) track.classList.add('nav-indicator-no-anim');

            this.indicator = {
                left: itemRect.left - trackRect.left,
                width: itemRect.width,
                opacity: 1,
            };

            if (!animate) {
                requestAnimationFrame(() => track.classList.remove('nav-indicator-no-anim'));
            }
        },
    }));

    Alpine.data('evomiProductDetail', (payload = {}) => ({
        id: payload.id,
        title: payload.title || '',
        accent: payload.accent || DEFAULT_THEME_BLUE,
        price: Number(payload.price) || 0,
        stock: Math.max(0, Number(payload.stock) || 0),
        gallery: Array.isArray(payload.gallery) ? payload.gallery : [],
        characterUrl: payload.characterUrl || '',
        kurirs: Array.isArray(payload.kurirs) ? payload.kurirs : [],
        promo: Math.max(0, Number(payload.promo) || 0),
        loginUrl: payload.loginUrl || '/login',
        applyTheme: payload.applyTheme !== false,

        currentIndex: 0,
        quantity: 1,
        selectedKurir: null,
        showKurirList: false,
        showShareModal: false,
        isChatOpen: false,
        isCopied: false,
        isWishlisted: false,
        statusMessage: '',
        statusTone: 'success',
        wishlistMessage: '',
        draft: '',
        chatBubbles: [],
        detailScrollHeight: null,
        alert: { show: false, message: '' },
        chatTemplates: ['Hai, barang ini ready?', 'Bisa dikirim hari ini?', 'Terima kasih'],
        actionBusy: false,
        wishlistBusy: false,
        wishlistId: null,
        _galleryTimer: null,
        _resizeObserver: null,

        get isOutOfStock() {
            return this.stock <= 0;
        },

        get productSubtotal() {
            return Math.max(this.price * this.quantity, 0);
        },

        get shippingCost() {
            return this.selectedKurir ? Number(this.selectedKurir.harga) || 0 : 0;
        },

        get promoDiscount() {
            const gross = this.productSubtotal + this.shippingCost;
            return Math.min(this.promo, gross);
        },

        get totalWithShipping() {
            return Math.max(this.productSubtotal + this.shippingCost - this.promoDiscount, 0);
        },

        get accentSurfaceStyle() {
            return { backgroundColor: this.accent };
        },

        get accentTextStyle() {
            return { color: this.accent };
        },

        get detailScrollStyle() {
            if (!this.detailScrollHeight) return {};
            return {
                height: `${this.detailScrollHeight}px`,
                maxHeight: `${this.detailScrollHeight}px`,
            };
        },

        get productUrl() {
            return typeof window !== 'undefined' ? window.location.href : '';
        },

        get shareLinks() {
            const text = encodeURIComponent(`Cek produk keren ini: ${this.title}`);
            const url = encodeURIComponent(this.productUrl);
            return {
                whatsapp: `https://api.whatsapp.com/send?text=${text}%20${url}`,
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
                twitter: `https://twitter.com/intent/tweet?url=${url}&text=${text}`,
            };
        },

        init() {
            this.selectedKurir = this.kurirs[0] || null;
            if (this.applyTheme) {
                applyProductTheme(this.accent);
            }

            if (this.gallery.length > 1) {
                this._galleryTimer = window.setInterval(() => {
                    this.currentIndex = (this.currentIndex + 1) % this.gallery.length;
                }, 4000);
            }

            this.$nextTick(() => this.syncDetailHeight());

            if (typeof ResizeObserver !== 'undefined') {
                this._resizeObserver = new ResizeObserver(() => this.syncDetailHeight());
                if (this.$refs.diskusiBox) this._resizeObserver.observe(this.$refs.diskusiBox);
                if (this.$refs.jaminanBox) this._resizeObserver.observe(this.$refs.jaminanBox);
            }

            this._onResize = () => this.syncDetailHeight();
            window.addEventListener('resize', this._onResize);

            this._onWishlistSync = () => this.syncWishlistState();
            window.addEventListener('wishlist_updated', this._onWishlistSync);
            window.addEventListener('auth-change', this._onWishlistSync);
            this.syncWishlistState();
        },

        destroy() {
            if (this._galleryTimer) window.clearInterval(this._galleryTimer);
            if (this._resizeObserver) this._resizeObserver.disconnect();
            if (this._onResize) window.removeEventListener('resize', this._onResize);
            if (this._onWishlistSync) {
                window.removeEventListener('wishlist_updated', this._onWishlistSync);
                window.removeEventListener('auth-change', this._onWishlistSync);
            }
        },

        flashStatus(message, tone = 'success', ms = 2200) {
            this.statusMessage = message;
            this.statusTone = tone;
            if (this._statusTimer) window.clearTimeout(this._statusTimer);
            this._statusTimer = window.setTimeout(() => {
                this.statusMessage = '';
            }, ms);
        },

        async syncWishlistState() {
            if (!getAuthToken() || !this.id) {
                this.isWishlisted = false;
                this.wishlistId = null;
                return;
            }
            try {
                const res = await fetch('/api/wishlists?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await readApiJson(res);
                const list = Array.isArray(data) ? data : data.data || [];
                const match = list.find(
                    (item) =>
                        Number(item.product_id) === Number(this.id) ||
                        Number(item.product?.id) === Number(this.id),
                );
                this.isWishlisted = Boolean(match);
                this.wishlistId = match?.id ?? null;
            } catch {
                /* keep current local state */
            }
        },

        syncDetailHeight() {
            if (window.innerWidth < 1024) {
                this.detailScrollHeight = null;
                return;
            }
            const diskusi = this.$refs.diskusiBox;
            const jaminan = this.$refs.jaminanBox;
            if (!diskusi || !jaminan) return;
            const next = Math.round(
                diskusi.getBoundingClientRect().height +
                    jaminan.getBoundingClientRect().height +
                    16,
            );
            this.detailScrollHeight = next > 0 ? next : null;
        },

        formatPrice(value) {
            const n = Number(value) || 0;
            return `Rp${n.toLocaleString('id-ID')}`;
        },

        estimasiTiba(kurir) {
            const date = new Date();
            let days = 3;
            if (kurir && typeof kurir === 'object') {
                const fromDb = Number(kurir.estimasi_hari);
                if (Number.isFinite(fromDb) && fromDb > 0) {
                    days = fromDb;
                } else {
                    const j = String(kurir.jenis || '').toLowerCase();
                    if (
                        j.includes('yes') ||
                        j.includes('express') ||
                        j.includes('sameday') ||
                        j.includes('same day') ||
                        j.includes('halu') ||
                        j.includes('gokil') ||
                        j.includes('ons')
                    ) {
                        days = 1;
                    }
                }
            }
            date.setDate(date.getDate() + days);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
        },

        changeQty(type) {
            if (type === 'dec' && this.quantity > 1) this.quantity -= 1;
            if (type === 'inc' && this.quantity < this.stock) this.quantity += 1;
            this.$nextTick(() => this.syncDetailHeight());
        },

        selectKurir(kurir) {
            this.selectedKurir = kurir;
            this.showKurirList = false;
            this.$nextTick(() => this.syncDetailHeight());
        },

        requireLogin(message) {
            this.alert = {
                show: true,
                message: message || 'Silakan login terlebih dahulu.',
            };
        },

        buyNow() {
            if (this.isOutOfStock) return;
            const kurirId = this.selectedKurir?.id || '';
            const params = new URLSearchParams({
                type: 'buynow',
                productId: String(this.id),
                qty: String(this.quantity),
                unitPrice: String(this.price),
                productDiscount: String(this.promo || 0),
            });
            if (kurirId) params.set('kurirId', String(kurirId));
            window.location.href = `/checkout?${params.toString()}`;
        },

        async addToCart() {
            if (this.isOutOfStock || this.actionBusy) return;
            if (!getAuthToken()) {
                this.requireLogin(storefrontL('Silakan login terlebih dahulu untuk menambah ke keranjang.', 'Please log in first to add items to cart.'));
                return;
            }
            this.actionBusy = true;
            this.statusMessage = storefrontL('Menambah...', 'Adding...');
            this.statusTone = 'info';
            try {
                const res = await fetch('/api/carts', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        product_id: this.id,
                        quantity: this.quantity,
                    }),
                });
                const data = await readApiJson(res);
                if (res.status === 401) {
                    clearAuthSession();
                    this.requireLogin(storefrontL('Sesi habis. Silakan login lagi.', 'Session expired. Please log in again.'));
                    return;
                }
                if (!res.ok) {
                    throw new Error(apiErrorMessage(data, storefrontL('Gagal menambah ke keranjang.', 'Failed to add to cart.')));
                }
                this.flashStatus(storefrontL('Ditambahkan ke keranjang!', 'Added to cart!'), 'success');
                emitEvomiEvent('cart_updated');
            } catch (err) {
                this.flashStatus(
                    err instanceof Error ? err.message : storefrontL('Gagal menambah ke keranjang.', 'Failed to add to cart.'),
                    'error',
                    3200,
                );
            } finally {
                this.actionBusy = false;
            }
        },

        async toggleWishlist() {
            if (!getAuthToken()) {
                this.requireLogin(storefrontL('Silakan login terlebih dahulu untuk menambah wishlist.', 'Please log in first to add to wishlist.'));
                return;
            }
            if (this.actionBusy || this.wishlistBusy) return;
            this.wishlistBusy = true;
            this.wishlistMessage = '';
            try {
                if (this.isWishlisted && this.wishlistId) {
                    const res = await fetch(`/api/wishlists/${this.wishlistId}`, {
                        method: 'DELETE',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                    if (res.status === 401) {
                        clearAuthSession();
                        this.requireLogin(storefrontL('Sesi habis. Silakan login lagi.', 'Session expired. Please log in again.'));
                        return;
                    }
                    if (!res.ok) {
                        const data = await readApiJson(res);
                        throw new Error(apiErrorMessage(data, storefrontL('Gagal menghapus wishlist.', 'Failed to remove from wishlist.')));
                    }
                    this.isWishlisted = false;
                    this.wishlistId = null;
                    this.wishlistMessage = storefrontL('Dihapus dari wishlist.', 'Removed from wishlist.');
                    emitEvomiEvent('wishlist_updated');
                } else {
                    const res = await fetch('/api/wishlists', {
                        method: 'POST',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                        body: JSON.stringify({ product_id: this.id }),
                    });
                    const data = await readApiJson(res);
                    if (res.status === 401) {
                        clearAuthSession();
                        this.requireLogin(storefrontL('Sesi habis. Silakan login lagi.', 'Session expired. Please log in again.'));
                        return;
                    }
                    if (!res.ok) {
                        const message = apiErrorMessage(data, storefrontL('Gagal menambah wishlist.', 'Failed to add to wishlist.'));
                        if (/sudah ada|already/i.test(message)) {
                            await this.syncWishlistState();
                            this.wishlistMessage = storefrontL('Sudah ada di wishlist.', 'Already in wishlist.');
                            return;
                        }
                        throw new Error(message);
                    }
                    this.isWishlisted = true;
                    this.wishlistId = data?.id || data?.data?.id || null;
                    if (!this.wishlistId) await this.syncWishlistState();
                    this.wishlistMessage = storefrontL('Ditambahkan ke wishlist!', 'Added to wishlist!');
                    emitEvomiEvent('wishlist_updated');
                }
                window.setTimeout(() => {
                    this.wishlistMessage = '';
                }, 2200);
            } catch (err) {
                this.wishlistMessage =
                    err instanceof Error ? err.message : storefrontL('Gagal mengubah wishlist.', 'Failed to update wishlist.');
                window.setTimeout(() => {
                    this.wishlistMessage = '';
                }, 3200);
            } finally {
                this.wishlistBusy = false;
            }
        },

        async sendChat() {
            const text = (this.draft || '').trim();
            if (!text) return;
            if (!getAuthToken()) {
                this.requireLogin(storefrontL('Anda harus login terlebih dahulu untuk mengirim pesan ke admin.', 'Please log in first to message admin.'));
                this.isChatOpen = false;
                return;
            }
            try {
                const user = getAuthUser();
                const res = await fetch('/api/contact', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        name: user?.nama || user?.name || 'User',
                        email: user?.email || '',
                        subject: `Chat Produk, ${this.title}`,
                        message: text,
                    }),
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(apiErrorMessage(data, storefrontL('Gagal mengirim pesan.', 'Failed to send message.')));
                }
                this.chatBubbles.push({
                    id: Date.now(),
                    type: 'user',
                    text,
                });
                this.draft = '';
                emitEvomiEvent('chat_updated');
            } catch (err) {
                this.requireLogin(err instanceof Error ? err.message : storefrontL('Gagal mengirim pesan.', 'Failed to send message.'));
                this.isChatOpen = false;
            }
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.productUrl);
                this.isCopied = true;
                window.setTimeout(() => {
                    this.isCopied = false;
                }, 2000);
            } catch {
                this.isCopied = false;
            }
        },
    }));

    Alpine.data('evomiArtikelList', (articles = []) => ({
        articles,
        query: '',
        page: 1,
        perPage: 6,
        searchFocused: false,

        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.articles;
            return this.articles.filter((a) => {
                const hay = `${a.title} ${a.excerpt} ${a.category}`.toLowerCase();
                return hay.includes(q);
            });
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },

        get pageNumbers() {
            return Array.from({ length: this.totalPages }, (_, i) => i + 1);
        },

        get paged() {
            const start = (this.page - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },

        get resultLabel() {
            const q = this.query.trim();
            if (q) {
                return storefrontL(
                    `${this.filtered.length} hasil untuk “${q}”`,
                    `${this.filtered.length} results for “${q}”`,
                );
            }
            return storefrontL(
                `${this.articles.length} artikel tersedia`,
                `${this.articles.length} articles available`,
            );
        },

        init() {
            this.$watch('query', () => {
                this.page = 1;
            });
        },

        scrollTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        goPrev() {
            this.page = Math.max(1, this.page - 1);
            this.scrollTop();
        },

        goNext() {
            this.page = Math.min(this.totalPages, this.page + 1);
            this.scrollTop();
        },

        goToPage(n) {
            this.page = Math.min(this.totalPages, Math.max(1, Number(n) || 1));
            this.scrollTop();
        },

        formatDate(value) {
            try {
                const locale = currentLocale() === 'en' ? 'en-US' : 'id-ID';
                return new Date(value).toLocaleDateString(locale, {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                });
            } catch {
                return value;
            }
        },
    }));

    Alpine.data('evomiArtikelShow', (meta = {}) => ({
        copied: false,
        title: meta.title || document.title,
        excerpt: meta.excerpt || '',

        async copyLink() {
            try {
                await navigator.clipboard.writeText(window.location.href);
                this.copied = true;
                window.setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch {
                this.copied = false;
            }
        },

        async share() {
            const url = window.location.href;
            const title = this.title || 'Evomi';
            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                    return;
                } catch {
                    /* fall through to copy */
                }
            }
            await this.copyLink();
        },
    }));

    Alpine.data('evomiFaq', (groups = {}) => ({
        query: '',
        groups: Object.entries(groups).map(([category, items]) => ({ category, items })),

        get visibleGroups() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.groups;
            return this.groups
                .map((group) => ({
                    ...group,
                    items: group.items.filter(
                        (item) =>
                            item.q.toLowerCase().includes(q) ||
                            item.a.toLowerCase().includes(q),
                    ),
                }))
                .filter((group) => group.items.length > 0);
        },
    }));

    Alpine.data('evomiKontak', () => ({
        form: { name: '', email: '', subject: '', message: '' },
        loading: false,
        status: { type: null, message: '' },

        async submit() {
            this.loading = true;
            this.status = { type: null, message: '' };
            try {
                const res = await fetch('/api/contact', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(apiErrorMessage(data, storefrontL('Gagal mengirim pesan.', 'Failed to send message.')));
                }
                this.status = {
                    type: 'success',
                    message: 'Pesan terkirim. Tim Evomi akan membalas segera.',
                };
                this.form = { name: '', email: '', subject: '', message: '' };
            } catch (err) {
                this.status = {
                    type: 'error',
                    message: err instanceof Error ? err.message : 'Gagal mengirim pesan.',
                };
            } finally {
                this.loading = false;
            }
        },
    }));

    Alpine.data('evomiKuis', (questions = [], results = {}) => ({
        questions,
        results,
        step: 0,
        finished: false,
        accent: DEFAULT_THEME_BLUE,
        scores: {
            peaceful_calm: 0,
            purpose_prestige: 0,
            sweet_shy: 0,
            rebel_brave: 0,
        },
        answers: [],
        resultKey: null,
        result: null,
        submitting: false,

        get currentQuestion() {
            return this.questions[this.step] || null;
        },

        get progress() {
            if (!this.questions.length) return 0;
            return ((this.step + 1) / this.questions.length) * 100;
        },

        get hasCustomBgWidth() {
            return Boolean(this.result?.bg_image_width_mobile || this.result?.bg_image_width_desktop);
        },

        get hasCustomProductWidth() {
            return Boolean(this.result?.product_image_width_mobile || this.result?.product_image_width_desktop);
        },

        resultImageStyle(kind = 'bg') {
            const style = { objectPosition: 'right bottom' };
            if (kind === 'bg' && this.hasCustomBgWidth) {
                const m = this.result.bg_image_width_mobile || this.result.bg_image_width_desktop || 300;
                const d = this.result.bg_image_width_desktop || this.result.bg_image_width_mobile || 380;
                style['--qr-bg-w-m'] = `${m}px`;
                style['--qr-bg-w-d'] = `${d}px`;
            }
            if (kind === 'product' && this.hasCustomProductWidth) {
                const m = this.result.product_image_width_mobile || this.result.product_image_width_desktop || 320;
                const d = this.result.product_image_width_desktop || this.result.product_image_width_mobile || 500;
                style['--qr-product-w-m'] = `${m}px`;
                style['--qr-product-w-d'] = `${d}px`;
            }
            return style;
        },

        async answer(option) {
            const question = this.currentQuestion;
            if (!question || !option) return;

            ['peaceful_calm', 'purpose_prestige', 'sweet_shy', 'rebel_brave'].forEach((key) => {
                this.scores[key] += Number(option[key] || 0);
            });
            this.answers.push({
                question_id: Number(question.id),
                option_id: Number(option.id),
            });

            if (this.step >= this.questions.length - 1) {
                await this.finish();
                return;
            }
            this.step += 1;
        },

        getResultKey() {
            let highest = -1;
            let winner = 'purpose_prestige';
            ['peaceful_calm', 'purpose_prestige', 'sweet_shy', 'rebel_brave'].forEach((key) => {
                const score = Number(this.scores[key] || 0);
                if (score > highest) {
                    highest = score;
                    winner = key;
                }
            });
            return winner;
        },

        async submitHistory() {
            if (!getAuthToken() || this.answers.length === 0 || this.submitting) return;
            this.submitting = true;
            try {
                const locale = currentLocale();
                await fetch(`/api/quiz/submit?locale=${locale}`, {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({ answers: this.answers, locale }),
                });
            } catch {
                // Hasil lokal tetap ditampilkan
            } finally {
                this.submitting = false;
            }
        },

        async finish() {
            await this.submitHistory();
            const winner = this.getResultKey();
            this.resultKey = winner;
            this.result = this.results[winner] || this.results.purpose_prestige || null;
            this.finished = true;
            const color = this.result?.color || DEFAULT_THEME_BLUE;
            this.accent = color;
            applyProductTheme(color);
            this.$nextTick(() => {
                applyProductTheme(color);
                if (typeof bindSoftLinks === 'function') {
                    bindSoftLinks(this.$el);
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        },

        scrollToDetail() {
            this.$refs.productDetail?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        restart() {
            this.step = 0;
            this.finished = false;
            this.result = null;
            this.resultKey = null;
            this.answers = [];
            this.accent = DEFAULT_THEME_BLUE;
            this.scores = {
                peaceful_calm: 0,
                purpose_prestige: 0,
                sweet_shy: 0,
                rebel_brave: 0,
            };
            restoreProductTheme();
            this.$nextTick(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        },
    }));

    Alpine.data('evomiAuth', (mode = 'login') => ({
        mode,
        loading: false,
        error: '',
        showPassword: false,
        passwordFocused: false,
        form: { name: '', email: '', password: '' },
        modal: {
            show: false,
            type: 'success',
            title: '',
            message: '',
            cta: 'Tutup',
            go: null,
        },

        openModal({ type = 'success', title, message, cta, go = null }) {
            this.modal = {
                show: true,
                type,
                title,
                message,
                cta: cta || (type === 'success' ? 'Lanjutkan' : 'Mengerti'),
                go,
            };
        },

        destinationForUser() {
            // Admin & user sama: ke beranda dulu (dashboard tetap bisa diakses dari menu akun)
            return '/';
        },

        async submit() {
            this.error = '';
            const password = this.form.password || '';

            if (password.length < 8) {
                if (this.mode === 'login') {
                    this.openModal({
                        type: 'warning',
                        title: 'Keamanan Lemah',
                        message:
                            'Password harus memiliki minimal 8 karakter demi keamanan akun Evomi Anda.',
                        cta: 'Mengerti',
                    });
                } else {
                    this.error = 'Password minimal 8 karakter.';
                }
                return;
            }

            if (this.mode === 'register' && !(this.form.name || '').trim()) {
                this.error = 'Nama lengkap wajib diisi.';
                return;
            }

            this.loading = true;

            try {
                const endpoint = this.mode === 'login' ? '/api/login' : '/api/register';
                const body =
                    this.mode === 'login'
                        ? { email: this.form.email, password }
                        : {
                              name: this.form.name.trim(),
                              email: this.form.email,
                              password,
                          };

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify(body),
                });

                const data = await readApiJson(res);

                if (!res.ok) {
                    const msg = apiErrorMessage(
                        data,
                        this.mode === 'login'
                            ? 'Login gagal. Periksa kembali email dan password Anda.'
                            : 'Registrasi gagal. Silakan coba lagi.',
                    );

                    if (this.mode === 'login') {
                        this.openModal({
                            type: 'error',
                            title: 'Akses Ditolak',
                            message: msg,
                        });
                    } else {
                        this.error = msg;
                    }
                    return;
                }

                const user = data.user || {};
                const token = data.token || '';
                if (!token) {
                    throw new Error('Token tidak diterima dari server.');
                }

                setAuthSession(token, user);
                const go = this.destinationForUser(user);

                if (this.mode === 'login') {
                    this.openModal({
                        type: 'success',
                        title: 'Selamat Datang!',
                        message:
                            'Login berhasil. Selamat melanjutkan petualangan aroma Anda bersama Evomi.',
                        cta: 'Lanjutkan',
                        go,
                    });
                    return;
                }

                softNavigate(go);
            } catch (err) {
                const msg =
                    err instanceof Error
                        ? err.message
                        : 'Tidak dapat terhubung ke server.';
                if (this.mode === 'login') {
                    this.openModal({
                        type: 'error',
                        title: 'Akses Ditolak',
                        message: msg,
                    });
                } else {
                    this.error = msg;
                }
            } finally {
                this.loading = false;
            }
        },

        closeModal() {
            const go = this.modal.go;
            const type = this.modal.type;
            this.modal.show = false;
            if (go && type === 'success') softNavigate(go);
        },
    }));

    Alpine.data('evomiAdminGate', () => ({
        ready: false,
        denied: false,
        deniedMessage: '',
        locale: 'id',

        init() {
            this.locale = readAdminLocale();
            document.documentElement.setAttribute('data-admin-theme', 'light');
            try {
                localStorage.setItem('evomi-admin-theme', 'light');
            } catch {
                /* ignore */
            }

            const token = getAuthToken();
            const user = getAuthUser();
            const i18n = createAdminI18nApi(() => this.locale);

            if (!token || !user) {
                this.denied = true;
                this.deniedMessage = i18n.t('auth', 'login_required');
                window.setTimeout(() => {
                    window.location.replace('/login');
                }, 900);
                return;
            }

            const isAdmin = user.is_admin === true || user.is_admin === 1 || user.is_admin === '1';
            if (!isAdmin) {
                clearAuthSession();
                this.denied = true;
                this.deniedMessage = i18n.t('auth', 'denied_message');
                window.setTimeout(() => {
                    window.location.replace('/login');
                }, 1400);
                return;
            }

            this.ready = true;
        },

        t(section, key, id = '', en = '') {
            return createAdminI18nApi(() => this.locale).t(section, key, id, en);
        },

        setLocale(next) {
            const code = next === 'en' ? 'en' : 'id';
            if (this.locale === code) return;
            beginLocaleSwitchFx();
            this.locale = writeAdminLocale(code);
            window.dispatchEvent(new CustomEvent('evomi-admin-locale', { detail: this.locale }));
            window.dispatchEvent(new Event('locale-change'));
            window.setTimeout(() => finishLocaleSwitchFx(), 420);
        },

        async logout() {
            const token = getAuthToken();
            try {
                if (token) {
                    await fetch('/api/logout', {
                        method: 'POST',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                }
            } catch {
                /* ignore */
            } finally {
                clearAuthSession();
                window.location.href = '/login';
            }
        },
    }));

    Alpine.data('evomiAdminHome', () => ({
        loading: true,
        error: '',
        stats: {
            totalProducts: 0,
            totalOrders: 0,
            activeUsers: 0,
            totalRevenue: 0,
        },
        chartData: [],
        recentOrders: [],

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const headers = authHeaders(true);
                const [productsRes, ordersRes, usersRes, revenueRes] = await Promise.all([
                    fetch('/api/products', { headers: { Accept: 'application/json' } }),
                    fetch('/api/admin/orders', { headers }),
                    fetch('/api/admin/users', { headers }),
                    fetch('/api/admin/revenue', { headers }),
                ]);

                if (ordersRes.status === 401 || ordersRes.status === 403) {
                    clearAuthSession();
                    window.location.replace('/login');
                    return;
                }

                const products = await readApiJson(productsRes);
                const orders = await readApiJson(ordersRes);
                const users = await readApiJson(usersRes);
                const revenue = await readApiJson(revenueRes);

                if (!ordersRes.ok) {
                    throw new Error(apiErrorMessage(orders, 'Gagal memuat pesanan admin.'));
                }

                const productsList = products?.data || products || [];
                const ordersList = orders?.data || orders || [];
                const usersList = users?.data || users || [];

                this.stats = {
                    totalProducts: Array.isArray(productsList) ? productsList.length : 0,
                    totalOrders: Array.isArray(ordersList) ? ordersList.length : 0,
                    activeUsers: Array.isArray(usersList) ? usersList.length : 0,
                    totalRevenue: Number(revenue?.data?.total_revenue || 0) || 0,
                };

                const salesByDate = {};
                for (const order of ordersList) {
                    if (!isSuccessfulPayment(order.payment_status)) continue;
                    const date = new Date(order.created_at).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                    });
                    salesByDate[date] = (salesByDate[date] || 0) + orderGrandTotal(order);
                }
                this.chartData = Object.keys(salesByDate).map((name) => ({
                    name,
                    total: salesByDate[name],
                }));

                this.recentOrders = [...ordersList]
                    .sort(
                        (a, b) =>
                            new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
                    )
                    .slice(0, 5)
                    .map((order) => {
                        const pay = normalizePaymentStatus(order.payment_status);
                        const fulfill = fulfillmentStatusConfig(order.status);
                        return {
                            ...order,
                            productTitle: order.product?.title || 'Produk Hilang',
                            buyerName: order.user?.name || 'Anonim',
                            totalLabel: formatRupiah(orderGrandTotal(order)),
                            imageUrl: storageUrl(order.product?.image_1),
                            orderDate: new Date(order.created_at).toLocaleString('id-ID', {
                                day: 'numeric',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                            }),
                            statusLabel: fulfill.label,
                            statusClass: fulfill.class,
                            paymentLabel: paymentStatusLabel(pay),
                            paymentClass: paymentStatusBadgeClass(pay),
                        };
                    });
            } catch (err) {
                this.error =
                    err instanceof Error ? err.message : 'Gagal mengambil data dashboard.';
            } finally {
                this.loading = false;
            }
        },

        formatRupiah,
        chartMax() {
            if (!this.chartData.length) return 1;
            return Math.max(...this.chartData.map((d) => d.total), 1);
        },
        chartPath() {
            const data = this.chartData;
            if (!data.length) return '';
            const max = this.chartMax();
            const w = 100;
            const h = 100;
            const step = data.length === 1 ? 0 : w / (data.length - 1);
            return data
                .map((d, i) => {
                    const x = data.length === 1 ? w / 2 : i * step;
                    const y = h - (d.total / max) * (h * 0.85) - h * 0.05;
                    return `${i === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`;
                })
                .join(' ');
        },
        chartArea() {
            const data = this.chartData;
            if (!data.length) return '';
            const max = this.chartMax();
            const w = 100;
            const h = 100;
            const step = data.length === 1 ? 0 : w / (data.length - 1);
            const points = data.map((d, i) => {
                const x = data.length === 1 ? w / 2 : i * step;
                const y = h - (d.total / max) * (h * 0.85) - h * 0.05;
                return `${x.toFixed(2)},${y.toFixed(2)}`;
            });
            return `M 0,${h} L ${points.join(' L ')} L ${w},${h} Z`;
        },
    }));

    Alpine.data('evomiProfileShell', (initialKey = 'settings') => ({
        ready: false,
        activeKey: initialKey || 'settings',
        badges: { cart: 0, wishlist: 0, history: 0, unread: 0 },
        indicator: { top: 0, height: 0, opacity: 0, color: '#1172BA' },

        get indicatorStyle() {
            return {
                transform: `translate3d(0, ${this.indicator.top}px, 0)`,
                height: `${Math.max(this.indicator.height, 0)}px`,
                opacity: this.indicator.opacity,
                backgroundColor: this.indicator.color,
            };
        },

        badgeLabel(key) {
            return formatBadge(this.badges?.[key] || 0);
        },

        previewTo(key) {
            if (!key || key === this.activeKey) return;
            this.setActive(key, true);
        },

        setActive(key, animate = true) {
            if (!key) return;
            this.activeKey = key;
            this.$el?.setAttribute('data-active-menu', key);
            this.moveIndicator(key, animate);
        },

        moveIndicator(key, animate = true) {
            const track = this.$refs.profileTrack;
            if (!track) return;

            const item = track.querySelector(`[data-profile-key="${key}"]`);
            if (!item) {
                this.indicator = { ...this.indicator, opacity: 0 };
                return;
            }

            const trackRect = track.getBoundingClientRect();
            const itemRect = item.getBoundingClientRect();
            const color = item.dataset.profileColor || '#1172BA';

            if (!animate) track.classList.add('profile-indicator-no-anim');

            this.indicator = {
                top: itemRect.top - trackRect.top,
                height: itemRect.height,
                opacity: 1,
                color,
            };

            if (!animate) {
                requestAnimationFrame(() => track.classList.remove('profile-indicator-no-anim'));
            }
        },

        async init() {
            window.__evomiProfileShell = this;

            const token = getAuthToken();
            const user = getAuthUser();
            if (!token || !user) {
                window.location.replace('/login');
                return;
            }
            this.ready = true;
            await this.refreshBadges();
            this._onBadge = () => this.refreshBadges();
            window.addEventListener('cart_updated', this._onBadge);
            window.addEventListener('wishlist_updated', this._onBadge);
            window.addEventListener('history_updated', this._onBadge);
            window.addEventListener('messages_read', this._onBadge);
            window.addEventListener('auth-change', this._onBadge);

            this._onResize = () => this.moveIndicator(this.activeKey, false);
            window.addEventListener('resize', this._onResize);

            this.$nextTick(() => {
                this.moveIndicator(this.activeKey, false);
                requestAnimationFrame(() => this.moveIndicator(this.activeKey, true));
                bindSoftLinks(this.$el);
            });
        },

        destroy() {
            if (window.__evomiProfileShell === this) {
                window.__evomiProfileShell = null;
            }
            if (this._onBadge) {
                window.removeEventListener('cart_updated', this._onBadge);
                window.removeEventListener('wishlist_updated', this._onBadge);
                window.removeEventListener('history_updated', this._onBadge);
                window.removeEventListener('messages_read', this._onBadge);
                window.removeEventListener('auth-change', this._onBadge);
            }
            if (this._onResize) {
                window.removeEventListener('resize', this._onResize);
            }
        },

        async refreshBadges() {
            this.badges = await fetchBadgeCounts();
        },
    }));

    Alpine.data('evomiProfileSettings', () => ({
        loading: true,
        saving: false,
        showPassword: false,
        status: { type: '', message: '' },
        toast: '',
        form: { name: '', email: '', phone: '', address: '', password: '' },
        avatarPreview: null,
        avatarFile: null,
        avatarPath: null,
        lastLoginAt: null,
        lastSeenAt: null,
        lastLoginLabel: '',
        lastSeenLabel: '',
        lastSeenExact: '',

        get initial() {
            return userDisplayInitial(this.form.name || this.form.email);
        },

        async init() {
            await this.load();
        },

        showToast(message, ms = 2400) {
            this.toast = message;
            if (this._toastTimer) window.clearTimeout(this._toastTimer);
            this._toastTimer = window.setTimeout(() => {
                this.toast = '';
            }, ms);
        },

        formatPresence(value) {
            if (!value) return '—';
            try {
                return new Date(value).toLocaleString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            } catch {
                return String(value);
            }
        },

        formatPresenceRelative(value) {
            if (!value) return '—';
            try {
                const d = new Date(value);
                const diffMs = Date.now() - d.getTime();
                if (Number.isNaN(diffMs)) return this.formatPresence(value);
                const mins = Math.floor(diffMs / 60000);
                if (mins < 1) return 'Baru saja';
                if (mins < 60) return `${mins} menit lalu`;
                const hours = Math.floor(mins / 60);
                if (hours < 24) return `${hours} jam lalu`;
                const days = Math.floor(hours / 24);
                if (days < 7) return `${days} hari lalu`;
                return this.formatPresence(value);
            } catch {
                return this.formatPresence(value);
            }
        },

        async load() {
            this.loading = true;
            try {
                const res = await fetch('/api/user/profile', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    window.location.replace('/login');
                    return;
                }
                const data = await readApiJson(res);
                const user = data.data || data.user || data;
                this.form = {
                    name: user.name || user.nama_lengkap || '',
                    email: user.email || '',
                    phone: user.phone || '',
                    address: user.alamat_lengkap || '',
                    password: '',
                };
                this.avatarPath = user.avatar_profile || user.avatar || null;
                this.avatarPreview = avatarUrlFromUser(user);
                this.lastLoginAt = user.last_login_at || null;
                this.lastSeenAt = user.last_seen_at || null;
                this.lastLoginLabel = this.formatPresence(this.lastLoginAt);
                this.lastSeenLabel = this.formatPresenceRelative(this.lastSeenAt);
                this.lastSeenExact = this.formatPresence(this.lastSeenAt);
            } catch (err) {
                this.status = {
                    type: 'error',
                    message: err instanceof Error ? err.message : storefrontL('Gagal memuat profil.', 'Failed to load profile.'),
                };
            } finally {
                this.loading = false;
            }
        },

        onAvatarChange(e) {
            const file = e.target.files?.[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                this.showToast('Ukuran foto maksimal 2MB.');
                e.target.value = '';
                return;
            }
            this.avatarFile = file;
            this.avatarPreview = URL.createObjectURL(file);
        },

        onAvatarError() {
            this.avatarPreview = null;
        },

        async save() {
            this.saving = true;
            this.status = { type: 'processing', message: storefrontL('Menyimpan perubahan...', 'Saving changes...') };
            try {
                if (this.form.password && this.form.password.length < 8) {
                    throw new Error('Password baru minimal 8 karakter.');
                }

                const fd = new FormData();
                fd.append('name', this.form.name);
                fd.append('nama_lengkap', this.form.name);
                fd.append('email', this.form.email);
                fd.append('phone', this.form.phone || '');
                fd.append('alamat_lengkap', this.form.address || '');
                if (this.form.password) fd.append('password', this.form.password);
                if (this.avatarFile) fd.append('avatar_profile', this.avatarFile);

                const headers = authHeaders(false);
                delete headers['Content-Type'];

                const res = await fetch('/api/user/profile', {
                    method: 'POST',
                    headers,
                    credentials: 'same-origin',
                    body: fd,
                });
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Gagal menyimpan profil.', 'Failed to save profile.')));

                const user = data.data || data.user || {};
                const prev = getAuthUser() || {};
                const merged = {
                    ...prev,
                    ...user,
                    avatar_profile: user.avatar_profile ?? prev.avatar_profile ?? null,
                    updated_at: user.updated_at || new Date().toISOString(),
                };
                setAuthSession(getAuthToken(), merged);
                this.form.password = '';
                this.avatarFile = null;
                this.avatarPath = merged.avatar_profile || null;
                this.avatarPreview = avatarUrlFromUser(merged);
                syncNavbarAuth();
                this.status = { type: '', message: '' };
                this.showToast(storefrontL('Profil berhasil diperbarui.', 'Profile updated successfully.'));
            } catch (err) {
                this.status = {
                    type: 'error',
                    message: err instanceof Error ? err.message : storefrontL('Gagal menyimpan.', 'Failed to save.'),
                };
            } finally {
                this.saving = false;
            }
        },
    }));

    Alpine.data('evomiProfileCart', () => ({
        loading: true,
        error: '',
        items: [],
        toast: '',
        updatingId: null,
        modal: { open: false, type: 'confirm', message: '', confirmAction: null },

        get subtotalLabel() {
            const total = this.items.reduce(
                (s, i) => s + (Number(i.unitPrice) || 0) * (Number(i.quantity) || 0),
                0,
            );
            return formatRupiah(total);
        },

        get itemCount() {
            return this.items.reduce((s, i) => s + (Number(i.quantity) || 0), 0);
        },

        async init() {
            await this.load();
        },

        showToast(message, ms = 2200) {
            this.toast = message;
            if (this._toastTimer) window.clearTimeout(this._toastTimer);
            this._toastTimer = window.setTimeout(() => {
                this.toast = '';
            }, ms);
        },

        openConfirm(message, confirmAction) {
            this.modal = { open: true, type: 'confirm', message, confirmAction };
        },

        openError(message) {
            this.modal = { open: true, type: 'error', message, confirmAction: null };
        },

        closeModal() {
            this.modal = { open: false, type: 'confirm', message: '', confirmAction: null };
        },

        async confirmModal() {
            const action = this.modal.confirmAction;
            this.closeModal();
            if (typeof action === 'function') await action();
        },

        mapItem(row) {
            const unit = productPrice(row.product);
            const qty = Number(row.quantity) || 1;
            return {
                id: row.id,
                product_id: row.product_id || row.product?.id,
                title: productTitle(row.product),
                imageUrl: productImage(row.product, 'cart'),
                accent: productAccent(row.product),
                stock: Number(row.product?.quantity ?? row.product?.stock ?? 0) || 0,
                quantity: qty,
                unitPrice: unit,
                priceLabel: formatRupiah(unit),
                lineTotalLabel: formatRupiah(unit * qty),
            };
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/api/carts?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    window.location.replace('/login');
                    return;
                }
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Gagal memuat keranjang.', 'Failed to load cart.')));
                const list = Array.isArray(data) ? data : data.data || [];
                this.items = list.map((row) => this.mapItem(row));
                emitEvomiEvent('cart_updated');
            } catch (err) {
                this.error = err instanceof Error ? err.message : storefrontL('Gagal memuat keranjang.', 'Failed to load cart.');
            } finally {
                this.loading = false;
                this.$nextTick(() => bindSoftLinks(this.$el));
            }
        },

        async changeQty(item, delta) {
            const next = (Number(item.quantity) || 1) + delta;
            if (next < 1) {
                this.requestRemove(item);
                return;
            }
            if (item.stock && next > item.stock) {
                this.showToast(storefrontL(`Stok tidak mencukupi. Tersedia: ${item.stock}`, `Insufficient stock. Available: ${item.stock}`));
                return;
            }
            if (this.updatingId === item.id) return;

            const prevQty = item.quantity;
            this.updatingId = item.id;
            item.quantity = next;
            item.lineTotalLabel = formatRupiah(item.unitPrice * next);
            try {
                const res = await fetch(`/api/carts/${item.id}`, {
                    method: 'PUT',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({ quantity: next }),
                });
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Gagal mengubah jumlah.', 'Failed to update quantity.')));
                emitEvomiEvent('cart_updated');
            } catch (err) {
                item.quantity = prevQty;
                item.lineTotalLabel = formatRupiah(item.unitPrice * prevQty);
                this.openError(err instanceof Error ? err.message : storefrontL('Gagal mengubah jumlah.', 'Failed to update quantity.'));
            } finally {
                this.updatingId = null;
            }
        },

        requestRemove(item) {
            this.openConfirm(storefrontL('Hapus produk ini dari keranjang?', 'Remove this product from cart?'), async () => {
                await this.remove(item);
            });
        },

        async remove(item) {
            try {
                const res = await fetch(`/api/carts/${item.id}`, {
                    method: 'DELETE',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    const data = await readApiJson(res);
                    throw new Error(apiErrorMessage(data, storefrontL('Gagal menghapus item.', 'Failed to remove item.')));
                }
                this.items = this.items.filter((i) => i.id !== item.id);
                emitEvomiEvent('cart_updated');
                this.showToast(storefrontL('Item dihapus dari keranjang.', 'Item removed from cart.'));
            } catch (err) {
                this.openError(err instanceof Error ? err.message : storefrontL('Gagal menghapus.', 'Failed to remove.'));
            }
        },

        goCheckout() {
            if (!this.items.length) {
                this.showToast(storefrontL('Keranjang masih kosong.', 'Cart is still empty.'));
                return;
            }
            this.showToast(storefrontL('Mengalihkan ke checkout...', 'Redirecting to checkout...'));
            window.setTimeout(() => {
                window.location.href = '/checkout?type=cart';
            }, 500);
        },
    }));

    Alpine.data('evomiProfileWishlist', () => ({
        loading: true,
        error: '',
        items: [],
        toast: '',
        addingId: null,
        modal: { open: false, type: 'confirm', message: '', targetId: null },

        async init() {
            await this.load();
        },

        showToast(message, ms = 2200) {
            this.toast = message;
            if (this._toastTimer) window.clearTimeout(this._toastTimer);
            this._toastTimer = window.setTimeout(() => {
                this.toast = '';
            }, ms);
        },

        closeModal() {
            this.modal = { open: false, type: 'confirm', message: '', targetId: null };
        },

        requestRemove(item) {
            this.modal = {
                open: true,
                type: 'confirm',
                message: storefrontL('Hapus produk ini dari wishlist?', 'Remove this product from wishlist?'),
                targetId: item.id,
            };
        },

        openError(message) {
            this.modal = { open: true, type: 'error', message, targetId: null };
        },

        mapItem(row) {
            return {
                id: row.id,
                product_id: row.product_id || row.product?.id,
                title: productTitle(row.product),
                imageUrl: productImage(row.product, 'wishlist'),
                accent: productAccent(row.product),
                priceLabel: formatRupiah(productPrice(row.product)),
            };
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/api/wishlists?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    window.location.replace('/login');
                    return;
                }
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Gagal memuat wishlist.', 'Failed to load wishlist.')));
                const list = Array.isArray(data) ? data : data.data || [];
                this.items = list.map((row) => this.mapItem(row));
                emitEvomiEvent('wishlist_updated');
            } catch (err) {
                this.error = err instanceof Error ? err.message : storefrontL('Gagal memuat wishlist.', 'Failed to load wishlist.');
            } finally {
                this.loading = false;
                this.$nextTick(() => bindSoftLinks(this.$el));
            }
        },

        async confirmRemove() {
            const id = this.modal.targetId;
            if (!id) {
                this.closeModal();
                return;
            }
            const item = this.items.find((i) => i.id === id);
            this.closeModal();
            if (item) await this.remove(item);
        },

        async remove(item) {
            try {
                const res = await fetch(`/api/wishlists/${item.id}`, {
                    method: 'DELETE',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    const data = await readApiJson(res);
                    throw new Error(apiErrorMessage(data, storefrontL('Gagal menghapus wishlist.', 'Failed to remove from wishlist.')));
                }
                this.items = this.items.filter((i) => i.id !== item.id);
                emitEvomiEvent('wishlist_updated');
                this.showToast(storefrontL('Produk dihapus dari wishlist.', 'Product removed from wishlist.'));
            } catch (err) {
                this.openError(err instanceof Error ? err.message : storefrontL('Gagal menghapus.', 'Failed to remove.'));
            }
        },

        async moveToCart(item) {
            if (!getAuthToken()) {
                this.openError('Silakan login terlebih dahulu untuk menambahkan produk.');
                return;
            }
            if (this.addingId === item.id) return;
            this.addingId = item.id;
            try {
                const res = await fetch('/api/carts', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({ product_id: item.product_id, quantity: 1 }),
                });
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Gagal menambah ke keranjang.', 'Failed to add to cart.')));

                const del = await fetch(`/api/wishlists/${item.id}`, {
                    method: 'DELETE',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (del.ok) {
                    this.items = this.items.filter((i) => i.id !== item.id);
                }
                emitEvomiEvent('cart_updated');
                emitEvomiEvent('wishlist_updated');
                this.showToast('Ditambahkan ke keranjang.');
            } catch (err) {
                this.openError(err instanceof Error ? err.message : storefrontL('Gagal memindahkan.', 'Failed to move item.'));
            } finally {
                this.addingId = null;
            }
        },
    }));

    Alpine.data('evomiProfileHistory', () => ({
        loading: true,
        error: '',
        groups: [],
        page: 1,
        perPage: 5,
        toast: '',
        modal: {
            open: false,
            type: 'confirm',
            variant: 'confirm',
            title: '',
            message: '',
            confirmText: '',
            action: null,
        },

        get pageCount() {
            return Math.max(1, Math.ceil(this.groups.length / this.perPage));
        },

        get pagedGroups() {
            const start = (this.page - 1) * this.perPage;
            return this.groups.slice(start, start + this.perPage);
        },

        async init() {
            await this.load();
        },

        showToast(message, ms = 2200) {
            this.toast = message;
            if (this._toastTimer) window.clearTimeout(this._toastTimer);
            this._toastTimer = window.setTimeout(() => {
                this.toast = '';
            }, ms);
        },

        closeModal() {
            this.modal = {
                open: false,
                type: 'confirm',
                variant: 'confirm',
                title: '',
                message: '',
                confirmText: '',
                action: null,
            };
        },

        openConfirm({ variant, title, message, confirmText, action }) {
            this.modal = {
                open: true,
                type: 'confirm',
                variant: variant || 'confirm',
                title,
                message,
                confirmText,
                action,
            };
        },

        async runModalAction() {
            const action = this.modal.action;
            if (typeof action !== 'function') {
                this.closeModal();
                return;
            }
            this.modal.type = 'loading';
            this.modal.title = storefrontL('Memproses...', 'Processing...');
            this.modal.message =
                this.modal.variant === 'delete'
                    ? storefrontL('Sedang menghapus riwayat pesanan Anda...', 'Deleting your order history...')
                    : storefrontL('Sedang menyelesaikan pesanan Anda...', 'Completing your order...');
            try {
                await action();
                this.closeModal();
            } catch (err) {
                this.modal.type = 'error';
                this.modal.title = storefrontL('Gagal', 'Failed');
                this.modal.message =
                    err instanceof Error ? err.message : storefrontL('Terjadi kesalahan. Coba lagi.', 'Something went wrong. Please try again.');
                this.modal.action = null;
            }
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/api/shopping-history?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    window.location.replace('/login');
                    return;
                }
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Gagal memuat riwayat belanja.', 'Failed to load order history.')));
                const list = Array.isArray(data) ? data : data.data || [];
                this.groups = groupOrdersByCreatedAt(list);
                cacheHistoryGroups(this.groups);
                this.page = 1;
                emitEvomiEvent('history_updated');
            } catch (err) {
                this.error = err instanceof Error ? err.message : storefrontL('Gagal memuat riwayat.', 'Failed to load history.');
            } finally {
                this.loading = false;
                this.$nextTick(() => bindSoftLinks(this.$el));
            }
        },

        requestConfirm(group) {
            this.openConfirm({
                variant: 'confirm',
                title: storefrontL('Pesanan Diterima', 'Order Received'),
                message:
                    storefrontL('Apakah Anda yakin telah menerima paket pesanan ini dengan baik? Jika ya, pesanan akan diselesaikan.', 'Have you received this package in good condition? If yes, the order will be completed.'),
                confirmText: storefrontL('Ya, Terima Pesanan', 'Yes, Order Received'),
                action: async () => {
                    for (const item of group.items) {
                        const res = await fetch(`/api/orders/${item.id}/confirm`, {
                            method: 'PATCH',
                            headers: authHeaders(true),
                            credentials: 'same-origin',
                        });
                        if (!res.ok) {
                            const data = await readApiJson(res);
                            throw new Error(apiErrorMessage(data, storefrontL('Gagal konfirmasi pesanan.', 'Failed to confirm order.')));
                        }
                    }
                    emitEvomiEvent('history_updated');
                    await this.load();
                    this.showToast(storefrontL('Pesanan telah berhasil diselesaikan.', 'Order completed successfully.'));
                },
            });
        },

        requestRemove(group) {
            this.openConfirm({
                variant: 'delete',
                title: storefrontL('Hapus Riwayat', 'Delete History'),
                message:
                    storefrontL('Apakah Anda yakin ingin menghapus seluruh pesanan ini dari riwayat belanja?', 'Are you sure you want to delete this order from your shopping history?'),
                confirmText: storefrontL('Ya, Hapus', 'Yes, Delete'),
                action: async () => {
                    for (const item of group.items) {
                        const res = await fetch(`/api/orders/${item.id}`, {
                            method: 'DELETE',
                            headers: authHeaders(true),
                            credentials: 'same-origin',
                        });
                        if (!res.ok) {
                            const data = await readApiJson(res);
                            throw new Error(apiErrorMessage(data, storefrontL('Gagal menghapus riwayat.', 'Failed to delete history.')));
                        }
                    }
                    emitEvomiEvent('history_updated');
                    await this.load();
                    this.showToast(storefrontL('Riwayat pesanan telah dihapus.', 'Order history deleted.'));
                },
            });
        },
    }));

    Alpine.data('evomiProfileHistoryShow', (orderId) => ({
        orderId,
        loading: false,
        error: '',
        group: null,
        toast: '',
        modal: {
            open: false,
            type: 'confirm',
            variant: 'confirm',
            title: '',
            message: '',
            confirmText: '',
            action: null,
        },

        get themeColor() {
            return this.group?.accent || '#1172BA';
        },

        get statusIcon() {
            const s = String(this.group?.status || '').toLowerCase();
            if (s === 'menunggu_konfirmasi') return 'clock';
            if (s === 'pengemasan') return 'box';
            if (s === 'dalam_perjalanan') return 'truck';
            return 'check';
        },

        async init() {
            const cached = findCachedHistoryGroup(this.orderId);
            if (cached) {
                this.group = cached;
                this.error = '';
            }
            await this.load({ silent: Boolean(cached) });
        },

        showToast(message, ms = 2200) {
            this.toast = message;
            if (this._toastTimer) window.clearTimeout(this._toastTimer);
            this._toastTimer = window.setTimeout(() => {
                this.toast = '';
            }, ms);
        },

        closeModal() {
            this.modal = {
                open: false,
                type: 'confirm',
                variant: 'confirm',
                title: '',
                message: '',
                confirmText: '',
                action: null,
            };
        },

        openConfirm({ variant, title, message, confirmText, action }) {
            this.modal = {
                open: true,
                type: 'confirm',
                variant: variant || 'confirm',
                title,
                message,
                confirmText,
                action,
            };
        },

        async runModalAction() {
            const action = this.modal.action;
            if (typeof action !== 'function') {
                this.closeModal();
                return;
            }
            this.modal.type = 'loading';
            this.modal.title = storefrontL('Memproses...', 'Processing...');
            this.modal.message =
                this.modal.variant === 'delete'
                    ? 'Menghapus item dari riwayat...'
                    : storefrontL('Sedang menyelesaikan pesanan Anda...', 'Completing your order...');
            try {
                await action();
                this.closeModal();
            } catch (err) {
                this.modal.type = 'error';
                this.modal.title = storefrontL('Gagal', 'Failed');
                this.modal.message =
                    err instanceof Error ? err.message : storefrontL('Terjadi kesalahan. Coba lagi.', 'Something went wrong. Please try again.');
                this.modal.action = null;
            }
        },

        async load({ silent = false } = {}) {
            // Never show a full loading screen on history detail
            this.loading = false;
            if (!silent) this.error = '';
            try {
                const res = await fetch('/api/shopping-history?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    window.location.replace('/login');
                    return;
                }
                const data = await readApiJson(res);
                const list = Array.isArray(data) ? data : data.data || [];
                const groups = groupOrdersByCreatedAt(list);
                cacheHistoryGroups(groups);
                const found =
                    groups.find((g) => String(g.groupId) === String(this.orderId)) ||
                    groups.find((g) => g.items.some((i) => String(i.id) === String(this.orderId))) ||
                    null;
                if (found) {
                    this.group = found;
                    this.error = '';
                } else if (!this.group) {
                    this.error = storefrontL('Pesanan tidak ditemukan di dalam riwayat belanja Anda.', 'Order not found in your shopping history.');
                }
            } catch (err) {
                if (!this.group) {
                    this.error = err instanceof Error ? err.message : storefrontL('Gagal memuat detail.', 'Failed to load details.');
                }
            } finally {
                this.loading = false;
                this.$nextTick(() => bindSoftLinks(this.$el));
            }
        },

        requestConfirm() {
            if (!this.group) return;
            this.openConfirm({
                variant: 'confirm',
                title: 'Konfirmasi Pesanan',
                message:
                    'Apakah Anda yakin telah menerima seluruh paket pesanan ini dengan baik? Status pesanan akan diubah menjadi Selesai.',
                confirmText: 'Ya, Sudah Diterima',
                action: async () => {
                    for (const item of this.group.items) {
                        const res = await fetch(`/api/orders/${item.id}/confirm`, {
                            method: 'PATCH',
                            headers: authHeaders(true),
                            credentials: 'same-origin',
                        });
                        if (!res.ok) {
                            const data = await readApiJson(res);
                            throw new Error(apiErrorMessage(data, storefrontL('Gagal konfirmasi pesanan.', 'Failed to confirm order.')));
                        }
                    }
                    emitEvomiEvent('history_updated');
                    await this.load({ silent: true });
                    this.showToast('Semua pesanan Anda telah berhasil diselesaikan.');
                },
            });
        },

        requestRemoveItem(item) {
            if (!item) return;
            this.openConfirm({
                variant: 'delete',
                title: storefrontL('Hapus Item', 'Delete Item'),
                message: 'Apakah Anda yakin ingin menghapus item ini dari riwayat belanja?',
                confirmText: storefrontL('Hapus', 'Delete'),
                action: async () => {
                    const res = await fetch(`/api/orders/${item.id}`, {
                        method: 'DELETE',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        const data = await readApiJson(res);
                        throw new Error(apiErrorMessage(data, storefrontL('Gagal menghapus item.', 'Failed to remove item.')));
                    }
                    emitEvomiEvent('history_updated');
                    const remaining = (this.group?.items || []).filter(
                        (i) => String(i.id) !== String(item.id),
                    );
                    if (remaining.length === 0) {
                        this.showToast('Seluruh item pesanan ini telah terhapus.');
                        window.setTimeout(() => {
                            softNavigate('/profile/history');
                        }, 600);
                        return;
                    }
                    await this.load({ silent: true });
                    this.showToast('Item pesanan telah dihapus.');
                },
            });
        },
    }));

    Alpine.data('evomiProfileChat', () => ({
        loading: true,
        refreshing: false,
        sending: false,
        draft: '',
        sendError: '',
        messages: [],
        showJumpLatest: false,
        hints: [
            'Cek status pesanan saya',
            'Rekomendasi aroma untuk saya',
            'Info pengiriman & ongkir',
        ],
        _poll: null,

        async init() {
            await this.load();
            this._poll = window.setInterval(() => this.load(true), 30000);
        },

        destroy() {
            if (this._poll) window.clearInterval(this._poll);
        },

        dayKey(iso) {
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) return '';
            return `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
        },

        dayLabel(iso) {
            const d = new Date(iso);
            if (Number.isNaN(d.getTime())) return '';
            const today = new Date();
            const yday = new Date();
            yday.setDate(today.getDate() - 1);
            if (this.dayKey(iso) === this.dayKey(today.toISOString())) return 'Hari ini';
            if (this.dayKey(iso) === this.dayKey(yday.toISOString())) return 'Kemarin';
            return d.toLocaleDateString('id-ID', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            });
        },

        showDayDivider(index) {
            if (index === 0) return true;
            const prev = this.messages[index - 1];
            const cur = this.messages[index];
            return this.dayKey(prev?.createdAt) !== this.dayKey(cur?.createdAt);
        },

        isConsecutive(index) {
            if (index === 0) return false;
            const prev = this.messages[index - 1];
            const cur = this.messages[index];
            return prev?.type === cur?.type;
        },

        onThreadScroll() {
            const el = this.$refs.thread;
            if (!el) return;
            const dist = el.scrollHeight - el.scrollTop - el.clientHeight;
            this.showJumpLatest = dist > 120;
        },

        jumpLatest() {
            const el = this.$refs.thread;
            if (!el) return;
            el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
            this.showJumpLatest = false;
        },

        useHint(hint) {
            this.draft = hint;
            this.$nextTick(() => this.$refs.composer?.focus());
        },

        async load(silent = false) {
            const user = getAuthUser();
            if (!user?.email) {
                window.location.replace('/login');
                return;
            }
            if (!silent) this.loading = true;
            else this.refreshing = true;
            try {
                const res = await fetch(`/api/contact?email=${encodeURIComponent(user.email)}`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await readApiJson(res);
                const raw = Array.isArray(data) ? data : data.data || data.messages || [];
                const bubbles = [];
                for (const row of raw) {
                    if (row.type && (row.text || row.message)) {
                        bubbles.push({
                            id: String(row.id || `${row.type}-${row.created_at}`),
                            type: row.type === 'admin' ? 'admin' : 'user',
                            text: row.text || row.message || '',
                            createdAt: row.created_at,
                            subject: row.subject || '',
                            isReadByAdmin: Boolean(row.isReadByAdmin ?? row.is_read_by_admin),
                            isNew: Boolean(row.isNew),
                            timeLabel: new Date(row.created_at).toLocaleString('id-ID', {
                                hour: '2-digit',
                                minute: '2-digit',
                            }),
                        });
                        continue;
                    }
                    if (row.message && row.message !== '[Percakapan dimulai oleh admin]') {
                        const readByAdmin =
                            row.is_read_by_admin === true ||
                            row.is_read_by_admin === 1 ||
                            row.is_read_by_admin === '1' ||
                            (Array.isArray(row.replies) && row.replies.length > 0);
                        bubbles.push({
                            id: `u-${row.id}`,
                            type: 'user',
                            text: row.message,
                            createdAt: row.created_at,
                            subject: row.subject || '',
                            isReadByAdmin: Boolean(readByAdmin),
                            isNew: false,
                            timeLabel: new Date(row.created_at).toLocaleString('id-ID', {
                                hour: '2-digit',
                                minute: '2-digit',
                            }),
                        });
                    }
                    const replies = row.replies || row.contact_replies || [];
                    for (const rep of replies) {
                        const unread =
                            !(
                                rep.is_read_by_user === true ||
                                rep.is_read_by_user === 1 ||
                                rep.is_read_by_user === '1'
                            );
                        bubbles.push({
                            id: `a-${rep.id}`,
                            type: 'admin',
                            text: rep.reply_message || rep.message || rep.reply || rep.text || '',
                            createdAt: rep.created_at,
                            subject: '',
                            isReadByAdmin: true,
                            isNew: unread,
                            timeLabel: new Date(rep.created_at).toLocaleString('id-ID', {
                                hour: '2-digit',
                                minute: '2-digit',
                            }),
                        });
                    }
                }
                bubbles.sort(
                    (a, b) => new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(),
                );
                this.messages = bubbles;

                await fetch('/api/contact/mark-read', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({ email: user.email }),
                }).catch(() => {});
                emitEvomiEvent('messages_read');

                if (!silent) {
                    this.$nextTick(() => this.jumpLatest());
                }
            } catch {
                /* keep previous */
            } finally {
                this.loading = false;
                this.refreshing = false;
            }
        },

        async send() {
            const text = (this.draft || '').trim();
            if (!text || this.sending) return;
            const user = getAuthUser();
            if (!user?.email) return;

            this.sending = true;
            this.sendError = '';
            const pendingId = `pending-${Date.now()}`;
            this.messages.push({
                id: pendingId,
                type: 'user',
                text,
                createdAt: new Date().toISOString(),
                subject: 'Pesan Dukungan Pelanggan',
                isReadByAdmin: false,
                isNew: false,
                pending: true,
                timeLabel: new Date().toLocaleString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                }),
            });
            this.draft = '';
            this.$nextTick(() => this.jumpLatest());

            try {
                const res = await fetch('/api/contact', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        name: user.name || user.nama_lengkap || user.email,
                        email: user.email,
                        subject: 'Pesan Dukungan Pelanggan',
                        message: text,
                    }),
                });
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Gagal mengirim pesan.', 'Failed to send message.')));
                await this.load(true);
            } catch (err) {
                this.messages = this.messages.filter((m) => m.id !== pendingId);
                this.draft = text;
                this.sendError =
                    err instanceof Error ? err.message : storefrontL('Gagal mengirim pesan. Coba lagi ya.', 'Failed to send message. Please try again.');
            } finally {
                this.sending = false;
            }
        },
    }));

    Alpine.data('evomiCheckout', () => ({
        loading: true,
        processing: false,
        fatalError: '',
        formError: '',
        type: 'buynow',
        brand: DEFAULT_THEME_BLUE,
        items: [],
        kurirs: [],
        selectedKurir: null,
        paymentMethod: 'cod',
        promoDiscount: 0,
        orderNote: '',
        editingAddress: false,
        savingAddress: false,
        form: { name: '', email: '', phone: '', address: '' },
        draft: { name: '', email: '', phone: '', address: '' },
        modal: { open: false, type: 'success', title: '', message: '' },
        completedOrderId: '',

        get hasAddress() {
            return Boolean(
                this.form.name?.trim() &&
                    this.form.email?.trim() &&
                    this.form.phone?.trim() &&
                    this.form.address?.trim(),
            );
        },

        applyAddress(next, { openEditorIfIncomplete = true } = {}) {
            const normalized = {
                name: String(next?.name || '').trim(),
                email: String(next?.email || '').trim(),
                phone: String(next?.phone || '').trim(),
                address: String(next?.address || '').trim(),
            };
            this.form = { ...normalized };
            this.draft = { ...normalized };
            if (openEditorIfIncomplete) {
                this.editingAddress = !this.hasAddress;
            }
        },

        addressFromUser(user) {
            if (!user || typeof user !== 'object') {
                return { name: '', email: '', phone: '', address: '' };
            }
            return {
                name: String(user.name || user.nama_lengkap || user.nama || '').trim(),
                email: String(user.email || '').trim(),
                phone: String(user.phone || user.no_hp || '').trim(),
                address: String(
                    user.alamat_lengkap || user.alamat || user.address || '',
                ).trim(),
            };
        },

        syncAuthUserLocal(userPatch) {
            try {
                const prev = getAuthUser() || {};
                const merged = { ...prev, ...userPatch };
                setAuthSession(getAuthToken(), merged);
            } catch {
                /* ignore */
            }
        },

        get itemCount() {
            return this.items.reduce((sum, i) => sum + Number(i.quantity || 0), 0);
        },

        get productSubtotal() {
            const raw = this.items.reduce((sum, i) => sum + Number(i.price) * Number(i.quantity), 0);
            return Math.max(raw - this.promoDiscount, 0);
        },

        get shippingCost() {
            return Number(this.selectedKurir?.harga || 0);
        },

        get total() {
            return Math.max(this.productSubtotal + this.shippingCost, 0);
        },

        get courierLabel() {
            if (!this.selectedKurir) return '';
            return `${this.selectedKurir.nama || ''}${this.selectedKurir.jenis ? ' ' + this.selectedKurir.jenis : ''}`.trim();
        },

        get shippingEtaLabel() {
            const days = Number(this.selectedKurir?.estimasi_hari || 3);
            const date = new Date();
            date.setDate(date.getDate() + (Number.isFinite(days) && days > 0 ? days : 3));
            return `Estimasi tiba ${date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}`;
        },

        formatPrice(value) {
            return formatRupiah(value);
        },

        itemUnitPrice(item) {
            if (this.items.length === 1 && item.quantity > 0 && this.promoDiscount > 0) {
                return this.productSubtotal / item.quantity;
            }
            return Number(item.price) || 0;
        },

        async boot() {
            const params = new URLSearchParams(window.location.search);
            this.type = (params.get('type') || 'buynow').toLowerCase();
            this.promoDiscount = Math.max(0, Number(params.get('productDiscount') || 0));

            try {
                await Promise.all([this.loadKurirs(params.get('kurirId')), this.loadItems(params)]);
                await this.prefillProfile();
            } catch (err) {
                this.fatalError = err instanceof Error ? err.message : storefrontL('Gagal memuat checkout.', 'Failed to load checkout.');
            } finally {
                this.loading = false;
                applyProductTheme(this.brand);
            }
        },

        async loadKurirs(preferredId) {
            const res = await fetch('/api/kurirs', { headers: { Accept: 'application/json' } });
            const data = await readApiJson(res);
            const list = Array.isArray(data) ? data : data.data || [];
            this.kurirs = list;
            if (!this.kurirs.length) return;
            const preferred = this.kurirs.find((k) => String(k.id) === String(preferredId));
            this.selectedKurir = preferred || this.kurirs[0];
        },

        async loadItems(params) {
            if (this.type === 'cart') {
                if (!getAuthToken()) {
                    window.location.replace('/login');
                    throw new Error(storefrontL('Login diperlukan untuk checkout keranjang.', 'Login required for cart checkout.'));
                }
                const res = await fetch('/api/carts?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    window.location.replace('/login');
                    throw new Error(storefrontL('Sesi berakhir.', 'Session expired.'));
                }
                const data = await readApiJson(res);
                const list = Array.isArray(data) ? data : data.data || [];
                this.items = list.map((row) => ({
                    id: row.id,
                    product_id: row.product_id || row.product?.id,
                    title: productTitle(row.product),
                    price: productPrice(row.product),
                    quantity: Number(row.quantity || 1),
                    stock: Number(row.product?.quantity ?? row.product?.stock ?? 99),
                    image: productImage(row.product),
                    personality_type: row.product?.personality_type || '',
                })).filter((i) => i.product_id);
                if (!this.items.length) throw new Error(storefrontL('Keranjang kosong.', 'Cart is empty.'));
                this.brand = DEFAULT_THEME_BLUE;
                return;
            }

            const productId = Number(params.get('productId'));
            const qty = Math.max(1, Number(params.get('qty') || 1));
            const unitPrice = Number(params.get('unitPrice') || 0);
            if (!productId) throw new Error(storefrontL('Produk checkout tidak valid.', 'Invalid checkout product.'));

            const res = await fetch(`/api/products/${productId}`, { headers: { Accept: 'application/json' } });
            const data = await readApiJson(res);
            const product = data?.data || data;
            if (!res.ok || !product?.id) throw new Error(storefrontL('Produk tidak ditemukan.', 'Product not found.'));

            this.items = [{
                id: `buynow-${product.id}`,
                product_id: product.id,
                title: productTitle(product),
                price: unitPrice > 0 ? unitPrice : productPrice(product),
                quantity: qty,
                stock: Number(product.quantity ?? product.stock ?? 99),
                image: productImage(product),
                personality_type: product.personality_type || '',
            }];

            this.brand = product.color || DEFAULT_THEME_BLUE;
            applyProductTheme(this.brand);
        },

        async prefillProfile() {
            if (!getAuthToken()) {
                // Guest: form kosong → tampilkan editor
                this.editingAddress = !this.hasAddress;
                this.draft = { ...this.form };
                return;
            }

            try {
                const res = await fetch('/api/user/profile', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.ok) {
                    const data = await readApiJson(res);
                    const user = data?.data || data?.user || data;
                    this.applyAddress(this.addressFromUser(user));
                    this.syncAuthUserLocal(user);
                    return;
                }
            } catch {
                /* fallback localStorage */
            }

            this.applyAddress(this.addressFromUser(getAuthUser()));
        },

        startEditAddress() {
            this.draft = { ...this.form };
            this.editingAddress = true;
            this.formError = '';
        },

        cancelAddressEdit() {
            if (!this.hasAddress) return;
            this.editingAddress = false;
            this.draft = { ...this.form };
            this.formError = '';
        },

        async saveAddress() {
            const next = {
                name: (this.draft.name || '').trim(),
                email: (this.draft.email || '').trim(),
                phone: (this.draft.phone || '').trim(),
                address: (this.draft.address || '').trim(),
            };

            if (!next.name || !next.email || !next.phone || !next.address) {
                this.formError = 'Lengkapi nama, email, telepon, dan alamat.';
                return false;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(next.email)) {
                this.formError = 'Format email tidak valid.';
                return false;
            }

            this.form = { ...next };
            this.draft = { ...next };
            this.editingAddress = false;
            this.formError = '';

            const token = getAuthToken();
            if (!token) return true;

            try {
                this.savingAddress = true;
                const fd = new FormData();
                fd.append('name', next.name);
                fd.append('nama_lengkap', next.name);
                fd.append('email', next.email);
                fd.append('phone', next.phone);
                fd.append('alamat_lengkap', next.address);

                const headers = authHeaders(false);
                delete headers['Content-Type'];

                const res = await fetch('/api/user/profile', {
                    method: 'POST',
                    headers,
                    credentials: 'same-origin',
                    body: fd,
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(apiErrorMessage(data, storefrontL('Gagal menyimpan alamat ke profil.', 'Failed to save address to profile.')));
                }
                const user = data?.data || data?.user || {
                    name: next.name,
                    nama_lengkap: next.name,
                    email: next.email,
                    phone: next.phone,
                    alamat_lengkap: next.address,
                };
                this.syncAuthUserLocal(user);
            } catch (err) {
                // Alamat tetap dipakai di checkout; gagal sync profil tidak memblokir
                console.error(err);
            } finally {
                this.savingAddress = false;
            }

            return true;
        },

        selectKurir(kurir) {
            this.selectedKurir = kurir;
        },

        selectKurirById(id) {
            const found = this.kurirs.find((k) => String(k.id) === String(id));
            if (found) this.selectedKurir = found;
        },

        updateQty(id, delta) {
            this.items = this.items.map((item) => {
                if (item.id !== id) return item;
                const max = Math.max(1, Number(item.stock) || 1);
                const next = Math.min(max, Math.max(1, Number(item.quantity) + delta));
                return { ...item, quantity: next };
            });
        },

        async validateForm() {
            if (this.editingAddress) {
                const saved = await this.saveAddress();
                if (!saved) return false;
            }
            if (!this.hasAddress) {
                this.formError = 'Lengkapi nama, email, telepon, dan alamat.';
                this.editingAddress = true;
                this.draft = { ...this.form };
                return false;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                this.formError = 'Format email tidak valid.';
                return false;
            }
            if (!this.selectedKurir) {
                this.formError = 'Pilih kurir pengiriman.';
                return false;
            }
            if (!this.items.length) {
                this.formError = storefrontL('Tidak ada item untuk di-checkout.', 'No items to checkout.');
                return false;
            }
            this.formError = '';
            return true;
        },

        makeInvoiceId() {
            return `INV-${Date.now()}-${Math.floor(Math.random() * 9000 + 1000)}`;
        },

        async submitCheckout() {
            if (this.processing) return;
            if (!(await this.validateForm())) return;

            const token = getAuthToken();
            const isGuestBuyNow = this.type === 'buynow' && !token;
            if (this.type === 'cart' && !token) {
                window.location.href = '/login';
                return;
            }

            this.processing = true;
            const invoiceId = this.makeInvoiceId();
            const paymentLabel = this.paymentMethod === 'qris' ? 'QRIS' : 'Cash on Delivery';

            const payload = {
                invoice_id: invoiceId,
                payment_method: paymentLabel,
                payment_status: this.paymentMethod === 'qris' ? 'success' : 'pending',
                total: this.total,
                shipping_cost: this.shippingCost,
                promo_discount: this.promoDiscount,
                recipient_name: this.form.name,
                recipient_phone: this.form.phone,
                recipient_address: this.form.address,
                courier: this.courierLabel,
                note: this.orderNote || undefined,
                items: this.items.map((item) => ({
                    product_id: Number(item.product_id),
                    quantity: Number(item.quantity),
                    price: Number(item.price),
                    title: item.title,
                })),
            };

            try {
                if (isGuestBuyNow) {
                    if (payload.items.length !== 1) {
                        throw new Error(storefrontL('Checkout tamu hanya untuk 1 produk (beli langsung).', 'Guest checkout is only for single-product buy now.'));
                    }
                    const res = await fetch('/api/checkout/guest', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            ...payload,
                            guest_email: this.form.email,
                        }),
                    });
                    const data = await readApiJson(res);
                    if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Checkout gagal.', 'Checkout failed.')));
                } else {
                    const res = await fetch('/api/checkout', {
                        method: 'POST',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            ...payload,
                            guest_email: this.form.email,
                        }),
                    });
                    const data = await readApiJson(res);
                    if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Checkout gagal.', 'Checkout failed.')));

                    try {
                        await fetch('/api/trackings', {
                            method: 'POST',
                            headers: authHeaders(true),
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                order_id: invoiceId,
                                status: storefrontL('Menunggu Konfirmasi', 'Awaiting Confirmation'),
                                courier: this.courierLabel,
                                recipient_name: this.form.name,
                                recipient_phone: this.form.phone,
                                recipient_address: this.form.address,
                            }),
                        });
                    } catch {
                        /* tracking optional if checkout already created it */
                    }
                    emitEvomiEvent('cart_updated');
                }

                this.completedOrderId = invoiceId;
                this.modal = {
                    open: true,
                    type: 'success',
                    title: storefrontL('Checkout Berhasil!', 'Checkout Successful!'),
                    message: `Pesanan ${invoiceId} sudah dibuat. Kamu akan kembali ke beranda.`,
                };
            } catch (err) {
                this.modal = {
                    open: true,
                    type: 'error',
                    title: storefrontL('Checkout Gagal', 'Checkout Failed'),
                    message: err instanceof Error ? err.message : 'Terjadi kesalahan.',
                };
            } finally {
                this.processing = false;
            }
        },

        closeModal() {
            const wasSuccess = this.modal.type === 'success';
            this.modal.open = false;
            if (wasSuccess) {
                window.location.href = '/';
            }
        },
    }));
});

Alpine.start();

async function softNavigate(href, { push = true, navIndex = null, force = false } = {}) {
    if (softNavBusy) return;

    const url = new URL(href, window.location.origin);
    const samePath = pathKey(url) === pathKey(window.location.href);
    const nav = window.__evomiNav;

    if (url.origin !== window.location.origin) {
        window.location.href = href;
        return;
    }

    // Dashboard uses a separate Blade layout — always hard navigate
    if (isDashboardPath(url.pathname) || isDashboardPath(window.location.pathname)) {
        window.location.href = url.pathname + url.search + url.hash;
        return;
    }

    // Same page + hash only (e.g. Tentang on beranda)
    if (!force && samePath && url.hash) {
        if (nav && navIndex !== null && !Number.isNaN(navIndex)) {
            nav.setActive(navIndex, true);
        }
        if (push) {
            history.pushState({ soft: true }, '', url.pathname + url.search + url.hash);
        }
        document.querySelector(url.hash)?.scrollIntoView({ behavior: 'smooth' });
        return;
    }

    // Same full URL — no-op (unless force, e.g. locale switch)
    if (!force && samePath && !url.hash && !window.location.hash) {
        if (nav && navIndex !== null && !Number.isNaN(navIndex)) {
            nav.setActive(navIndex, true);
        }
        return;
    }

    softNavBusy = true;

    if (nav && navIndex !== null && !Number.isNaN(navIndex)) {
        nav.setActive(navIndex, true);
    }

    const main = document.getElementById('evomi-main');
    const footerWrap = document.getElementById('evomi-footer-wrap');

    // Set underlay BEFORE fade — beranda needs blue, not white body flash
    setSurfaceForPath(url.pathname);

    const fromProfile = isProfilePath(window.location.pathname);
    const toProfile = isProfilePath(url.pathname);
    const profileShell = document.querySelector('.evomi-profile-shell[data-profile-page]');

    // Profile → profile: keep sidebar mounted so the blue pill can slide like the navbar
    if (fromProfile && toProfile && profileShell && main) {
        try {
            const fetchPromise = fetch(url.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                credentials: 'same-origin',
            });

            const content = profileShell.querySelector('[data-profile-content]');
            // History detail: instant swap — no leave fade / loading-screen feel
            const skipProfileAnim =
                isHistoryDetailPath(url.pathname) ||
                isHistoryDetailPath(window.location.pathname);

            if (content && !skipProfileAnim) {
                content.classList.add('profile-content-panel', 'is-leaving');
            }

            const [res] = await Promise.all([
                fetchPromise,
                wait(skipProfileAnim ? 0 : 280),
            ]);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextMain = doc.getElementById('evomi-main');
            const nextShell = nextMain?.querySelector('.evomi-profile-shell[data-profile-page]');
            const nextContent = nextShell?.querySelector('[data-profile-content]');
            const nextTitle = doc.querySelector('title')?.textContent;
            const nextMenu = nextShell?.getAttribute('data-active-menu') || 'settings';

            if (!nextMain || !nextContent || !content) {
                throw new Error('profile soft-nav fallback');
            }

            if (window.Alpine?.destroyTree) {
                Alpine.destroyTree(content);
            }
            content.innerHTML = nextContent.innerHTML;
            if (nextTitle) document.title = nextTitle;
            profileShell.setAttribute('data-active-menu', nextMenu);

            if (window.Alpine?.initTree) {
                Alpine.initTree(content);
            }
            bindSoftLinks(content);

            if (window.__evomiProfileShell) {
                window.__evomiProfileShell.setActive(nextMenu, true);
            }

            if (push) {
                history.pushState({ soft: true }, nextTitle || '', url.pathname + url.search + url.hash);
            }

            window.scrollTo({ top: 0, left: 0 });

            content.classList.remove('is-leaving');
            if (!skipProfileAnim) {
                content.classList.add('is-entering');
                await waitFrames(2);
                requestAnimationFrame(() => {
                    content.classList.remove('is-entering');
                });
                await wait(320);
            }
            softNavBusy = false;
            return;
        } catch (err) {
            // Fall through to full soft-nav if partial swap fails
            if (String(err?.message || err) !== 'profile soft-nav fallback') {
                console.warn(err);
            }
            const content = profileShell.querySelector('[data-profile-content]');
            content?.classList.remove('is-leaving', 'is-entering');
        }
    }

    try {
        // Fetch in parallel with leave animation (SPA feel, no loader)
        const fetchPromise = fetch(url.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            credentials: 'same-origin',
        });

        // Artikel detail (and similar): instant swap — no leave fade / loading-screen feel
        const skipPageAnim =
            shouldSkipPageLoadingFeel(url.pathname) ||
            shouldSkipPageLoadingFeel(window.location.pathname);

        if (!skipPageAnim) {
            main.classList.add('is-leaving');
        }

        const [res] = await Promise.all([
            fetchPromise,
            wait(skipPageAnim ? 0 : 340),
        ]);

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextMain = doc.getElementById('evomi-main');
        const nextFooter = doc.getElementById('evomi-footer-wrap');
        const nextTitle = doc.querySelector('title')?.textContent;

        if (!nextMain) {
            window.location.href = href;
            return;
        }

        const apply = () => {
            if (window.Alpine?.destroyTree) {
                Alpine.destroyTree(main);
                if (footerWrap) Alpine.destroyTree(footerWrap);
            }

            const nextTheme =
                doc.body?.style.getPropertyValue('--evomi-theme')?.trim() ||
                nextFooter?.style.backgroundColor ||
                DEFAULT_THEME_BLUE;

            main.innerHTML = nextMain.innerHTML;
            if (footerWrap && nextFooter) {
                footerWrap.innerHTML = nextFooter.innerHTML;
                footerWrap.style.backgroundColor =
                    nextFooter.style.backgroundColor || nextTheme;
            }
            if (nextTitle) document.title = nextTitle;

            document.body?.style.setProperty('--evomi-theme', nextTheme);

            if (window.Alpine?.initTree) {
                Alpine.initTree(main);
                if (footerWrap) Alpine.initTree(footerWrap);
            }

            bindSoftLinks(footerWrap || document);
            bindSoftLinks(main);

            if (push) {
                history.pushState({ soft: true }, nextTitle || '', url.pathname + url.search + url.hash);
            }

            if (nav && (navIndex === null || Number.isNaN(navIndex))) {
                syncNavFromPath(url.pathname, url.hash);
            } else if (nav && url.hash === '#about') {
                nav.setActive(1, true);
            }

            window.scrollTo({ top: 0, left: 0 });

            if (nav) {
                nav.isNavHidden = false;
                nav.lastScrollY = 0;
            }

            if (isBelanjaDetailPath(url.pathname)) {
                applyProductTheme(nextTheme);
            } else {
                restoreProductTheme();
            }

            main.classList.remove('is-leaving');
            if (!skipPageAnim) {
                main.classList.add('is-entering');
            }
        };

        if (!skipPageAnim && document.startViewTransition) {
            await document.startViewTransition(apply).finished.catch(() => {});
        } else {
            apply();
        }

        await waitFrames(2);
        requestAnimationFrame(() => {
            main.classList.remove('is-entering');
        });
        await wait(skipPageAnim ? 0 : 420);
        // Re-measure pill after layout settles
        if (nav) {
            nav.moveIndicator(nav.activeIndex, true);
            if (typeof nav.syncSpacer === 'function') nav.syncSpacer();
        }

        if (isBerandaPath(url.pathname)) {
            initBerandaMotion(main);
        } else if (typeof berandaMotionCleanup === 'function') {
            berandaMotionCleanup();
            berandaMotionCleanup = null;
        }

        if (url.hash) {
            requestAnimationFrame(() => {
                document.querySelector(url.hash)?.scrollIntoView({ behavior: 'smooth' });
            });
        }
    } catch (err) {
        console.error(err);
        window.location.href = href;
    } finally {
        softNavBusy = false;
    }
}

window.softNavigate = softNavigate;

function syncNavFromPath(pathname, hash = '') {
    const nav = window.__evomiNav;
    if (!nav) return;
    const track = nav.$refs?.track;
    if (!track) return;

    if (hash === '#about') {
        nav.setActive(1, true);
        return;
    }

    const p = pathname.replace(/\/$/, '') || '/';
    let best = -1;
    track.querySelectorAll('[data-nav-index]').forEach((el) => {
        const match = el.dataset.navMatch || '';
        const i = Number(el.dataset.navIndex);
        if (match === '#about') return;
        // Checkout is part of belanja flow — keep Belanja active
        if (match === '/belanja' && (p.startsWith('/belanja') || p.startsWith('/checkout'))) {
            best = i;
            return;
        }
        if (match === '/' && (p === '/' || p === '')) best = i;
        if (match !== '/' && match !== '#about' && match !== '/belanja' && p.startsWith(match)) best = i;
    });

    if (best < 0) {
        nav.clearActive(true);
    } else {
        nav.setActive(best, true);
    }
}

function bindSoftLinks(root = document) {
    if (!root) return;
    root.querySelectorAll('[data-soft-nav], .nav-soft, a.nav-transit').forEach((el) => {
        if (el.dataset.softBound) return;
        el.dataset.softBound = '1';

        el.addEventListener('click', (e) => {
            if (e.defaultPrevented) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            if (el.target === '_blank') return;

            const href = el.getAttribute('href');
            if (!href || href.startsWith('mailto:') || href.startsWith('tel:')) return;
            if (href.startsWith('http') && !href.startsWith(window.location.origin)) return;

            e.preventDefault();
            const nav = window.__evomiNav;
            if (nav) nav.open = false;

            const index = el.dataset.navIndex !== undefined ? Number(el.dataset.navIndex) : null;
            softNavigate(href, { navIndex: index });
        });
    });
}

/* ——— Dashboard: client-side menu switching (parity with Next.js <Link>) ———
 * The shell (sidebar, theme, locale, auth gate) stays mounted; only #admin-page
 * swaps, so switching menus never re-runs the access check or flashes the layout.
 */
const ADMIN_NAV_ACTIVE = ['admin-nav-active', 'bg-gray-900', 'text-white', 'shadow-md', 'shadow-gray-900/10'];
const ADMIN_NAV_IDLE = ['text-gray-500', 'hover:bg-gray-50', 'hover:text-gray-700'];
const ADMIN_PILL_ACTIVE = ['bg-gray-900', 'text-white'];
const ADMIN_PILL_IDLE = ['bg-gray-100', 'text-gray-600'];

let adminNavToken = 0;
const adminPageCache = new Map();

function adminMenuKeyFromPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    if (p === '/dashboard') return 'dashboard';
    return p.match(/^\/dashboard\/([^/]+)/)?.[1] || 'dashboard';
}

/** Centered spinner while the next menu loads, mirroring the Next.js page loading state */
function showAdminPageLoader(page) {
    page.classList.add('is-hidden');
    document.getElementById('admin-page-loading')?.classList.add('is-visible');
    document.querySelector('.admin-shell main')?.setAttribute('aria-busy', 'true');
}

function hideAdminPageLoader(page) {
    document.getElementById('admin-page-loading')?.classList.remove('is-visible');
    page.classList.remove('is-hidden');
    document.querySelector('.admin-shell main')?.removeAttribute('aria-busy');
}

/**
 * A page's own loading block is still on: Alpine writes the inline display on
 * x-show elements, which stays readable while #admin-page is hidden.
 */
function adminPageStillLoading(page) {
    for (const el of page.querySelectorAll('[x-show="loading"]')) {
        if (el.style.display !== 'none') return true;
    }
    for (const tpl of page.querySelectorAll('template[x-if="loading"]')) {
        if (tpl.nextElementSibling?.querySelector?.('.animate-spin')) return true;
    }
    return false;
}

/**
 * Hold the centered spinner until the freshly mounted page finished its own
 * initial fetch, so a menu switch never shows two spinners in a row.
 */
async function waitForAdminPageData(page, token, timeout = 4000) {
    // Components flip `loading` inside init(), which may run on the next tick
    await waitFrames(2);
    await wait(40);

    const deadline = Date.now() + timeout;
    while (Date.now() < deadline && token === adminNavToken) {
        if (!adminPageStillLoading(page)) return;
        await wait(60);
    }
}

function setAdminActiveMenu(key, { scrollPill = true } = {}) {
    document.querySelectorAll('[data-admin-nav-desktop] a[data-admin-nav]').forEach((el) => {
        const active = el.dataset.adminNav === key;
        el.classList.remove(...(active ? ADMIN_NAV_IDLE : ADMIN_NAV_ACTIVE));
        el.classList.add(...(active ? ADMIN_NAV_ACTIVE : ADMIN_NAV_IDLE));
        if (active) el.setAttribute('aria-current', 'page');
        else el.removeAttribute('aria-current');

        const icon = el.querySelector('svg');
        if (icon) {
            icon.classList.toggle('text-white', active);
            icon.classList.toggle('text-gray-400', !active);
        }
    });

    document.querySelectorAll('[data-admin-nav-mobile] a[data-admin-nav]').forEach((el) => {
        const active = el.dataset.adminNav === key;
        el.classList.remove(...(active ? ADMIN_PILL_IDLE : ADMIN_PILL_ACTIVE));
        el.classList.add(...(active ? ADMIN_PILL_ACTIVE : ADMIN_PILL_IDLE));
        if (active) {
            el.setAttribute('aria-current', 'page');
            if (scrollPill) {
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        } else {
            el.removeAttribute('aria-current');
        }
    });
}

function fetchAdminPage(href) {
    const cached = adminPageCache.get(href);
    if (cached) return cached;

    const request = fetch(href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        credentials: 'same-origin',
    }).then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.text();
    });

    adminPageCache.set(href, request);
    request.catch(() => adminPageCache.delete(href));
    // Blade only ships the shell (data arrives via API), so a short-lived cache is safe
    setTimeout(() => adminPageCache.delete(href), 30000);

    return request;
}

async function adminSoftNavigate(href, { push = true } = {}) {
    const url = new URL(href, window.location.origin);
    const page = document.getElementById('admin-page');

    if (url.origin !== window.location.origin || !isDashboardPath(url.pathname) || !page) {
        window.location.href = href;
        return;
    }

    const targetKey = adminMenuKeyFromPath(url.pathname);

    if (pathKey(url) === pathKey(window.location.href) && url.search === window.location.search) {
        setAdminActiveMenu(targetKey);
        window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
        return;
    }

    const token = ++adminNavToken;
    setAdminActiveMenu(targetKey);

    const request = fetchAdminPage(url.href);
    page.classList.add('is-leaving');

    try {
        await wait(100);
        if (token !== adminNavToken) return;

        showAdminPageLoader(page);
        window.scrollTo({ top: 0, left: 0 });

        // Floor the spinner time so a fast/prefetched page never flashes it
        const [html] = await Promise.all([request, wait(200)]);
        if (token !== adminNavToken) return;

        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextPage = doc.getElementById('admin-page');
        if (!nextPage) throw new Error('admin soft-nav fallback');

        const nextTitle = doc.querySelector('title')?.textContent;

        if (window.Alpine?.destroyTree) Alpine.destroyTree(page);
        page.innerHTML = nextPage.innerHTML;
        page.dataset.adminPage = nextPage.dataset.adminPage || targetKey;
        if (nextTitle) document.title = nextTitle;
        // A modal on the previous page may have locked scrolling
        document.body.style.overflow = '';

        if (window.Alpine?.initTree) Alpine.initTree(page);

        await waitForAdminPageData(page, token);
        if (token !== adminNavToken) return;

        page.classList.add('is-entering');
        page.classList.remove('is-leaving');
        hideAdminPageLoader(page);

        setAdminActiveMenu(page.dataset.adminPage);

        if (push) {
            history.pushState({ admin: true }, nextTitle || '', url.pathname + url.search + url.hash);
        }

        window.scrollTo({ top: 0, left: 0 });

        await waitFrames(2);
        requestAnimationFrame(() => page.classList.remove('is-entering'));
    } catch (err) {
        if (token !== adminNavToken) return;
        console.warn(err);
        hideAdminPageLoader(page);
        window.location.href = url.pathname + url.search + url.hash;
    }
}

window.adminSoftNavigate = adminSoftNavigate;

function bindAdminNav() {
    const page = document.getElementById('admin-page');
    if (!page) return;

    setAdminActiveMenu(page.dataset.adminPage || adminMenuKeyFromPath(window.location.pathname), {
        scrollPill: false,
    });

    const linkFrom = (event) =>
        event.target?.closest?.('a[data-admin-nav], a[data-admin-nav-link]') || null;

    document.addEventListener('pointerenter', (e) => {
        const link = linkFrom(e);
        const href = link?.getAttribute('href');
        if (href) fetchAdminPage(new URL(href, window.location.origin).href).catch(() => {});
    }, true);

    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;

        const link = linkFrom(e);
        const href = link?.getAttribute('href');
        if (!href || link.target === '_blank') return;

        const url = new URL(href, window.location.origin);
        if (url.origin !== window.location.origin || !isDashboardPath(url.pathname)) return;

        e.preventDefault();
        adminSoftNavigate(url.href);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setSurfaceForPath(window.location.pathname);
    bindSoftLinks(document);
    initBerandaMotion(document);
    bindAdminNav();
});

window.addEventListener('popstate', () => {
    if (isDashboardPath(window.location.pathname) && document.getElementById('admin-page')) {
        adminSoftNavigate(window.location.href, { push: false });
        return;
    }
    softNavigate(window.location.href, { push: false });
});

/* ——— Beranda: hero scroll parallax + section reveal/parallax ——— */
let berandaMotionCleanup = null;

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function lerp(a, b, t) {
    return a + (b - a) * t;
}

function clamp01(n) {
    return Math.min(1, Math.max(0, n));
}

function initBerandaMotion(root = document) {
    if (typeof berandaMotionCleanup === 'function') {
        berandaMotionCleanup();
        berandaMotionCleanup = null;
    }

    const scope = root.querySelector?.('.hero-section')
        ? root
        : document.getElementById('evomi-main') || document;

    const hero = scope.querySelector('.hero-section');
    if (!hero && !scope.querySelector('[data-reveal], [data-parallax]')) {
        return;
    }

    if (prefersReducedMotion()) {
        scope.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-revealed'));
        if (hero) {
            hero.classList.add('is-hero-ready', 'is-hero-float-live');
        }
        return;
    }

    const cleanups = [];

    // Entrance after loading screen (or immediately on soft-nav back to beranda)
    if (hero) {
        let floatTimer = 0;
        let startTimer = 0;
        let played = false;

        const playEntrance = () => {
            if (played) return;
            played = true;
            hero.classList.add('is-hero-resetting');
            hero.classList.remove('is-hero-ready', 'is-hero-float-live');
            // Force reflow so entrance restarts from opacity 0 / offset transform
            void hero.offsetWidth;
            requestAnimationFrame(() => {
                hero.classList.remove('is-hero-resetting');
                void hero.offsetWidth;
                requestAnimationFrame(() => {
                    hero.classList.add('is-hero-ready');
                    floatTimer = window.setTimeout(() => {
                        hero.classList.add('is-hero-float-live');
                    }, 1600);
                });
            });
        };

        const loader = document.getElementById('evomi-loader');
        const loaderDone =
            !loader ||
            loader.classList.contains('is-hidden') ||
            loader.classList.contains('is-fading') ||
            !document.documentElement.classList.contains('evomi-loading');

        if (loaderDone) {
            // Soft-nav / already past loader — slight beat then stagger in
            startTimer = window.setTimeout(playEntrance, 80);
        } else {
            const onLoaderDone = () => playEntrance();
            window.addEventListener('evomi:loader-done', onLoaderDone, { once: true });
            // Safety if the event was missed (e.g. race with module load)
            startTimer = window.setTimeout(() => {
                if (!hero.classList.contains('is-hero-ready')) playEntrance();
            }, LOADER_MAX_MS + 400);
            cleanups.push(() => window.removeEventListener('evomi:loader-done', onLoaderDone));
        }

        cleanups.push(() => {
            window.clearTimeout(floatTimer);
            window.clearTimeout(startTimer);
        });
    }

    // Hero: Next useScroll — progress 0→0.6 → opacity 1→0, y 0→-50
    const layer = hero?.querySelector('.hero-parallax-layer');
    if (hero && layer) {
        let ticking = false;

        const updateHero = () => {
            ticking = false;
            const rect = hero.getBoundingClientRect();
            const total = Math.max(hero.offsetHeight, 1);
            // offset ["start start", "end start"] → progress while hero exits upward
            const progress = clamp01(-rect.top / total);
            const t = clamp01(progress / 0.6);
            layer.style.setProperty('--hero-parallax-o', String(lerp(1, 0, t)));
            layer.style.setProperty('--hero-parallax-y', `${lerp(0, -50, t)}px`);
        };

        const onScroll = () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(updateHero);
            }
        };

        updateHero();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        cleanups.push(() => {
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onScroll);
            layer.style.removeProperty('--hero-parallax-o');
            layer.style.removeProperty('--hero-parallax-y');
        });
    }

    // Scroll reveal (Next whileInView)
    const revealEls = [...scope.querySelectorAll('[data-reveal]')];
    revealEls.forEach((el) => {
        const delay = el.dataset.revealDelay;
        if (delay) el.style.setProperty('--reveal-delay', `${delay}s`);
    });

    if (revealEls.length) {
        const io = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        el.classList.add('is-revealed');
                        const delayMs = (Number.parseFloat(el.dataset.revealDelay || '0') || 0) * 1000;
                        window.setTimeout(() => el.classList.add('is-parallax-live'), delayMs + 700);
                        io.unobserve(el);
                    }
                });
            },
            { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
        );
        revealEls.forEach((el) => io.observe(el));
        cleanups.push(() => io.disconnect());
    }

    // Section parallax: text slower, images faster via data-parallax speed
    const parallaxEls = [...scope.querySelectorAll('[data-parallax]')];
    if (parallaxEls.length) {
        let ticking = false;

        const updateParallax = () => {
            ticking = false;
            const vh = window.innerHeight || 1;
            const mid = vh * 0.5;

            parallaxEls.forEach((el) => {
                if (el.closest('.hero-parallax-layer')) return;
                const speed = Number.parseFloat(el.dataset.parallax || '0.1') || 0.1;
                const rect = el.getBoundingClientRect();
                const elMid = rect.top + rect.height * 0.5;
                const delta = (elMid - mid) * -speed * 0.35;
                el.style.setProperty('--parallax-y', `${delta.toFixed(2)}px`);
            });
        };

        const onScroll = () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(updateParallax);
            }
        };

        updateParallax();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        cleanups.push(() => {
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onScroll);
            parallaxEls.forEach((el) => el.style.removeProperty('--parallax-y'));
        });
    }

    berandaMotionCleanup = () => cleanups.forEach((fn) => fn());
}

window.initBerandaMotion = initBerandaMotion;
