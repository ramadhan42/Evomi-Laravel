import Alpine from 'alpinejs';

window.Alpine = Alpine;

let softNavBusy = false;

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
                label: 'Menunggu Konfirmasi',
                class: 'bg-orange-50 text-orange-600 border border-orange-100',
            };
        case 'pengemasan':
            return {
                label: 'Pengemasan',
                class: 'bg-purple-50 text-purple-600 border border-purple-100',
            };
        case 'dalam_perjalanan':
            return {
                label: 'Dalam Perjalanan',
                class: 'bg-blue-50 text-blue-600 border border-blue-100',
            };
        case 'diterima':
            return {
                label: 'Diterima',
                class: 'bg-emerald-50 text-emerald-600 border border-emerald-100',
            };
        case 'selesai':
            return {
                label: 'Selesai',
                class: 'bg-emerald-50 text-emerald-600 border border-emerald-100',
            };
        default:
            return {
                label: status || 'Diproses',
                class: 'bg-gray-50 text-gray-600 border border-gray-100',
            };
    }
}

function paymentStatusBadgeClass(status) {
    if (status === 'success') return 'bg-emerald-50 text-emerald-700 border border-emerald-100';
    if (status === 'cancelled') return 'bg-red-50 text-red-700 border border-red-100';
    return 'bg-amber-50 text-amber-700 border border-amber-100';
}

function paymentStatusLabel(status) {
    if (status === 'success') return 'Berhasil';
    if (status === 'cancelled') return 'Dibatalkan';
    return 'Pending';
}

function storageUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    return `/storage/${String(path).replace(/^\/+/, '')}`;
}

/** Only return a usable avatar URL; empty/null/"null" → null (show initial letter). */
function resolveAvatarUrl(path) {
    if (path == null) return null;
    const raw = String(path).trim();
    if (!raw || raw === 'null' || raw === 'undefined' || raw === '/') return null;
    if (/^(blob:|data:)/i.test(raw)) return raw;
    if (/^https?:\/\//i.test(raw)) return raw;
    const cleaned = raw.replace(/^\/+/, '');
    if (!cleaned || cleaned === 'storage' || cleaned === 'storage/') return null;
    return `/storage/${cleaned.replace(/^storage\//i, '')}`;
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

function productImage(product) {
    const path =
        product?.image_produk_belanja ||
        product?.image_1 ||
        product?.image ||
        '';
    const url = storageUrl(path);
    if (url) return url;

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

function groupOrdersByCreatedAt(orders) {
    const map = new Map();
    for (const order of orders || []) {
        const key = `${order.created_at}|${order.id}`;
        // Prefer grouping by identical created_at timestamp (checkout batch)
        const batchKey = String(order.created_at || order.id);
        if (!map.has(batchKey)) {
            map.set(batchKey, []);
        }
        map.get(batchKey).push(order);
    }

    return Array.from(map.entries()).map(([batchKey, items]) => {
        const first = items[0];
        const quantity = items.reduce((s, o) => s + (Number(o.quantity) || 0), 0);
        const subtotal = items.reduce((s, o) => s + (Number(o.total_price) || 0), 0);
        const shipping = items.reduce((s, o) => s + (Number(o.shipping_cost) || 0), 0);
        const promo = items.reduce((s, o) => s + (Number(o.promo_discount) || 0), 0);
        const total = items.reduce((s, o) => s + orderGrandTotal(o), 0);
        const status = first.status;
        const pay = normalizePaymentStatus(first.payment_status);
        const fulfill = fulfillmentStatusConfig(status);
        const extra = items.length > 1 ? ` (+${items.length - 1})` : '';

        return {
            groupId: first.id,
            batchKey,
            items: items.map((o) => ({
                ...o,
                title: productTitle(o.product),
                priceLabel: formatRupiah(productPrice(o.product) || Number(o.total_price) / Math.max(1, Number(o.quantity) || 1)),
                lineTotalLabel: formatRupiah(orderGrandTotal(o)),
                imageUrl: productImage(o.product),
            })),
            invoice: String(first.id),
            productTitle: productTitle(first.product) + extra,
            imageUrl: productImage(first.product),
            quantity,
            dateLabel: new Date(first.created_at).toLocaleString('id-ID', {
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
            paymentLabel: paymentStatusLabel(pay) === 'Berhasil'
                ? 'Pembayaran berhasil'
                : paymentStatusLabel(pay) === 'Dibatalkan'
                  ? 'Pembayaran dibatalkan'
                  : 'Pembayaran pending',
            paymentClass: paymentStatusBadgeClass(pay),
            paymentMethod: first.metode_pembayaran || '',
            canConfirm: String(status).toLowerCase() === 'dalam_perjalanan',
            canDelete: ['diterima', 'selesai'].includes(String(status).toLowerCase()),
        };
    });
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
    Alpine.data('evomiNavbar', (activeIndex = 0) => ({
        open: false,
        isNavHidden: false,
        activeIndex,
        lastScrollY: 0,
        _scrollTicking: false,
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

        get indicatorStyle() {
            return {
                transform: `translate3d(${this.indicator.left}px, 0, 0)`,
                width: `${Math.max(this.indicator.width, 0)}px`,
                opacity: this.indicator.opacity,
            };
        },

        init() {
            window.__evomiNav = this;
            this.lastScrollY = window.scrollY || document.documentElement.scrollTop || 0;
            this.readAuth();
            this._onAuthChange = () => this.readAuth();
            this._onBadgeRefresh = () => this.refreshBadges();
            window.addEventListener('auth-change', this._onAuthChange);
            window.addEventListener('storage', this._onAuthChange);
            window.addEventListener('cart_updated', this._onBadgeRefresh);
            window.addEventListener('wishlist_updated', this._onBadgeRefresh);
            window.addEventListener('history_updated', this._onBadgeRefresh);
            window.addEventListener('messages_read', this._onBadgeRefresh);

            this.$nextTick(() => {
                this.syncSpacer();
                this.moveIndicator(this.activeIndex, false);
                requestAnimationFrame(() => {
                    this.moveIndicator(this.activeIndex, true);
                    this.syncSpacer();
                });
                // Fonts/images can change header height after first paint
                window.setTimeout(() => this.syncSpacer(), 120);
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

            // Arcanisia-style: hide on scroll down, show on scroll up
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
                this.userAvatar = resolveAvatarUrl(user.avatar_profile);
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

            // Keep visible while mobile menu is open
            if (this.open) {
                this.isNavHidden = false;
                this.lastScrollY = y;
                return;
            }

            if (y > prev && y > 100) {
                this.isNavHidden = true;
            } else {
                this.isNavHidden = false;
            }

            this.lastScrollY = y;
        },

        setActive(index, animate = true) {
            // Tentang (#about) keeps Beranda pill active — same as Next isActive
            const pillIndex = index === 1 ? 0 : index;
            this.activeIndex = pillIndex;
            this.syncActiveClasses(pillIndex);
            this.moveIndicator(pillIndex, animate);
        },

        syncActiveClasses(pillIndex) {
            const roots = [this.$refs.track, this.$refs.mobileMenu].filter(Boolean);
            roots.forEach((root) => {
                root.querySelectorAll('[data-nav-index]').forEach((el) => {
                    const i = Number(el.dataset.navIndex);
                    const on = i === pillIndex;
                    el.classList.toggle('is-active', on);
                    el.classList.toggle('text-[var(--nav-color)]', on);
                    el.classList.toggle('text-white', !on);
                });
            });
        },

        moveIndicator(index, animate = true) {
            const track = this.$refs.track;
            if (!track) return;

            const items = track.querySelectorAll('[data-nav-index]');
            const item = items[index];
            if (!item) {
                this.indicator.opacity = 0;
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

        currentIndex: 0,
        quantity: 1,
        selectedKurir: null,
        showKurirList: false,
        showShareModal: false,
        isChatOpen: false,
        isCopied: false,
        isWishlisted: false,
        statusMessage: '',
        draft: '',
        chatBubbles: [],
        detailScrollHeight: null,
        alert: { show: false, message: '' },
        chatTemplates: ['Hai, barang ini ready?', 'Bisa dikirim hari ini?', 'Terima kasih'],
        actionBusy: false,
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
            applyProductTheme(this.accent);

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
        },

        destroy() {
            if (this._galleryTimer) window.clearInterval(this._galleryTimer);
            if (this._resizeObserver) this._resizeObserver.disconnect();
            if (this._onResize) window.removeEventListener('resize', this._onResize);
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
                this.requireLogin('Silakan login terlebih dahulu untuk menambah ke keranjang.');
                return;
            }
            this.actionBusy = true;
            this.statusMessage = 'Menambah...';
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
                if (!res.ok) {
                    throw new Error(apiErrorMessage(data, 'Gagal menambah ke keranjang.'));
                }
                this.statusMessage = 'Ditambahkan!';
                emitEvomiEvent('cart_updated');
                window.setTimeout(() => {
                    this.statusMessage = '';
                }, 1800);
            } catch (err) {
                this.statusMessage = '';
                this.requireLogin(err instanceof Error ? err.message : 'Gagal menambah ke keranjang.');
            } finally {
                this.actionBusy = false;
            }
        },

        async toggleWishlist() {
            if (!getAuthToken()) {
                this.requireLogin('Silakan login terlebih dahulu untuk menambah wishlist.');
                return;
            }
            if (this.actionBusy) return;
            this.actionBusy = true;
            try {
                if (this.isWishlisted && this.wishlistId) {
                    const res = await fetch(`/api/wishlists/${this.wishlistId}`, {
                        method: 'DELETE',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        const data = await readApiJson(res);
                        throw new Error(apiErrorMessage(data, 'Gagal menghapus wishlist.'));
                    }
                    this.isWishlisted = false;
                    this.wishlistId = null;
                } else {
                    const res = await fetch('/api/wishlists', {
                        method: 'POST',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                        body: JSON.stringify({ product_id: this.id }),
                    });
                    const data = await readApiJson(res);
                    if (!res.ok) {
                        throw new Error(apiErrorMessage(data, 'Gagal menambah wishlist.'));
                    }
                    this.isWishlisted = true;
                    this.wishlistId = data?.id || data?.data?.id || null;
                }
                emitEvomiEvent('wishlist_updated');
            } catch (err) {
                this.requireLogin(err instanceof Error ? err.message : 'Gagal mengubah wishlist.');
            } finally {
                this.actionBusy = false;
            }
        },

        async sendChat() {
            const text = (this.draft || '').trim();
            if (!text) return;
            if (!getAuthToken()) {
                this.requireLogin('Anda harus login terlebih dahulu untuk mengirim pesan ke admin.');
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
                    throw new Error(apiErrorMessage(data, 'Gagal mengirim pesan.'));
                }
                this.chatBubbles.push({
                    id: Date.now(),
                    type: 'user',
                    text,
                });
                this.draft = '';
                emitEvomiEvent('chat_updated');
            } catch (err) {
                this.requireLogin(err instanceof Error ? err.message : 'Gagal mengirim pesan.');
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

        get paged() {
            const start = (this.page - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },

        get resultLabel() {
            if (this.query.trim()) {
                return `${this.filtered.length} hasil untuk "${this.query.trim()}"`;
            }
            return `${this.articles.length} artikel tersedia`;
        },

        init() {
            this.$watch('query', () => {
                this.page = 1;
            });
        },

        formatDate(value) {
            try {
                return new Date(value).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                });
            } catch {
                return value;
            }
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
                    throw new Error(apiErrorMessage(data, 'Gagal mengirim pesan.'));
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
        result: null,

        get currentQuestion() {
            return this.questions[this.step] || null;
        },

        get progress() {
            if (!this.questions.length) return 0;
            return ((this.step + 1) / this.questions.length) * 100;
        },

        answer(option) {
            ['peaceful_calm', 'purpose_prestige', 'sweet_shy', 'rebel_brave'].forEach((key) => {
                this.scores[key] += Number(option[key] || 0);
            });

            if (this.step >= this.questions.length - 1) {
                this.finish();
                return;
            }
            this.step += 1;
        },

        finish() {
            const winner =
                Object.entries(this.scores).sort((a, b) => b[1] - a[1])[0]?.[0] ||
                'purpose_prestige';
            this.result = this.results[winner] || this.results.purpose_prestige;
            this.finished = true;
            this.accent = this.result.color;
            applyProductTheme(this.result.color);
        },

        restart() {
            this.step = 0;
            this.finished = false;
            this.result = null;
            this.accent = DEFAULT_THEME_BLUE;
            this.scores = {
                peaceful_calm: 0,
                purpose_prestige: 0,
                sweet_shy: 0,
                rebel_brave: 0,
            };
            restoreProductTheme();
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

        destinationForUser(user) {
            return user?.is_admin ? '/dashboard' : '/';
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
                        message: user?.is_admin
                            ? 'Login berhasil. Mengarahkan ke dashboard admin Evomi.'
                            : 'Login berhasil. Selamat melanjutkan petualangan aroma Anda bersama Evomi.',
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

        init() {
            const token = getAuthToken();
            const user = getAuthUser();

            if (!token || !user) {
                this.denied = true;
                this.deniedMessage = 'Silakan login terlebih dahulu.';
                window.setTimeout(() => {
                    window.location.replace('/login');
                }, 900);
                return;
            }

            if (user.is_admin !== true) {
                clearAuthSession();
                this.denied = true;
                this.deniedMessage =
                    'Akses ditolak! Anda tidak memiliki izin sebagai Administrator.';
                window.setTimeout(() => {
                    window.location.replace('/login');
                }, 1400);
                return;
            }

            this.ready = true;
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

    Alpine.data('evomiProfileShell', () => ({
        ready: false,
        badges: { cart: 0, wishlist: 0, history: 0, unread: 0 },

        badgeLabel(key) {
            return formatBadge(this.badges?.[key] || 0);
        },

        async init() {
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
            this.$nextTick(() => bindSoftLinks(this.$el));
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
        form: { name: '', email: '', phone: '', address: '', password: '' },
        avatarPreview: null,
        avatarFile: null,
        lastLoginLabel: '',
        lastSeenLabel: '',

        get initial() {
            return userDisplayInitial(this.form.name || this.form.email);
        },

        async init() {
            await this.load();
        },

        formatPresence(value) {
            if (!value) return '';
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
                this.avatarPreview = resolveAvatarUrl(user.avatar_profile);
                this.lastLoginLabel = this.formatPresence(user.last_login_at);
                this.lastSeenLabel = this.formatPresence(user.last_seen_at);
            } catch (err) {
                this.status = {
                    type: 'error',
                    message: err instanceof Error ? err.message : 'Gagal memuat profil.',
                };
            } finally {
                this.loading = false;
            }
        },

        onAvatarChange(e) {
            const file = e.target.files?.[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                this.status = { type: 'error', message: 'Ukuran foto maksimal 2MB.' };
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
            this.status = { type: '', message: '' };
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
                if (!res.ok) throw new Error(apiErrorMessage(data, 'Gagal menyimpan profil.'));

                const user = data.data || data.user || {};
                const prev = getAuthUser() || {};
                const merged = { ...prev, ...user };
                setAuthSession(getAuthToken(), merged);
                this.form.password = '';
                this.avatarFile = null;
                this.avatarPreview = resolveAvatarUrl(user.avatar_profile);
                this.status = { type: 'success', message: 'Profil berhasil diperbarui.' };
            } catch (err) {
                this.status = {
                    type: 'error',
                    message: err instanceof Error ? err.message : 'Gagal menyimpan.',
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

        get subtotalLabel() {
            const total = this.items.reduce(
                (s, i) => s + (Number(i.unitPrice) || 0) * (Number(i.quantity) || 0),
                0,
            );
            return formatRupiah(total);
        },

        async init() {
            await this.load();
        },

        mapItem(row) {
            const unit = productPrice(row.product);
            const qty = Number(row.quantity) || 1;
            return {
                id: row.id,
                product_id: row.product_id || row.product?.id,
                title: productTitle(row.product),
                imageUrl: productImage(row.product),
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
                const list = Array.isArray(data) ? data : data.data || [];
                this.items = list.map((row) => this.mapItem(row));
            } catch (err) {
                this.error = err instanceof Error ? err.message : 'Gagal memuat keranjang.';
            } finally {
                this.loading = false;
                this.$nextTick(() => bindSoftLinks(this.$el));
            }
        },

        async changeQty(item, delta) {
            const next = Math.max(1, (Number(item.quantity) || 1) + delta);
            if (item.stock && next > item.stock) {
                this.toast = `Stok tersedia: ${item.stock}`;
                return;
            }
            try {
                const res = await fetch(`/api/carts/${item.id}`, {
                    method: 'PUT',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({ quantity: next }),
                });
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, 'Gagal mengubah jumlah.'));
                item.quantity = next;
                item.lineTotalLabel = formatRupiah(item.unitPrice * next);
                emitEvomiEvent('cart_updated');
            } catch (err) {
                this.toast = err instanceof Error ? err.message : 'Gagal mengubah jumlah.';
            }
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
                    throw new Error(apiErrorMessage(data, 'Gagal menghapus item.'));
                }
                this.items = this.items.filter((i) => i.id !== item.id);
                emitEvomiEvent('cart_updated');
            } catch (err) {
                this.toast = err instanceof Error ? err.message : 'Gagal menghapus.';
            }
        },

        goCheckout() {
            if (!this.items.length) {
                this.toast = 'Keranjang masih kosong.';
                window.setTimeout(() => {
                    this.toast = '';
                }, 2500);
                return;
            }
            window.location.href = '/checkout?type=cart';
        },
    }));

    Alpine.data('evomiProfileWishlist', () => ({
        loading: true,
        error: '',
        items: [],
        toast: '',

        async init() {
            await this.load();
        },

        mapItem(row) {
            return {
                id: row.id,
                product_id: row.product_id || row.product?.id,
                title: productTitle(row.product),
                imageUrl: productImage(row.product),
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
                const list = Array.isArray(data) ? data : data.data || [];
                this.items = list.map((row) => this.mapItem(row));
            } catch (err) {
                this.error = err instanceof Error ? err.message : 'Gagal memuat wishlist.';
            } finally {
                this.loading = false;
                this.$nextTick(() => bindSoftLinks(this.$el));
            }
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
                    throw new Error(apiErrorMessage(data, 'Gagal menghapus wishlist.'));
                }
                this.items = this.items.filter((i) => i.id !== item.id);
                emitEvomiEvent('wishlist_updated');
            } catch (err) {
                this.toast = err instanceof Error ? err.message : 'Gagal menghapus.';
            }
        },

        async moveToCart(item) {
            try {
                const res = await fetch('/api/carts', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({ product_id: item.product_id, quantity: 1 }),
                });
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, 'Gagal menambah ke keranjang.'));
                await this.remove(item);
                emitEvomiEvent('cart_updated');
                this.toast = 'Produk dipindahkan ke keranjang.';
                window.setTimeout(() => {
                    this.toast = '';
                }, 2500);
            } catch (err) {
                this.toast = err instanceof Error ? err.message : 'Gagal memindahkan.';
            }
        },
    }));

    Alpine.data('evomiProfileHistory', () => ({
        loading: true,
        error: '',
        groups: [],
        page: 1,
        perPage: 5,

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
                const list = Array.isArray(data) ? data : data.data || [];
                this.groups = groupOrdersByCreatedAt(list);
                this.page = 1;
            } catch (err) {
                this.error = err instanceof Error ? err.message : 'Gagal memuat riwayat.';
            } finally {
                this.loading = false;
                this.$nextTick(() => bindSoftLinks(this.$el));
            }
        },

        async confirmGroup(group) {
            try {
                for (const item of group.items) {
                    const res = await fetch(`/api/orders/${item.id}/confirm`, {
                        method: 'PATCH',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        const data = await readApiJson(res);
                        throw new Error(apiErrorMessage(data, 'Gagal konfirmasi pesanan.'));
                    }
                }
                emitEvomiEvent('history_updated');
                await this.load();
            } catch (err) {
                this.error = err instanceof Error ? err.message : 'Gagal konfirmasi.';
            }
        },

        async removeGroup(group) {
            if (!window.confirm('Hapus pesanan ini dari riwayat?')) return;
            try {
                for (const item of group.items) {
                    const res = await fetch(`/api/orders/${item.id}`, {
                        method: 'DELETE',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        const data = await readApiJson(res);
                        throw new Error(apiErrorMessage(data, 'Gagal menghapus riwayat.'));
                    }
                }
                emitEvomiEvent('history_updated');
                await this.load();
            } catch (err) {
                this.error = err instanceof Error ? err.message : 'Gagal menghapus.';
            }
        },
    }));

    Alpine.data('evomiProfileHistoryShow', (orderId) => ({
        orderId,
        loading: true,
        error: '',
        group: null,

        async init() {
            await this.load();
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
                const list = Array.isArray(data) ? data : data.data || [];
                const groups = groupOrdersByCreatedAt(list);
                this.group =
                    groups.find((g) => String(g.groupId) === String(this.orderId)) ||
                    groups.find((g) => g.items.some((i) => String(i.id) === String(this.orderId))) ||
                    null;
                if (!this.group) this.error = 'Pesanan tidak ditemukan.';
            } catch (err) {
                this.error = err instanceof Error ? err.message : 'Gagal memuat detail.';
            } finally {
                this.loading = false;
                this.$nextTick(() => bindSoftLinks(this.$el));
            }
        },

        async confirmGroup() {
            if (!this.group) return;
            try {
                for (const item of this.group.items) {
                    const res = await fetch(`/api/orders/${item.id}/confirm`, {
                        method: 'PATCH',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        const data = await readApiJson(res);
                        throw new Error(apiErrorMessage(data, 'Gagal konfirmasi pesanan.'));
                    }
                }
                emitEvomiEvent('history_updated');
                await this.load();
            } catch (err) {
                this.error = err instanceof Error ? err.message : 'Gagal konfirmasi.';
            }
        },
    }));

    Alpine.data('evomiProfileChat', () => ({
        loading: true,
        sending: false,
        draft: '',
        messages: [],
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

        async load(silent = false) {
            const user = getAuthUser();
            if (!user?.email) {
                window.location.replace('/login');
                return;
            }
            if (!silent) this.loading = true;
            try {
                const res = await fetch(`/api/contact?email=${encodeURIComponent(user.email)}`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await readApiJson(res);
                const raw = Array.isArray(data) ? data : data.data || data.messages || [];
                // Normalize possible shapes into bubbles
                this.messages = [];
                for (const row of raw) {
                    if (row.type && row.text) {
                        this.messages.push({
                            id: row.id || `${row.type}-${row.created_at}`,
                            type: row.type === 'admin' ? 'admin' : 'user',
                            text: row.text || row.message || '',
                            timeLabel: new Date(row.created_at).toLocaleString('id-ID', {
                                day: 'numeric',
                                month: 'short',
                                hour: '2-digit',
                                minute: '2-digit',
                            }),
                        });
                        continue;
                    }
                    if (row.message) {
                        this.messages.push({
                            id: `u-${row.id}`,
                            type: 'user',
                            text: row.message,
                            timeLabel: new Date(row.created_at).toLocaleString('id-ID', {
                                day: 'numeric',
                                month: 'short',
                                hour: '2-digit',
                                minute: '2-digit',
                            }),
                        });
                    }
                    const replies = row.replies || row.contact_replies || [];
                    for (const rep of replies) {
                        this.messages.push({
                            id: `a-${rep.id}`,
                            type: 'admin',
                            text: rep.reply_message || rep.message || rep.reply || rep.text || '',
                            timeLabel: new Date(rep.created_at).toLocaleString('id-ID', {
                                day: 'numeric',
                                month: 'short',
                                hour: '2-digit',
                                minute: '2-digit',
                            }),
                        });
                    }
                }

                await fetch('/api/contact/mark-read', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({ email: user.email }),
                }).catch(() => {});
                emitEvomiEvent('messages_read');

                this.$nextTick(() => {
                    const el = this.$refs.thread;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            } catch {
                /* keep previous */
            } finally {
                this.loading = false;
            }
        },

        async send() {
            const text = (this.draft || '').trim();
            if (!text || this.sending) return;
            const user = getAuthUser();
            if (!user?.email) return;

            this.sending = true;
            try {
                const res = await fetch('/api/contact', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        name: user.name || user.email,
                        email: user.email,
                        subject: 'Pesan Dukungan Pelanggan',
                        message: text,
                    }),
                });
                const data = await readApiJson(res);
                if (!res.ok) throw new Error(apiErrorMessage(data, 'Gagal mengirim pesan.'));
                this.draft = '';
                await this.load(true);
            } catch (err) {
                window.alert(err instanceof Error ? err.message : 'Gagal mengirim.');
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
                if (!this.hasAddress) {
                    this.editingAddress = true;
                    this.draft = { ...this.form };
                }
            } catch (err) {
                this.fatalError = err instanceof Error ? err.message : 'Gagal memuat checkout.';
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
                    throw new Error('Login diperlukan untuk checkout keranjang.');
                }
                const res = await fetch('/api/carts?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    window.location.replace('/login');
                    throw new Error('Sesi berakhir.');
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
                if (!this.items.length) throw new Error('Keranjang kosong.');
                this.brand = DEFAULT_THEME_BLUE;
                return;
            }

            const productId = Number(params.get('productId'));
            const qty = Math.max(1, Number(params.get('qty') || 1));
            const unitPrice = Number(params.get('unitPrice') || 0);
            if (!productId) throw new Error('Produk checkout tidak valid.');

            const res = await fetch(`/api/products/${productId}`, { headers: { Accept: 'application/json' } });
            const data = await readApiJson(res);
            const product = data?.data || data;
            if (!res.ok || !product?.id) throw new Error('Produk tidak ditemukan.');

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
            if (!getAuthToken()) return;
            try {
                const res = await fetch('/api/user/profile', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await readApiJson(res);
                const user = data?.data || data?.user || data;
                this.form = {
                    name: user?.nama || user?.name || '',
                    email: user?.email || '',
                    phone: user?.phone || user?.no_hp || '',
                    address: user?.alamat || user?.address || '',
                };
            } catch {
                /* ignore */
            }
        },

        startEditAddress() {
            this.draft = { ...this.form };
            this.editingAddress = true;
        },

        cancelAddressEdit() {
            this.editingAddress = false;
            this.draft = { ...this.form };
        },

        saveAddress() {
            this.form = {
                name: (this.draft.name || '').trim(),
                email: (this.draft.email || '').trim(),
                phone: (this.draft.phone || '').trim(),
                address: (this.draft.address || '').trim(),
            };
            this.editingAddress = false;
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

        validateForm() {
            if (this.editingAddress) this.saveAddress();
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
                this.formError = 'Tidak ada item untuk di-checkout.';
                return false;
            }
            this.formError = '';
            return true;
        },

        makeInvoiceId() {
            return `INV-${Date.now()}-${Math.floor(Math.random() * 9000 + 1000)}`;
        },

        async submitCheckout() {
            if (this.processing || !this.validateForm()) return;

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
                        throw new Error('Checkout tamu hanya untuk 1 produk (beli langsung).');
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
                    if (!res.ok) throw new Error(apiErrorMessage(data, 'Checkout gagal.'));
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
                    if (!res.ok) throw new Error(apiErrorMessage(data, 'Checkout gagal.'));

                    try {
                        await fetch('/api/trackings', {
                            method: 'POST',
                            headers: authHeaders(true),
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                order_id: invoiceId,
                                status: 'Menunggu Konfirmasi',
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
                    title: 'Checkout Berhasil!',
                    message: `Pesanan ${invoiceId} sudah dibuat. Kamu bisa lacak di halaman Pengiriman.`,
                };
            } catch (err) {
                this.modal = {
                    open: true,
                    type: 'error',
                    title: 'Checkout Gagal',
                    message: err instanceof Error ? err.message : 'Terjadi kesalahan.',
                };
            } finally {
                this.processing = false;
            }
        },

        closeModal() {
            const wasSuccess = this.modal.type === 'success';
            const orderId = this.completedOrderId;
            this.modal.open = false;
            if (wasSuccess) {
                if (orderId) {
                    window.location.href = `/pengiriman/${encodeURIComponent(orderId)}`;
                } else {
                    window.location.href = '/';
                }
            }
        },
    }));
});

Alpine.start();

async function softNavigate(href, { push = true, navIndex = null } = {}) {
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
    if (samePath && url.hash) {
        if (nav && navIndex !== null && !Number.isNaN(navIndex)) {
            nav.setActive(navIndex, true);
        }
        if (push) {
            history.pushState({ soft: true }, '', url.pathname + url.search + url.hash);
        }
        document.querySelector(url.hash)?.scrollIntoView({ behavior: 'smooth' });
        return;
    }

    // Same full URL — no-op
    if (samePath && !url.hash && !window.location.hash) {
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

    try {
        // Fetch in parallel with leave animation (SPA feel, no loader)
        const fetchPromise = fetch(url.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            credentials: 'same-origin',
        });

        main.classList.add('is-leaving');

        const [res] = await Promise.all([fetchPromise, wait(340)]);

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
            main.classList.add('is-entering');
        };

        if (document.startViewTransition) {
            await document.startViewTransition(apply).finished.catch(() => {});
        } else {
            apply();
        }

        await waitFrames(2);
        // Soft enter: allow one frame then animate in
        requestAnimationFrame(() => {
            main.classList.remove('is-entering');
        });
        await wait(420);
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

    let best = 0;
    track.querySelectorAll('[data-nav-index]').forEach((el) => {
        const match = el.dataset.navMatch || '';
        const i = Number(el.dataset.navIndex);
        if (match === '#about') return;
        const p = pathname.replace(/\/$/, '') || '/';
        // Checkout is part of belanja flow — keep Belanja active
        if (match === '/belanja' && (p.startsWith('/belanja') || p.startsWith('/checkout'))) {
            best = i;
            return;
        }
        if (match === '/' && (p === '/' || p === '')) best = i;
        if (match !== '/' && match !== '#about' && match !== '/belanja' && p.startsWith(match)) best = i;
    });
    nav.setActive(best, true);
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

document.addEventListener('DOMContentLoaded', () => {
    setSurfaceForPath(window.location.pathname);
    bindSoftLinks(document);
    initBerandaMotion(document);
});

window.addEventListener('popstate', () => {
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
        return;
    }

    const cleanups = [];

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
