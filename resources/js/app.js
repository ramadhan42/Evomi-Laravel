import Alpine from 'alpinejs';
import { readAdminLocale, writeAdminLocale } from './admin-locale';
import { L as storefrontL, currentLocale, registerStorefrontI18n } from './storefront-i18n';
import { initSourceGuard } from './source-guard';
import {
    clearTurnstileSessionVerified,
    destroyTurnstile,
    ensureTurnstileMounted,
    isTurnstileSessionVerified,
    markTurnstileSessionVerified,
    resetTurnstile,
    runTurnstile,
    setupTurnstile,
    turnstileEnabled,
    turnstileRequiredMessage,
    turnstileState,
    turnstileToken,
} from './turnstile';

window.Alpine = Alpine;
registerStorefrontI18n(Alpine);

/*
 * Kode dashboard admin (admin-crud, kamus admin-i18n, grafik penjualan) dimuat
 * lewat dynamic import supaya tidak ikut terkirim ke pengunjung biasa.
 * Aman karena masuk/keluar /dashboard selalu hard navigate — lihat softNavigate().
 */
let registerAdminCrud = null;
let createAdminI18nApi = null;
let buildSalesChartModel = null;
let salesChartHoverAt = null;
let salesChartClearHover = null;

async function loadAdminModules() {
    const [crud, i18n, chart] = await Promise.all([
        import('./admin-crud'),
        import('./admin-i18n'),
        import('./admin-sales-chart.js'),
    ]);
    registerAdminCrud = crud.registerAdminCrud;
    createAdminI18nApi = i18n.createAdminI18nApi;
    buildSalesChartModel = chart.buildSalesChartModel;
    salesChartHoverAt = chart.salesChartHoverAt;
    salesChartClearHover = chart.salesChartClearHover;
}

let softNavBusy = false;
let softNavToken = 0;
let localeRevealTimer = 0;
let lastSoftPath = (typeof window !== 'undefined'
    ? window.location.pathname.replace(/\/$/, '') || '/'
    : '/');
const productChromeById = new Map();

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

function isKuisPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/kuis';
}

function isSettingsPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/profile';
}

function isArtikelPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/artikel' || p.startsWith('/artikel/');
}

function contactTimeLabel(iso) {
    return new Date(iso).toLocaleString(
        document.documentElement.lang === 'en' ? 'en-US' : 'id-ID',
        { hour: '2-digit', minute: '2-digit' },
    );
}

/**
 * Ratakan respons /api/contact (tiket + balasan admin, atau bubble siap pakai)
 * menjadi satu daftar percakapan urut waktu.
 */
function normalizeContactBubbles(raw) {
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
                timeLabel: contactTimeLabel(row.created_at),
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
                timeLabel: contactTimeLabel(row.created_at),
            });
        }

        for (const rep of row.replies || row.contact_replies || []) {
            const unread = !(
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
                timeLabel: contactTimeLabel(rep.created_at),
            });
        }
    }

    return bubbles.sort(
        (a, b) => new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(),
    );
}

async function fetchContactThread(email) {
    const res = await fetch(`/api/contact?email=${encodeURIComponent(email)}`, {
        headers: authHeaders(false),
        credentials: 'same-origin',
    });
    const data = await readApiJson(res);
    const raw = Array.isArray(data) ? data : data.data || data.messages || [];

    return normalizeContactBubbles(raw);
}

/**
 * Captcha untuk composer chat.
 *
 * persistSession: setelah lolos, widget disembunyikan dan server mengingat
 * verifikasi selama beberapa menit (halaman / modal pesan profil).
 *
 * freshOnOpen: setiap kali panel dibuka, captcha diminta ulang. Dipakai chat
 * di detail produk — tutup modal = verifikasi hangus.
 */
function chatCaptcha(mountId, { persistSession = true, freshOnOpen = false } = {}) {
    return {
        turnstileMountId: mountId,
        captchaScope: '',
        ...turnstileState(turnstileEnabled() && (persistSession ? !isTurnstileSessionVerified() : true)),

        runTurnstile() {
            runTurnstile(this);
        },

        async mountChatCaptcha() {
            if (!freshOnOpen && this.hasTurnstile && isTurnstileSessionVerified()) {
                this.hasTurnstile = false;
            }

            await ensureTurnstileMounted(
                this,
                document.getElementById(this.turnstileMountId),
                { theme: 'light' },
            );
        },

        async startFreshCaptcha() {
            this.captchaScope = freshOnOpen
                ? `chat-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
                : '';
            this.hasTurnstile = turnstileEnabled();
            destroyTurnstile(this);
            await this.$nextTick();
            await setupTurnstile(
                this,
                document.getElementById(this.turnstileMountId),
                { theme: 'light' },
            );
        },

        stopChatCaptcha() {
            destroyTurnstile(this);
            this.captchaScope = '';
            this.hasTurnstile = turnstileEnabled();
        },

        /** Kembalikan null bila captcha tampil tapi belum dicentang. */
        chatCaptchaToken() {
            if (!this.hasTurnstile) {
                return '';
            }

            return turnstileToken(this) || null;
        },

        markChatCaptchaPassed() {
            if (!this.hasTurnstile) return;

            if (persistSession) {
                markTurnstileSessionVerified();
            }
            resetTurnstile(this);
            this.hasTurnstile = false;
        },

        /** Server menolak (verifikasi kedaluwarsa/cache hilang): tampilkan lagi. */
        async askChatCaptchaAgain() {
            if (persistSession) {
                clearTurnstileSessionVerified();
            }
            await this.startFreshCaptcha();
        },
    };
}

function isAuthPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return (
        p === '/login' ||
        p === '/register' ||
        p === '/lupa-password' ||
        p.startsWith('/reset-password')
    );
}

function isBelanjaDetailPath(pathname) {
    return /^\/belanja\/\d+/.test((pathname || '').replace(/\/$/, '') || '/');
}

function isBelanjaFlowPath(pathname) {
    return isBelanjaListPath(pathname) || isBelanjaDetailPath(pathname);
}

function isBlueSurfacePath(pathname) {
    return isBerandaPath(pathname) || isBelanjaListPath(pathname) || isArtikelPath(pathname) || isAuthPath(pathname);
}

const DEFAULT_THEME_BLUE = '#1172BA';

/**
 * Menyelaraskan warna scrollbar dengan latar halaman yang sedang tampil.
 *
 * Latar body berganti-ganti: biru penuh di beranda dan artikel, abu terang di
 * daftar belanja, putih di halaman detail produk. Kalau ibu jari scrollbar
 * dipatok satu warna, di salah satu halaman ia pasti menyatu dengan latarnya
 * dan seolah hilang - itu yang terjadi di beranda, karena warna temanya sama
 * persis dengan warna latarnya.
 *
 * Jadi warnanya dipilih dari kecerahan latar: latar gelap -> ibu jari putih,
 * latar terang -> warna tema halaman (yang di halaman produk berarti warna
 * varian produknya).
 */
function syncScrollbarTint() {
    const body = document.body;

    if (!body) return;

    // Setiap cabang di bawah menulis ulang variabelnya; yang opsional dibersihkan
    // dulu supaya nilai dari halaman sebelumnya tidak ikut terbawa saat berpindah
    // halaman lewat soft-navigation (elemen body-nya sama, tidak dimuat ulang).
    body.style.removeProperty('--evomi-scrollbar-hover');

    // Dashboard admin memakai palet monokrom - permukaan putih/abu muda dengan
    // teks dan tombol #111827. Scrollbar-nya ikut warna gelap itu, bukan biru
    // merek, supaya menyatu dengan tampilan dashboard.
    if (body.classList.contains('evomi-admin-mode')) {
        body.style.setProperty('--evomi-scrollbar', '#111827');
        body.style.setProperty('--evomi-scrollbar-track', 'rgba(17, 24, 39, 0.08)');

        return;
    }

    // Halaman ber-chrome biru - beranda, artikel, daftar belanja, dan auth -
    // memakai satu tampilan scrollbar yang sama: jalur biru merek dengan ibu
    // jari putih.
    //
    // Jalurnya diberi warna eksplisit, bukan dibiarkan mengambil latar halaman,
    // karena latar ketiganya tidak seragam: beranda dan artikel biru penuh,
    // sedangkan daftar belanja abu terang. Tanpa jalur eksplisit, ibu jari putih
    // akan lenyap di halaman daftar belanja.
    if (body.classList.contains('evomi-surface-blue')) {
        body.style.setProperty('--evomi-scrollbar', 'rgba(255, 255, 255, 0.9)');
        body.style.setProperty('--evomi-scrollbar-track', DEFAULT_THEME_BLUE);
        body.style.setProperty('--evomi-scrollbar-hover', '#ffffff');

        return;
    }

    const cs = getComputedStyle(body);
    const parts = cs.backgroundColor.match(/\d+(\.\d+)?/g);

    if (!parts || parts.length < 3) return;

    const [r, g, b] = parts.map(Number);
    // Luminansi persepsi (BT.601) - cukup untuk sekadar memilih terang vs gelap.
    const luma = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    if (luma < 0.62) {
        body.style.setProperty('--evomi-scrollbar', 'rgba(255, 255, 255, 0.82)');
        body.style.setProperty('--evomi-scrollbar-track', 'rgba(255, 255, 255, 0.18)');

        return;
    }

    const theme = cs.getPropertyValue('--evomi-theme').trim() || DEFAULT_THEME_BLUE;
    body.style.setProperty('--evomi-scrollbar', theme);
    // Jalur memakai warna tema yang sama tapi sangat samar, supaya batangnya
    // tetap satu keluarga warna dengan halamannya.
    body.style.setProperty('--evomi-scrollbar-track', `color-mix(in srgb, ${theme} 14%, transparent)`);
}

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
    if (chrome) chrome.style.backgroundColor = c;
    if (spacer) spacer.style.backgroundColor = c;
    if (footerWrap) footerWrap.style.backgroundColor = c;
    if (footer) {
        footer.style.backgroundColor = c;
        footer.style.setProperty('--footer-accent', c);
    }

    // Latar body bisa ikut berubah bersama tema; selaraskan scrollbar sekarang
    // dan sekali lagi setelah transisi latar selesai.
    syncScrollbarTint();
    window.setTimeout(syncScrollbarTint, 400);
}

function restoreProductTheme() {
    applyProductTheme(DEFAULT_THEME_BLUE);
}

/** Paint Evomi blue under soft-nav so fade never flashes white into blue pages */
function setSurfaceForPath(pathname) {
    const blue = isBlueSurfacePath(pathname);
    const auth = isAuthPath(pathname);
    const detail = isBelanjaDetailPath(pathname);
    const payment = isPaymentPath(pathname);

    document.body.classList.toggle('evomi-surface-blue', blue);
    document.body.classList.toggle('evomi-auth-mode', auth);
    document.body.classList.toggle('evomi-detail-seamless', detail);
    document.body.classList.toggle('evomi-payment-mode', payment);
    document.body.classList.toggle('evomi-belanja-page', isBelanjaListPath(pathname));

    const main = document.getElementById('evomi-main');
    const footerWrap = document.getElementById('evomi-footer-wrap');
    if (main) {
        main.classList.toggle('overflow-visible', detail);
        main.classList.toggle('overflow-x-hidden', !detail);
        main.classList.toggle('min-h-0', payment);
    }
    if (footerWrap) {
        footerWrap.classList.toggle('belanja-detail-footer-seam', detail);
        footerWrap.classList.remove('hidden');
        footerWrap.removeAttribute('hidden');
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

    // Kelas di atas yang menentukan latar halaman, jadi warna scrollbar
    // diselaraskan setelah kelasnya dipasang.
    syncScrollbarTint();
    window.setTimeout(syncScrollbarTint, 400);
}

function wait(ms) {
    return new Promise((r) => setTimeout(r, ms));
}

/* ——— Full-page loader (parity with Next.js LoadingScreen) ——— */
const LOADER_MIN_MS = 1200;
const LOADER_MAX_MS = 2400;
// Grace after the clip ends: the last frame holds while a slow page finishes.
const LOADER_VIDEO_GRACE_MS = 4000;

function unlockEvomiLoaderScroll() {
    document.documentElement.classList.remove('evomi-loading');
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
}

/**
 * Video loading screen: plays the clip behind the loader, fits it to the
 * viewport and paints the backdrop with the video's own edge colour so a
 * letterboxed clip has no visible band. Any failure (missing file, blocked
 * autoplay, reduced motion) simply leaves the animated orb in place.
 */
function initEvomiLoaderVideo(root, onSettled) {
    const video = document.getElementById('evomi-loader-video');
    if (!video) return null;

    const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion) return null;

    const applyFit = () => {
        const vw = video.videoWidth;
        const vh = video.videoHeight;
        if (!vw || !vh) return;

        const videoRatio = vw / vh;
        const screenRatio = window.innerWidth / Math.max(1, window.innerHeight);
        // Crop only while the shapes are close; beyond that, letterbox instead
        // of cutting the animation in half on a phone held upright.
        const drift = Math.abs(videoRatio - screenRatio) / videoRatio;
        root.classList.toggle('fit-contain', drift > 0.35);
    };

    const paintBackdrop = () => {
        try {
            const canvas = document.createElement('canvas');
            canvas.width = 32;
            canvas.height = 18;
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            if (!ctx) return;

            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const { data } = ctx.getImageData(0, 0, canvas.width, canvas.height);

            let r = 0;
            let g = 0;
            let b = 0;
            let n = 0;
            for (let y = 0; y < canvas.height; y++) {
                for (let x = 0; x < canvas.width; x++) {
                    const edge = x === 0 || y === 0 || x === canvas.width - 1 || y === canvas.height - 1;
                    if (!edge) continue;
                    const i = (y * canvas.width + x) * 4;
                    r += data[i];
                    g += data[i + 1];
                    b += data[i + 2];
                    n++;
                }
            }
            if (!n) return;

            root.style.background = `rgb(${Math.round(r / n)}, ${Math.round(g / n)}, ${Math.round(b / n)})`;
        } catch (e) {
            /* canvas can be unavailable; the gradient stays */
        }
    };

    // Nothing to watch if the clip cannot play at all: show the orb instead of
    // an empty screen and let the loader fall back to its own timing.
    const giveUp = () => {
        root.classList.add('show-fallback');
        onSettled?.();
    };

    const reveal = () => {
        applyFit();
        paintBackdrop();
        root.classList.add('is-video-playing');
    };

    let started = false;
    const startClip = () => {
        if (started) return;
        started = true;

        try {
            video.currentTime = 0;
        } catch (e) {
            /* seeking before data is harmless to skip */
        }

        const played = video.play?.();
        if (played?.catch) played.catch(giveUp);
    };

    /*
     * The clip is started a painted frame late on purpose: `autoplay` would
     * begin during parsing, before the browser has put anything on screen, and
     * the opening of the animation would be over before it was ever visible.
     */
    const startWhenVisible = () => {
        window.requestAnimationFrame(() => window.requestAnimationFrame(startClip));
    };

    video.addEventListener('loadedmetadata', applyFit);
    video.addEventListener('loadeddata', applyFit);
    video.addEventListener('playing', reveal, { once: true });
    video.addEventListener('ended', () => onSettled?.(), { once: true });
    video.addEventListener('error', giveUp, { once: true });
    window.addEventListener('resize', applyFit);
    window.addEventListener('orientationchange', applyFit);

    if (video.readyState >= 2) {
        startWhenVisible();
    } else {
        video.addEventListener('loadeddata', startWhenVisible, { once: true });
        // A clip that never buffers must not hold the page hostage.
        window.setTimeout(() => (started ? null : giveUp()), 2500);
    }

    return video;
}

function initEvomiLoader() {
    const root = document.getElementById('evomi-loader');
    const bar = document.getElementById('evomi-loader-bar');
    if (!root) {
        unlockEvomiLoaderScroll();
        return;
    }

    let clipDone = false;
    const video = initEvomiLoaderVideo(root, () => {
        clipDone = true;
    });
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
            video?.pause?.();
        }, 500);
    };

    const tick = () => {
        if (done) return;

        const clip = clipMs();
        if (clip && video && video.currentTime > 0) {
            // Honest progress: the bar fills exactly as the clip plays.
            setProgress(Math.min(99, (video.currentTime * 1000 / clip) * 100));
        } else {
            const elapsed = Date.now() - startedAt;
            setProgress(Math.min(92, 8 + (elapsed / maxMs()) * 84));
        }

        raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);

    /*
     * Timing with a clip: it plays exactly once and is never cut short, even if
     * the page is ready first. Should the page still be loading when the clip
     * ends, its last frame holds instead of looping. Without a clip the
     * original 1.2s / 2.4s window applies.
     */
    const clipMs = () => {
        const clip = (video?.duration || 0) * 1000;

        return Number.isFinite(clip) && clip > 250 ? clip : 0;
    };

    const maxMs = () => (video ? (clipMs() || LOADER_MAX_MS) + LOADER_VIDEO_GRACE_MS : LOADER_MAX_MS);

    const tryFinish = () => {
        const elapsed = Date.now() - startedAt;

        if (elapsed < maxMs()) {
            // Never leave in the middle of the clip.
            if (video && !clipDone) {
                pollTimer = window.setTimeout(tryFinish, 80);

                return;
            }

            const minimum = video ? 0 : LOADER_MIN_MS;
            if (document.readyState !== 'complete' || elapsed < minimum) {
                pollTimer = window.setTimeout(tryFinish, 120);

                return;
            }
        }

        finish();
    };

    const onLoad = () => tryFinish();
    if (document.readyState === 'complete') {
        tryFinish();
    } else {
        window.addEventListener('load', onLoad, { once: true });
        maxTimer = window.setTimeout(tryFinish, maxMs());
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
/**
 * Tooltip teks alt untuk gambar di dalam artikel. Satu elemen dipakai ulang dan
 * dipasang lewat event delegation, jadi gambar yang baru muncul (ganti bahasa,
 * navigasi lunak) ikut terlayani tanpa pemasangan ulang.
 */
function initEvomiAltTooltips() {
    const SELECTOR = '.artikel-detail-body img[alt], .artikel-detail-text img[alt]';
    let tip = null;

    const ensureTip = () => {
        if (tip && tip.isConnected) return tip;
        tip = document.createElement('div');
        tip.className = 'evomi-alt-tip';
        tip.setAttribute('role', 'tooltip');
        document.body.appendChild(tip);

        return tip;
    };

    const place = (img) => {
        const box = img.getBoundingClientRect();
        const node = ensureTip();
        const size = node.getBoundingClientRect();
        const margin = 10;

        let left = box.left + box.width / 2 - size.width / 2;
        left = Math.max(margin, Math.min(left, window.innerWidth - size.width - margin));

        // Di atas gambar bila muat, kalau tidak di bawahnya.
        let top = box.top - size.height - 10;
        if (top < margin) top = Math.min(box.bottom + 10, window.innerHeight - size.height - margin);

        node.style.left = `${Math.round(left)}px`;
        node.style.top = `${Math.round(top)}px`;
    };

    const show = (img) => {
        const text = (img.getAttribute('alt') || '').trim();
        if (text === '') return;

        const node = ensureTip();
        node.textContent = text;
        node.classList.add('is-on');
        place(img);
    };

    const hide = () => {
        if (tip) tip.classList.remove('is-on');
    };

    document.addEventListener('mouseover', (e) => {
        const img = e.target instanceof Element ? e.target.closest(SELECTOR) : null;
        if (img) show(img);
    });

    document.addEventListener('mouseout', (e) => {
        const img = e.target instanceof Element ? e.target.closest(SELECTOR) : null;
        if (img) hide();
    });

    // Keyboard dan sentuh: fokus memunculkan, gulir menyembunyikan.
    document.addEventListener('focusin', (e) => {
        const img = e.target instanceof Element ? e.target.closest(SELECTOR) : null;
        if (img) show(img);
    });
    document.addEventListener('focusout', hide);
    window.addEventListener('scroll', hide, { passive: true });
}

initEvomiAltTooltips();
initEvomiLoader();

function isProfilePath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/profile' || p.startsWith('/profile/');
}

function isArtikelDetailPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return /^\/artikel\/[^/]+$/.test(p);
}

function isCheckoutPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/checkout';
}

function isPaymentPath(pathname) {
    const p = (pathname || '').replace(/\/$/, '') || '/';
    return p === '/pembayaran' || p.startsWith('/pembayaran/');
}

/** Pages whose navbar/footer keep the product accent instead of Evomi blue. */
function usesProductChrome(pathname) {
    return (
        isBelanjaDetailPath(pathname) ||
        isCheckoutPath(pathname) ||
        isPaymentPath(pathname)
    );
}

function belanjaDetailId(pathname) {
    const match = String(pathname || '').match(/^\/belanja\/(\d+)/);
    return match ? match[1] : '';
}

function rememberProductChrome(pathnameOrId, color) {
    const raw = String(pathnameOrId || '').trim();
    const id = /^\d+$/.test(raw) ? raw : belanjaDetailId(raw);
    const c = String(color || '').trim();
    if (id && c) productChromeById.set(id, c);
}

function productChromeForPath(pathname) {
    const id = belanjaDetailId(pathname);
    return id ? productChromeById.get(id) || '' : '';
}

function accentFromNavSource(el) {
    if (!el?.closest) return '';
    const host = el.closest(
        '.belanja-card, .evomi-wishlist-page__card, .evomi-profile-list__card, .evomi-wishlist-modal__item, [data-accent], [style*="--card-accent"], [style*="--detail-accent"], [style*="--wl-accent"]',
    );
    if (!host) return '';
    const data = host.getAttribute?.('data-accent');
    if (data) return data.trim();
    try {
        const cs = getComputedStyle(host);
        return (
            cs.getPropertyValue('--card-accent') ||
            cs.getPropertyValue('--detail-accent') ||
            cs.getPropertyValue('--wl-accent') ||
            ''
        ).trim();
    } catch {
        return '';
    }
}

function seedProductChromeFromDom(root = document) {
    root.querySelectorAll('a[href]').forEach((anchor) => {
        const href = anchor.getAttribute('href') || '';
        if (!href.includes('/belanja/')) return;
        let path = href;
        try {
            path = new URL(href, window.location.origin).pathname;
        } catch {
            /* keep */
        }
        const color = accentFromNavSource(anchor);
        if (color) rememberProductChrome(path, color);
    });
}

function chromeThemeFromDoc(doc) {
    if (!doc) return '';
    return (
        doc.body?.style.getPropertyValue('--evomi-theme')?.trim() ||
        doc.getElementById('evomi-footer-wrap')?.style.backgroundColor ||
        ''
    );
}

/** Start chrome recolor during page fade so the morph is hidden. Uses lastSoftPath for back-button. */
function previewChromeThemeForNav(fromPath, toPath, hintColor = '') {
    if (usesProductChrome(fromPath) && !usesProductChrome(toPath)) {
        restoreProductTheme();
        return;
    }
    if (!isBelanjaDetailPath(toPath)) return;
    const color = String(hintColor || '').trim() || productChromeForPath(toPath);
    if (color) {
        rememberProductChrome(toPath, color);
        applyProductTheme(color);
    }
}

function belanjaEnterScopes(root = document) {
    const list = [];
    if (!root) return list;
    if (root.querySelectorAll) {
        root.querySelectorAll('.belanja-page, .belanja-detail-enter, .evomi-soft-enter').forEach((el) => list.push(el));
    }
    if (
        root.classList?.contains('belanja-page') ||
        root.classList?.contains('belanja-detail-enter') ||
        root.classList?.contains('evomi-soft-enter')
    ) {
        list.push(root);
    }
    return [...new Set(list)];
}

function playBelanjaEntrance(root = document, { skipAsync = false } = {}) {
    let scopes = belanjaEnterScopes(root);
    if (skipAsync) {
        scopes = scopes.filter(
            (el) =>
                !el.classList.contains('evomi-payment-page') &&
                !el.classList.contains('profile-page-card') &&
                !el.closest?.('.evomi-settings-modal'),
        );
    }
    if (!scopes.length) return;

    scopes.forEach((scope) => {
        if (typeof prefersReducedMotion === 'function' && prefersReducedMotion()) {
            scope.classList.remove('is-belanja-resetting');
            scope.classList.add('is-belanja-ready', 'is-belanja-settled');
            return;
        }
        if (scope._belanjaEnterTimer) window.clearTimeout(scope._belanjaEnterTimer);
        scope.classList.remove('is-belanja-ready', 'is-belanja-settled');
        scope.classList.add('is-belanja-resetting');
        void scope.offsetWidth;
        requestAnimationFrame(() => {
            scope.classList.remove('is-belanja-resetting');
            void scope.offsetWidth;
            nextFrame().then(() => {
                scope.classList.add('is-belanja-ready');
                scope._belanjaEnterTimer = window.setTimeout(() => {
                    scope.classList.add('is-belanja-settled');
                }, 900);
            });
        });
    });
}

function scheduleBelanjaEntrance(root = document) {
    if (!belanjaEnterScopes(root).length) return;
    const play = () => playBelanjaEntrance(root, { skipAsync: true });
    if (typeof prefersReducedMotion === 'function' && prefersReducedMotion()) {
        play();
        return;
    }
    const loader = document.getElementById('evomi-loader');
    const loaderDone =
        !loader ||
        loader.classList.contains('is-hidden') ||
        loader.classList.contains('is-fading') ||
        !document.documentElement.classList.contains('evomi-loading');
    if (loaderDone) {
        window.setTimeout(play, 80);
        return;
    }
    window.addEventListener('evomi:loader-done', play, { once: true });
    window.setTimeout(play, (typeof LOADER_MAX_MS === 'number' ? LOADER_MAX_MS : 2400) + 400);
}

let footerEnterIo = null;

function footerEnterRoot() {
    return (
        document.querySelector('#evomi-footer-wrap footer.evomi-footer') ||
        document.querySelector('#evomi-footer-wrap footer')
    );
}

function playFooterEntrance(footer) {
    if (!footer) return;
    if (typeof prefersReducedMotion === 'function' && prefersReducedMotion()) {
        footer.classList.remove('is-footer-resetting');
        footer.classList.add('is-footer-ready', 'is-footer-settled');
        return;
    }
    // A hidden tab does not run animation frames, so the entrance transition
    // would freeze at its start value while the settle timer still fires. Skip
    // straight to the end state -- nobody is watching the stagger anyway.
    if (document.hidden) {
        footer.classList.remove('is-footer-resetting');
        footer.classList.add('is-footer-ready', 'is-footer-settled');
        return;
    }
    if (footer._footerEnterTimer) window.clearTimeout(footer._footerEnterTimer);
    footer.classList.remove('is-footer-ready', 'is-footer-settled');
    footer.classList.add('is-footer-resetting');
    void footer.offsetWidth;
    requestAnimationFrame(() => {
        footer.classList.remove('is-footer-resetting');
        void footer.offsetWidth;
        requestAnimationFrame(() => {
            footer.classList.add('is-footer-ready');
            footer._footerEnterTimer = window.setTimeout(() => {
                footer.classList.add('is-footer-settled');
            }, 1200);
            // If the tab is hidden before the transition finishes it stalls
            // mid-flight; settling on return guarantees the footer is visible.
            const settleOnReturn = () => {
                if (document.hidden) return;
                document.removeEventListener('visibilitychange', settleOnReturn);
                footer.classList.add('is-footer-ready', 'is-footer-settled');
            };
            document.addEventListener('visibilitychange', settleOnReturn);
        });
    });
}

function bindFooterEntrance() {
    if (footerEnterIo) {
        footerEnterIo.disconnect();
        footerEnterIo = null;
    }
    const footer = footerEnterRoot();
    if (!footer) return;
    footer.classList.remove('is-footer-ready', 'is-footer-settled', 'is-footer-resetting');
    if (typeof prefersReducedMotion === 'function' && prefersReducedMotion()) {
        footer.classList.add('is-footer-ready', 'is-footer-settled');
        return;
    }
    const observe = () => {
        if (footerEnterIo) {
            footerEnterIo.disconnect();
            footerEnterIo = null;
        }
        footerEnterIo = new IntersectionObserver(
            (entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) return;
                playFooterEntrance(footer);
                footerEnterIo?.disconnect();
                footerEnterIo = null;
            },
            { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
        );
        footerEnterIo.observe(footer);
    };
    const loader = document.getElementById('evomi-loader');
    const loaderDone =
        !loader ||
        loader.classList.contains('is-hidden') ||
        loader.classList.contains('is-fading') ||
        !document.documentElement.classList.contains('evomi-loading');
    if (loaderDone) {
        observe();
        return;
    }
    window.addEventListener('evomi:loader-done', observe, { once: true });
}

/** Soft-nav / hard-load routes that should feel instant (no leave fade / full loader). */
function shouldSkipPageLoadingFeel(pathname) {
    return (
        isArtikelDetailPath(pathname) ||
        isKuisPath(pathname) ||
        isCheckoutPath(pathname) ||
        isPaymentPath(pathname)
    );
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
            groups.find((g) => orderInvoiceRoot(String(g.groupId)) === orderInvoiceRoot(id)) ||
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

const AUTH_KEYS = ['auth_token', 'auth_user', 'user', 'auth_expires_at'];

/**
 * Sesi login disimpan di salah satu dari dua tempat, sesuai centang
 * "Biarkan saya tetap masuk": localStorage bertahan setelah browser ditutup,
 * sessionStorage ikut hilang bersama tabnya. Pembacaan selalu memeriksa
 * keduanya supaya kode pemanggil tidak perlu tahu yang mana dipakai.
 */
function authStores() {
    const out = [];
    try { if (window.localStorage) out.push(window.localStorage); } catch {}
    try { if (window.sessionStorage) out.push(window.sessionStorage); } catch {}
    return out;
}

function authReadRaw(key) {
    for (const store of authStores()) {
        try {
            const value = store.getItem(key);
            if (value) return value;
        } catch {}
    }
    return '';
}

/** Tempat sesi sekarang berada; localStorage kalau belum ada apa-apa. */
function activeAuthStore() {
    for (const store of authStores()) {
        try {
            if (store.getItem('auth_token')) return store;
        } catch {}
    }
    try { return window.localStorage; } catch { return null; }
}

/** Token yang sudah lewat masa berlakunya dibuang, bukan dikirim lalu ditolak 401. */
function authSessionExpired() {
    const raw = authReadRaw('auth_expires_at');
    if (!raw) return false;
    const at = Date.parse(raw);
    return Number.isFinite(at) && at <= Date.now();
}

function getAuthToken() {
    if (authSessionExpired()) {
        clearAuthSession();
        return '';
    }
    return authReadRaw('auth_token') || '';
}

function getAuthUser() {
    try {
        const raw = authReadRaw('auth_user') || authReadRaw('user');
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

/**
 * `remember` null berarti pertahankan pilihan yang sudah dipakai sesi ini -
 * dipakai saat data user disegarkan dengan token yang sama.
 */
function setAuthSession(token, user, remember = null, expiresAt = null) {
    const store = remember === null
        ? activeAuthStore()
        : (remember ? window.localStorage : window.sessionStorage);

    if (!store) return;

    // Bersihkan tempat satunya supaya tidak ada sisa token ganda yang terbaca
    // lebih dulu dan membuat sesi seolah hidup kembali.
    for (const other of authStores()) {
        if (other === store) continue;
        try { AUTH_KEYS.forEach((k) => other.removeItem(k)); } catch {}
    }

    try {
        store.setItem('auth_token', token);
        store.setItem('auth_user', JSON.stringify(user));
        if (expiresAt) store.setItem('auth_expires_at', expiresAt);
        if (user?.is_admin) {
            store.setItem('user', JSON.stringify(user));
        } else {
            store.removeItem('user');
        }
    } catch {}

    window.dispatchEvent(new Event('auth-change'));
}

function clearAuthSession() {
    for (const store of authStores()) {
        try { AUTH_KEYS.forEach((k) => store.removeItem(k)); } catch {}
    }
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

/** Local calendar day YYYY-MM-DD (bukan UTC — penting untuk WIB). */
function localDayKey(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function parseLocalDayKey(dayKey) {
    const [y, m, d] = String(dayKey)
        .split('-')
        .map((part) => Number(part));
    return new Date(y, m - 1, d);
}

function startOfLocalWeek(date) {
    const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    // Senin sebagai awal minggu (umum di ID)
    const day = d.getDay();
    const diff = day === 0 ? -6 : 1 - day;
    d.setDate(d.getDate() + diff);
    return d;
}

function salesPeriodKey(date, period) {
    if (period === 'month') {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        return `${y}-${m}`;
    }
    if (period === 'week') {
        return localDayKey(startOfLocalWeek(date));
    }
    return localDayKey(date);
}

function formatSalesPeriodLabel(periodKey, period) {
    if (period === 'month') {
        const [y, m] = periodKey.split('-').map(Number);
        return new Date(y, m - 1, 1).toLocaleDateString('id-ID', {
            month: 'short',
            year: 'numeric',
        });
    }
    if (period === 'week') {
        const start = parseLocalDayKey(periodKey);
        const end = new Date(start);
        end.setDate(end.getDate() + 6);
        const a = start.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        const b = end.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        return `${a} – ${b}`;
    }
    return parseLocalDayKey(periodKey).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });
}

function nextSalesPeriodCursor(cursor, period) {
    if (period === 'month') {
        cursor.setMonth(cursor.getMonth() + 1);
        return;
    }
    if (period === 'week') {
        cursor.setDate(cursor.getDate() + 7);
        return;
    }
    cursor.setDate(cursor.getDate() + 1);
}

function salesPeriodUnitLabel(period, count) {
    if (period === 'month') return `${count} bulan`;
    if (period === 'week') return `${count} minggu`;
    return `${count} hari`;
}

function startOfLocalDay(date = new Date()) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function startOfLocalMonth(date = new Date()) {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

/** Rentang sumbu X: selalu banyak titik tanggal (mirip tren Next.js), berakhir hari ini. */
function salesChartRange(mode, salesKeys) {
    const today = startOfLocalDay();
    let end;
    let start;

    if (mode === 'month') {
        end = startOfLocalMonth(today);
        start = new Date(end);
        start.setMonth(start.getMonth() - 5); // 6 bulan
        if (salesKeys.length) {
            const [y, m] = salesKeys[0].split('-').map(Number);
            const first = new Date(y, m - 1, 1);
            if (first < start) start = first;
        }
    } else if (mode === 'week') {
        end = startOfLocalWeek(today);
        start = new Date(end);
        start.setDate(start.getDate() - 7 * 7); // 8 minggu
        if (salesKeys.length) {
            const first = startOfLocalWeek(parseLocalDayKey(salesKeys[0]));
            if (first < start) start = first;
        }
    } else {
        end = today;
        start = new Date(today);
        start.setDate(start.getDate() - 13); // 14 hari
        if (salesKeys.length) {
            const first = parseLocalDayKey(salesKeys[0]);
            if (first < start) start = first;
        }
        // Minimal 7 titik supaya garis tidak pernah “datar 1 titik”
        const minStart = new Date(today);
        minStart.setDate(minStart.getDate() - 6);
        if (start > minStart) start = minStart;
    }

    return { start, end };
}

/**
 * Agregasi pendapatan berhasil per hari / minggu / bulan (tanggal lokal).
 * Sumbu X diisi rentang tanggal berurutan (termasuk 0) agar grafik dinamis.
 * Total = seluruh pembayaran berhasil (sinkron kartu pendapatan).
 */
function buildSalesSeries(ordersList, period = 'day') {
    const mode = period === 'week' || period === 'month' ? period : 'day';
    const salesByPeriod = {};
    let totalRevenue = 0;

    for (const order of ordersList) {
        if (!isSuccessfulPayment(order.payment_status)) continue;
        const created = new Date(order.created_at);
        if (Number.isNaN(created.getTime())) continue;

        const key = salesPeriodKey(created, mode);
        const amount = orderGrandTotal(order);
        if (!salesByPeriod[key]) salesByPeriod[key] = 0;
        salesByPeriod[key] += amount;
        totalRevenue += amount;
    }

    const keys = Object.keys(salesByPeriod).sort();
    if (!keys.length) {
        return { chartData: [], tableRows: [], totalRevenue: 0, period: mode };
    }

    const { start, end } = salesChartRange(mode, keys);
    const endKey = salesPeriodKey(end, mode);
    const cursor = new Date(start.getTime());
    const chartData = [];

    while (salesPeriodKey(cursor, mode) <= endKey) {
        const key = salesPeriodKey(cursor, mode);
        chartData.push({
            name: formatSalesPeriodLabel(key, mode),
            dayKey: key,
            total: Number(salesByPeriod[key] || 0),
        });
        nextSalesPeriodCursor(cursor, mode);
        if (chartData.length > 800) break;
    }

    const tableRows = [...chartData]
        .filter((row) => row.total > 0)
        .reverse()
        .map((row) => ({
            name: row.name,
            dayKey: row.dayKey,
            total: row.total,
        }));

    return {
        chartData,
        tableRows,
        totalRevenue: Math.round(totalRevenue),
        period: mode,
    };
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

function isCodPayment(order) {
    if (!order || typeof order !== 'object') return false;
    if (order.is_cod_payment === true || order.is_cod === true) return true;
    const channel = String(order.payment_channel || '').toLowerCase().trim();
    if (channel === 'cod') return true;
    const method = String(
        order.metode_pembayaran || order.payment_method || '',
    )
        .toLowerCase()
        .trim();
    if (!method) return false;
    return method.includes('cash on delivery') || /(^|\b)cod(\b|$)/.test(method);
}

function formatRupiah(value) {
    const numberValue = Number(value) || 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(numberValue);
}

/** Satu potongan per checkout/keranjang (persentase diprioritaskan, lalu nominal tetap). */
function checkoutPromoDiscount(subtotal, promo) {
    const sub = Math.max(0, Number(subtotal) || 0);
    if (sub <= 0 || !promo) return 0;
    const percent = Math.max(0, Number(promo.persentase_promo) || 0);
    const flat = Math.max(0, Number(promo.harga_promo) || 0);
    let amount = 0;
    if (percent > 0) {
        amount = Math.round(sub * (percent / 100) * 100) / 100;
    } else if (flat > 0) {
        amount = flat;
    }
    return Math.min(Math.max(0, amount), sub);
}

function fulfillmentStatusConfig(status) {
    const normalizedStatus = String(status || '').toLowerCase();
    switch (normalizedStatus) {
        case 'dibatalkan':
            return {
                label: storefrontL('Dibatalkan', 'Cancelled'),
                class: 'bg-red-50 text-red-700 border-red-200',
                dot: 'bg-red-500',
            };
        case 'menunggu_konfirmasi':
            return {
                label: storefrontL('Menunggu Konfirmasi', 'Awaiting Confirmation'),
                class: 'bg-amber-50 text-amber-700 border-amber-200',
                dot: 'bg-amber-500',
            };
        case 'pengemasan':
            return {
                label: storefrontL('Pengemasan', 'Packaging'),
                class: 'bg-blue-50 text-blue-700 border-blue-200',
                dot: 'bg-blue-500',
            };
        case 'dalam_perjalanan':
            return {
                label: storefrontL('Dalam Perjalanan', 'In Transit'),
                class: 'bg-violet-50 text-violet-700 border-violet-200',
                dot: 'bg-violet-500',
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
    if (status === 'success') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    if (status === 'cancelled') return 'bg-rose-50 text-rose-700 border-rose-200';
    return 'bg-amber-50 text-amber-700 border-amber-200';
}

function paymentStatusLabel(status) {
    if (status === 'success') return storefrontL('Sudah dibayar', 'Paid');
    if (status === 'cancelled') return storefrontL('Dibatalkan', 'Cancelled');
    return storefrontL('Belum dibayar', 'Unpaid');
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

/**
 * YouTube-live style floating stars that rise, sway, then streak into a target.
 * Used for cart catch and drawer "Lacak Pesanan" transitions.
 *
 * @param {{ imageUrl?: string, accent?: string, sourceEl?: Element|null, targetEl?: Element|null, particleCount?: number, mode?: 'cart'|'track' }} opts
 */
function flyToCart(opts = {}) {
    return flyStarsToTarget({
        ...opts,
        mode: opts.mode || 'cart',
        targetEl: opts.targetEl || getVisibleCartButton(),
    });
}

/**
 * @param {{ imageUrl?: string, accent?: string, sourceEl?: Element|null, targetEl?: Element|null, particleCount?: number, mode?: 'cart'|'track' }} opts
 */
function flyStarsToTarget(opts = {}) {
    const {
        imageUrl = '',
        accent = '#1172BA',
        sourceEl = null,
        targetEl = null,
        particleCount = null,
        mode = 'cart',
    } = opts;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const target = targetEl || getVisibleCartButton();

    if (reduceMotion || !target) {
        if (mode === 'cart') bumpCartCatch(target);
        else bumpDrawerTabCatch('track');
        return Promise.resolve();
    }

    const fromRect = resolveFlySourceRect(sourceEl);
    const toRect = target.getBoundingClientRect();
    if (toRect.width < 2 || toRect.height < 2) {
        if (mode === 'cart') bumpCartCatch(target);
        else bumpDrawerTabCatch('track');
        return Promise.resolve();
    }

    if (mode === 'cart' || target.classList.contains('nav-cart-btn')) {
        setCartMagnet(target, true);
    } else {
        target.classList.add('is-magnet');
    }

    const layer = document.createElement('div');
    layer.className = 'evomi-cart-fly';
    layer.dataset.flyMode = mode;
    layer.setAttribute('aria-hidden', 'true');
    document.body.appendChild(layer);

    const startX = fromRect.left + fromRect.width / 2;
    const startY = fromRect.top + fromRect.height / 2;
    const endX = toRect.left + toRect.width / 2;
    const endY = toRect.top + toRect.height / 2;

    const isMobile = window.matchMedia('(max-width: 767px)').matches;
    const count = particleCount ?? (mode === 'cart'
        ? 0
        : (isMobile ? 10 : 14));
    const floatH = mode === 'cart'
        ? (isMobile ? 120 : 170)
        : (isMobile ? 110 : 160);
    const jobs = [];
    const palette = reactionPalette(accent);

    if (mode === 'cart') {
        const thumb = document.createElement('div');
        thumb.className = 'evomi-cart-fly__orb evomi-cart-fly__orb--hero';
        thumb.style.setProperty('--fly-accent', accent);
        thumb.innerHTML =
            '<span class="evomi-cart-fly__orb-shine" aria-hidden="true"></span>' +
            '<span class="evomi-cart-fly__orb-rim" aria-hidden="true"></span>' +
            '<span class="evomi-cart-fly__orb-core"></span>';
        const core = thumb.querySelector('.evomi-cart-fly__orb-core');
        if (imageUrl) {
            const img = document.createElement('img');
            img.src = imageUrl;
            img.alt = '';
            img.draggable = false;
            core.appendChild(img);
        } else {
            thumb.classList.add('evomi-cart-fly__orb--empty');
            core.innerHTML = REACTION_STAR_SVG;
        }
        layer.appendChild(thumb);
        jobs.push(
            animateCartBubble(thumb, {
                startX,
                startY,
                endX,
                endY,
                duration: isMobile ? 1180 : 1420,
                sizeScale: isMobile ? 1.08 : 1.22,
            }),
        );

        // Soft companion 3D bubbles that trail into the cart
        const trailCount = isMobile ? 4 : 6;
        for (let i = 0; i < trailCount; i++) {
            const kind = REACTION_KINDS[i % REACTION_KINDS.length];
            const color = palette[i % palette.length];
            const el = document.createElement('span');
            el.className = `evomi-cart-fly__react evomi-cart-fly__react--3d evomi-cart-fly__react--${kind.key} evomi-cart-fly__react--tone${i % 5}`;
            el.style.setProperty('--fly-accent', color);
            el.style.setProperty('--react-face', color);
            el.innerHTML =
                `<span class="evomi-cart-fly__react-bubble" aria-hidden="true"></span>` +
                `<span class="evomi-cart-fly__react-icon">${kind.svg}</span>`;
            layer.appendChild(el);

            const side = i % 2 === 0 ? -1 : 1;
            jobs.push(
                animateCartBubble(el, {
                    startX: startX + side * (10 + i * 6) + (Math.random() * 10 - 5),
                    startY: startY + (Math.random() * 12 - 4),
                    endX: endX + (Math.random() * 8 - 4),
                    endY: endY + (Math.random() * 6 - 3),
                    duration: (isMobile ? 1080 : 1280) + i * 70,
                    delay: 70 + i * 55,
                    sizeScale: 0.55 + (i % 4) * 0.08,
                    isOrb: false,
                    sway: side * (28 + i * 8),
                }),
            );
        }
    } else {
        for (let i = 0; i < count; i++) {
            const kind = REACTION_KINDS[i % REACTION_KINDS.length];
            const color = palette[i % palette.length];
            const el = document.createElement('span');
            el.className = `evomi-cart-fly__react evomi-cart-fly__react--${kind.key} evomi-cart-fly__react--tone${i % 5}`;
            el.style.setProperty('--fly-accent', color);
            el.style.setProperty('--react-face', color);
            el.innerHTML =
                `<span class="evomi-cart-fly__react-bubble" aria-hidden="true"></span>` +
                `<span class="evomi-cart-fly__react-icon">${kind.svg}</span>`;
            layer.appendChild(el);

            const side = i % 2 === 0 ? -1 : 1;
            const sway = (isMobile ? 48 : 68) * side * (0.55 + (i % 5) * 0.14) + (Math.random() * 18 - 9);
            const sizeScale = 0.55 + (i % 6) * 0.1 + Math.random() * 0.08;
            const delay = 40 + i * (isMobile ? 55 : 70);
            const duration = (isMobile ? 1180 : 1420) + (i % 5) * 110 + Math.random() * 80;
            const float = floatH * (0.72 + (i % 5) * 0.09) + Math.random() * 24;
            const rotate = side * (12 + (i % 4) * 8);
            const wobble = (isMobile ? 18 : 28) * (0.6 + (i % 3) * 0.2);

            jobs.push(
                animateReactionParticle(el, {
                    startX: startX + (Math.random() * 28 - 14),
                    startY: startY + (Math.random() * 16 - 6),
                    endX: endX + (Math.random() * 12 - 6),
                    endY: endY + (Math.random() * 10 - 5),
                    floatH: float,
                    sway,
                    delay,
                    duration,
                    sizeScale,
                    rotate,
                    isOrb: false,
                    wobble,
                }),
            );
        }
    }

    const catchAt = mode === 'cart'
        ? (isMobile ? 900 : 1100)
        : ((isMobile ? 980 : 1180) + count * 24);
    window.setTimeout(() => {
        if (mode === 'cart' || target.classList.contains('nav-cart-btn')) {
            bumpCartCatch(target, accent);
        } else {
            bumpDrawerTabCatch(mode === 'track' ? 'track' : 'cart', accent);
        }
        spawnCatchSparkles(target, accent, mode === 'cart' ? 8 : 10);
    }, catchAt);

    return Promise.allSettled(jobs).then(() => {
        layer.remove();
        if (mode === 'cart' || target.classList.contains('nav-cart-btn')) {
            setCartMagnet(target, false);
        } else {
            target.classList.remove('is-magnet');
        }
    });
}

function reactionPalette(accent) {
    const base = String(accent || '#1172BA');
    return [
        base,
        '#FF5A7A', // love / YT like
        '#FFB020', // wow / warm
        '#5AC8FA', // like blue
        '#A78BFA', // soft violet
        '#34D399', // care / soft green
        '#FFFFFF',
    ];
}

const REACTION_STAR_SVG =
    '<svg viewBox="0 0 24 24" width="100%" height="100%" aria-hidden="true" focusable="false">' +
    '<path fill="currentColor" d="M12 2.1l2.55 6.2 6.75.55-5.15 4.45 1.55 6.55L12 16.7 6.3 19.85l1.55-6.55L2.7 8.85l6.75-.55L12 2.1z"/>' +
    '</svg>';

const REACTION_HEART_SVG =
    '<svg viewBox="0 0 24 24" width="100%" height="100%" aria-hidden="true" focusable="false">' +
    '<path fill="currentColor" d="M12 21.2s-7.4-4.55-9.7-9.05C.5 8.9 1.85 5.45 5.1 4.55c1.85-.5 3.75.15 5.05 1.55C11.45 4.7 13.35 4.05 15.2 4.55c3.25.9 4.6 4.35 2.8 7.6C19.4 16.65 12 21.2 12 21.2z"/>' +
    '</svg>';

const REACTION_SPARK_SVG =
    '<svg viewBox="0 0 24 24" width="100%" height="100%" aria-hidden="true" focusable="false">' +
    '<path fill="currentColor" d="M12 1.5l1.55 7.05L20.5 12l-6.95 3.45L12 22.5l-1.55-7.05L3.5 12l6.95-3.45L12 1.5z"/>' +
    '</svg>';

const REACTION_BURST_SVG =
    '<svg viewBox="0 0 24 24" width="100%" height="100%" aria-hidden="true" focusable="false">' +
    '<path fill="currentColor" d="M12 3.2l1.2 4.35 4.55.2-3.55 2.85 1.15 4.4L12 12.9 8.65 15l1.15-4.4L6.25 7.75l4.55-.2L12 3.2zm0 9.05l.55 2 2.05.1-1.6 1.3.5 2L12 16.4l-1.55 1.25.5-2-1.6-1.3 2.05-.1.55-2z"/>' +
    '</svg>';

const REACTION_KINDS = [
    { key: 'star', svg: REACTION_STAR_SVG },
    { key: 'heart', svg: REACTION_HEART_SVG },
    { key: 'spark', svg: REACTION_SPARK_SVG },
    { key: 'star', svg: REACTION_STAR_SVG },
    { key: 'burst', svg: REACTION_BURST_SVG },
    { key: 'heart', svg: REACTION_HEART_SVG },
];

const CART_STAR_SVG = REACTION_STAR_SVG;
const TRACK_STAR_SVG = REACTION_SPARK_SVG;

/**
 * Smooth 3D-style bubble flight into the cart.
 * Soft pop → gentle arc float → eased swoop toward the cart icon.
 */
function animateCartBubble(el, cfg) {
    const {
        startX,
        startY,
        endX,
        endY,
        duration = 1320,
        delay = 0,
        sizeScale = 1,
        isOrb = true,
        sway = 0,
    } = cfg;

    const dx = endX - startX;
    const dy = endY - startY;
    const arcLift = Math.min(150, Math.max(78, Math.abs(dy) * 0.2 + 86));
    const sideBias =
        sway ||
        (Math.abs(dx) < 8
            ? (Math.random() > 0.5 ? 1 : -1) * 32
            : Math.sign(dx) * Math.min(52, Math.abs(dx) * 0.14 + 24));

    const pointAt = (t) => {
        const u = t * t * (3 - 2 * t);
        const lift = Math.sin(Math.PI * t) * arcLift * (1 - u * 0.2);
        const swayX = Math.sin(Math.PI * t) * sideBias * (1 - u * 0.55);
        return {
            x: startX + dx * u + swayX,
            y: startY + dy * u - lift,
        };
    };

    const startScale = isOrb ? 0.2 : 0.16 * sizeScale;
    const popScale = isOrb ? 1.32 * sizeScale : 1.12 * sizeScale;
    const cruiseScale = isOrb ? 1.06 * sizeScale : 0.9 * sizeScale;
    const endScale = isOrb ? 0.12 : 0.07;

    const frames = [];
    const steps = [0, 0.07, 0.14, 0.24, 0.36, 0.48, 0.6, 0.72, 0.84, 0.92, 1];
    for (const t of steps) {
        const p = pointAt(t);
        let scale;
        let rot;
        let opacity;
        let brightness;
        let blur;

        if (t <= 0.07) {
            const k = t / 0.07;
            scale = startScale + (popScale - startScale) * k;
            opacity = k;
            rot = -12 + 20 * k;
            brightness = 1.05 + 0.22 * k;
            blur = 0;
        } else if (t <= 0.24) {
            const k = (t - 0.07) / 0.17;
            scale = popScale + (cruiseScale - popScale) * k;
            opacity = 1;
            rot = 8 - 14 * k;
            brightness = 1.27 - 0.1 * k;
            blur = 0;
        } else if (t <= 0.6) {
            const k = (t - 0.24) / 0.36;
            scale = cruiseScale * (1 - 0.04 * k);
            opacity = 1;
            rot = -6 + Math.sin(k * Math.PI * 2) * 7;
            brightness = 1.16;
            blur = 0;
        } else {
            const k = (t - 0.6) / 0.4;
            const ease = k * k * (3 - 2 * k);
            scale = cruiseScale * 0.96 * (1 - ease) + endScale * ease;
            opacity = 1 - ease * 0.95;
            rot = 6 + ease * 26;
            brightness = 1.18 + ease * 0.4;
            blur = ease * 0.45;
        }

        frames.push({
            offset: t,
            opacity,
            transform: `translate3d(${p.x}px, ${p.y}px, 0) translate(-50%, -50%) scale(${scale}) rotate(${rot}deg)`,
            filter: `blur(${blur}px) brightness(${brightness})`,
        });
    }

    el.style.left = '0px';
    el.style.top = '0px';
    el.style.opacity = '0';
    el.style.transform = `translate3d(${startX}px, ${startY}px, 0) translate(-50%, -50%) scale(${startScale})`;

    return el.animate(frames, {
        duration,
        delay,
        easing: 'linear',
        fill: 'forwards',
    }).finished;
}

/**
 * Facebook reaction / YouTube-live like motion:
 * pop → float up with sway → hang → swoop into cart.
 */
function animateReactionParticle(el, cfg) {
    const {
        startX,
        startY,
        endX,
        endY,
        floatH,
        sway,
        delay,
        duration,
        sizeScale,
        rotate,
        isOrb,
        wobble = 20,
    } = cfg;

    const rise1X = startX + sway * 0.35;
    const rise1Y = startY - floatH * 0.38;
    const peakX = startX + sway;
    const peakY = startY - floatH;
    const hangX = peakX + wobble * 0.35;
    const hangY = peakY - 8;
    const midX = hangX + (endX - hangX) * 0.48;
    const midY = hangY + (endY - hangY) * 0.42;

    const pop = isOrb ? 0.9 : 0.42 * sizeScale;
    const floatScale = isOrb ? 0.76 : 1.05 * sizeScale;
    const endScale = isOrb ? 0.12 : 0.1;

    el.style.left = '0px';
    el.style.top = '0px';
    el.style.opacity = '0';
    el.style.transform = `translate(${startX}px, ${startY}px) translate(-50%, -50%) scale(${pop * 0.2})`;

    return el.animate(
        [
            {
                // Spawn tiny under fingertip
                offset: 0,
                opacity: 0,
                transform: `translate(${startX}px, ${startY + 8}px) translate(-50%, -50%) scale(${pop * 0.15}) rotate(${-rotate}deg)`,
                filter: 'blur(0px) brightness(1)',
            },
            {
                // Elastic pop (FB reaction)
                offset: 0.1,
                opacity: 1,
                transform: `translate(${startX + sway * 0.05}px, ${startY - 14}px) translate(-50%, -50%) scale(${pop * 1.45}) rotate(${rotate * 0.2}deg)`,
                filter: 'blur(0px) brightness(1.2)',
            },
            {
                // Settle after bounce
                offset: 0.18,
                opacity: 1,
                transform: `translate(${startX + sway * 0.12}px, ${startY - 28}px) translate(-50%, -50%) scale(${floatScale}) rotate(${rotate * 0.35}deg)`,
                filter: 'blur(0px) brightness(1.12)',
            },
            {
                // Rise + sway (YouTube like)
                offset: 0.4,
                opacity: 1,
                transform: `translate(${rise1X}px, ${rise1Y}px) translate(-50%, -50%) scale(${floatScale * 1.06}) rotate(${-rotate * 0.4}deg)`,
                filter: 'blur(0px) brightness(1.15)',
            },
            {
                // Peak float
                offset: 0.55,
                opacity: 0.98,
                transform: `translate(${peakX}px, ${peakY}px) translate(-50%, -50%) scale(${floatScale}) rotate(${rotate * 0.55}deg)`,
                filter: 'blur(0px) brightness(1.18)',
            },
            {
                // Soft hang / wobble
                offset: 0.66,
                opacity: 0.95,
                transform: `translate(${hangX}px, ${hangY}px) translate(-50%, -50%) scale(${floatScale * 0.96}) rotate(${-rotate * 0.25}deg)`,
                filter: 'blur(0.15px) brightness(1.22)',
            },
            {
                // Falling-star swoop to cart
                offset: 0.84,
                opacity: 0.82,
                transform: `translate(${midX}px, ${midY}px) translate(-50%, -50%) scale(${floatScale * 0.55}) rotate(${rotate * 0.9}deg)`,
                filter: 'blur(0.35px) brightness(1.35)',
            },
            {
                offset: 1,
                opacity: 0,
                transform: `translate(${endX}px, ${endY}px) translate(-50%, -50%) scale(${endScale}) rotate(${rotate * 1.2}deg)`,
                filter: 'blur(0.8px) brightness(1.45)',
            },
        ],
        {
            duration,
            delay,
            easing: 'cubic-bezier(0.2, 0.85, 0.25, 1)',
            fill: 'forwards',
        },
    ).finished;
}

/** @deprecated alias — keep callers working */
function animateCartStar(el, cfg) {
    return animateReactionParticle(el, { wobble: 18, ...cfg });
}

function resolveFlySourceRect(sourceEl) {
    if (sourceEl && typeof sourceEl.getBoundingClientRect === 'function') {
        const r = sourceEl.getBoundingClientRect();
        if (r.width > 2 && r.height > 2) return r;
    }
    const modalImg = document.querySelector('.evomi-product-modal__image');
    if (modalImg) {
        const r = modalImg.getBoundingClientRect();
        if (r.width > 2 && r.height > 2) return r;
    }
    const detailImg = document.querySelector('[data-belanja-hero-image], .belanja-detail__image, .belanja-detail img');
    if (detailImg) {
        const r = detailImg.getBoundingClientRect();
        if (r.width > 2 && r.height > 2) return r;
    }
    return {
        left: window.innerWidth / 2 - 24,
        top: window.innerHeight * 0.45,
        width: 48,
        height: 48,
    };
}

function getVisibleCartButton() {
    const buttons = [...document.querySelectorAll('.nav-cart-btn')];
    return (
        buttons.find((btn) => {
            const r = btn.getBoundingClientRect();
            const style = window.getComputedStyle(btn);
            if (style.display === 'none' || style.visibility === 'hidden' || Number(style.opacity) === 0) {
                return false;
            }
            return r.width > 2 && r.height > 2 && r.bottom > 0 && r.top < window.innerHeight;
        }) || buttons[0] || null
    );
}

function getDrawerTabButton(tab) {
    return document.querySelector(`[data-drawer-tab="${tab}"]`);
}

function setCartMagnet(cartBtn, on) {
    const btn = cartBtn || getVisibleCartButton();
    if (!btn) return;
    btn.classList.toggle('nav-cart-btn--magnet', Boolean(on));
}

function bumpCartCatch(cartBtn, accent = '#1172BA') {
    const btn = cartBtn || getVisibleCartButton();
    if (!btn) return;
    btn.style.setProperty('--fly-accent', accent);
    btn.classList.remove('nav-cart-btn--catch');
    void btn.offsetWidth;
    btn.classList.add('nav-cart-btn--catch', 'nav-cart-btn--magnet');
    window.setTimeout(() => {
        btn.classList.remove('nav-cart-btn--catch');
        // keep hover glow briefly after bounce
        window.setTimeout(() => btn.classList.remove('nav-cart-btn--magnet'), 420);
    }, 900);
}

function bumpDrawerTabCatch(tab = 'track', accent = '#1172BA') {
    const btn = getDrawerTabButton(tab);
    if (!btn) return;
    btn.style.setProperty('--fly-accent', accent);
    btn.classList.remove('is-catch');
    void btn.offsetWidth;
    btn.classList.add('is-catch', 'is-magnet');
    window.setTimeout(() => {
        btn.classList.remove('is-catch');
        window.setTimeout(() => btn.classList.remove('is-magnet'), 380);
    }, 850);
}

function spawnCatchSparkles(targetEl, accent = '#1172BA', count = 12) {
    if (!targetEl || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const rect = targetEl.getBoundingClientRect();
    if (rect.width < 2) return;

    const layer = document.createElement('div');
    layer.className = 'evomi-cart-fly evomi-cart-fly--spark';
    layer.setAttribute('aria-hidden', 'true');
    document.body.appendChild(layer);

    const cx = rect.left + rect.width / 2;
    const cy = rect.top + rect.height / 2;
    const jobs = [];

    for (let i = 0; i < count; i++) {
        const spark = document.createElement('span');
        spark.className = `evomi-cart-fly__spark evomi-cart-fly__spark--${i % 3}`;
        spark.style.setProperty('--fly-accent', accent);
        spark.innerHTML = CART_STAR_SVG;
        layer.appendChild(spark);

        const angle = (Math.PI * 2 * i) / count + (Math.random() * 0.35 - 0.17);
        const dist = 28 + Math.random() * 42;
        const tx = cx + Math.cos(angle) * dist;
        const ty = cy + Math.sin(angle) * dist - 8;
        const size = 0.45 + Math.random() * 0.55;

        spark.style.left = '0px';
        spark.style.top = '0px';
        jobs.push(
            spark.animate(
                [
                    {
                        offset: 0,
                        opacity: 0,
                        transform: `translate(${cx}px, ${cy}px) translate(-50%, -50%) scale(${0.2 * size}) rotate(0deg)`,
                    },
                    {
                        offset: 0.18,
                        opacity: 1,
                        transform: `translate(${cx + Math.cos(angle) * 8}px, ${cy + Math.sin(angle) * 8}px) translate(-50%, -50%) scale(${1.15 * size}) rotate(${angle * 40}deg)`,
                    },
                    {
                        offset: 1,
                        opacity: 0,
                        transform: `translate(${tx}px, ${ty}px) translate(-50%, -50%) scale(${0.15 * size}) rotate(${angle * 90}deg)`,
                    },
                ],
                {
                    duration: 520 + Math.random() * 280,
                    delay: i * 18,
                    easing: 'cubic-bezier(0.22, 0.9, 0.3, 1)',
                    fill: 'forwards',
                },
            ).finished,
        );
    }

    Promise.allSettled(jobs).then(() => layer.remove());
}

/**
 * Twitter/X-style heart burst for wishlist add / remove.
 * @param {Element|null} sourceEl
 * @param {{ mode?: 'add'|'remove', color?: string }} [options]
 */
function spawnWishlistHeartBurst(sourceEl, options = {}) {
    if (!sourceEl || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const mode = options.mode === 'remove' ? 'remove' : 'add';
    const baseColor = String(options.color || '#FF4D6D').trim() || '#FF4D6D';
    const colors = [
        baseColor,
        shadeHexColor(baseColor, 22),
        shadeHexColor(baseColor, -14),
        shadeHexColor(baseColor, 36),
        shadeHexColor(baseColor, -24),
        shadeHexColor(baseColor, 10),
        shadeHexColor(baseColor, -8),
    ];

    const btn = sourceEl.closest?.('button') || sourceEl;
    const icon = btn.querySelector?.('svg') || btn;
    const rect = icon.getBoundingClientRect();
    if (rect.width < 2) return;

    const popClass = mode === 'remove' ? 'is-wishlist-unpop' : 'is-wishlist-pop';
    btn.classList.remove('is-wishlist-pop', 'is-wishlist-unpop');
    void btn.offsetWidth;
    btn.classList.add(popClass);
    window.setTimeout(() => btn.classList.remove(popClass), 700);

    const layer = document.createElement('div');
    layer.className = `evomi-heart-burst${mode === 'remove' ? ' is-remove' : ''}`;
    layer.setAttribute('aria-hidden', 'true');
    layer.style.setProperty('--burst-color', baseColor);
    layer.style.setProperty('--burst-color-soft', shadeHexColor(baseColor, 28));
    document.body.appendChild(layer);

    const cx = rect.left + rect.width / 2;
    const cy = rect.top + rect.height / 2;

    const ring = document.createElement('span');
    ring.className = 'evomi-heart-burst__ring';
    ring.style.left = `${cx}px`;
    ring.style.top = `${cy}px`;
    ring.style.borderColor = baseColor;
    layer.appendChild(ring);

    const ring2 = document.createElement('span');
    ring2.className = 'evomi-heart-burst__ring evomi-heart-burst__ring--soft';
    ring2.style.left = `${cx}px`;
    ring2.style.top = `${cy}px`;
    ring2.style.borderColor = shadeHexColor(baseColor, 35);
    layer.appendChild(ring2);

    const count = mode === 'remove' ? 9 : 10;
    const jobs = [];

    for (let i = 0; i < count; i++) {
        const p = document.createElement('span');
        const isDot = i % 3 === 0;
        p.className = isDot
            ? `evomi-heart-burst__dot evomi-heart-burst__dot--${i % 3}`
            : `evomi-heart-burst__heart evomi-heart-burst__heart--${i % 4}`;
        p.style.color = colors[i % colors.length];
        if (!isDot) p.innerHTML = REACTION_HEART_SVG;
        layer.appendChild(p);

        const angle = (Math.PI * 2 * i) / count + (Math.random() * 0.4 - 0.2) - Math.PI / 2;
        const dist = mode === 'remove' ? 28 + Math.random() * 40 : 36 + Math.random() * 48;
        const lift = mode === 'remove' ? -(16 + Math.random() * 34) : 18 + Math.random() * 28;
        const midX = cx + Math.cos(angle) * (dist * 0.45);
        const midY = cy + Math.sin(angle) * (dist * 0.35) - lift * 0.55;
        const endX = cx + Math.cos(angle) * dist;
        const endY = cy + Math.sin(angle) * dist - lift;
        const cruiseY = mode === 'remove' ? endY + 8 : endY - 6;
        const size = isDot ? 0.55 + Math.random() * 0.55 : 0.55 + Math.random() * 0.75;
        const rot = (Math.random() * 50 - 25) + angle * 28;

        p.style.left = '0px';
        p.style.top = '0px';

        jobs.push(
            p.animate(
                mode === 'remove'
                    ? [
                          {
                              offset: 0,
                              opacity: 1,
                              transform: `translate(${cx}px, ${cy}px) translate(-50%, -50%) scale(${1.05 * size}) rotate(0deg)`,
                          },
                          {
                              offset: 0.22,
                              opacity: 0.95,
                              transform: `translate(${midX}px, ${midY}px) translate(-50%, -50%) scale(${1.15 * size}) rotate(${rot * 0.5}deg)`,
                          },
                          {
                              offset: 0.62,
                              opacity: 0.55,
                              transform: `translate(${endX}px, ${cruiseY}px) translate(-50%, -50%) scale(${0.7 * size}) rotate(${rot}deg)`,
                          },
                          {
                              offset: 1,
                              opacity: 0,
                              transform: `translate(${endX}px, ${endY + 22}px) translate(-50%, -50%) scale(${0.2 * size}) rotate(${rot * 1.35}deg)`,
                          },
                      ]
                    : [
                          {
                              offset: 0,
                              opacity: 0,
                              transform: `translate(${cx}px, ${cy}px) translate(-50%, -50%) scale(${0.15 * size}) rotate(0deg)`,
                          },
                          {
                              offset: 0.14,
                              opacity: 1,
                              transform: `translate(${midX}px, ${midY}px) translate(-50%, -50%) scale(${1.2 * size}) rotate(${rot * 0.4}deg)`,
                          },
                          {
                              offset: 0.55,
                              opacity: 0.95,
                              transform: `translate(${endX}px, ${cruiseY}px) translate(-50%, -50%) scale(${0.95 * size}) rotate(${rot}deg)`,
                          },
                          {
                              offset: 1,
                              opacity: 0,
                              transform: `translate(${endX}px, ${endY - 14}px) translate(-50%, -50%) scale(${0.35 * size}) rotate(${rot * 1.2}deg)`,
                          },
                      ],
                {
                    duration: (mode === 'remove' ? 700 : 780) + Math.random() * 280,
                    delay: i * 14,
                    easing: mode === 'remove' ? 'cubic-bezier(0.4, 0.05, 0.35, 1)' : 'cubic-bezier(0.22, 0.9, 0.28, 1)',
                    fill: 'forwards',
                },
            ).finished,
        );
    }

    const hero = document.createElement('span');
    hero.className = 'evomi-heart-burst__hero';
    hero.style.color = baseColor;
    hero.innerHTML = REACTION_HEART_SVG;
    layer.appendChild(hero);
    jobs.push(
        hero.animate(
            mode === 'remove'
                ? [
                      {
                          offset: 0,
                          opacity: 1,
                          transform: `translate(${cx}px, ${cy}px) translate(-50%, -50%) scale(1.2)`,
                      },
                      {
                          offset: 0.28,
                          opacity: 0.85,
                          transform: `translate(${cx}px, ${cy + 6}px) translate(-50%, -50%) scale(0.95)`,
                      },
                      {
                          offset: 1,
                          opacity: 0,
                          transform: `translate(${cx}px, ${cy + 36}px) translate(-50%, -50%) scale(0.35)`,
                      },
                  ]
                : [
                      {
                          offset: 0,
                          opacity: 0,
                          transform: `translate(${cx}px, ${cy}px) translate(-50%, -50%) scale(0.2)`,
                      },
                      {
                          offset: 0.18,
                          opacity: 1,
                          transform: `translate(${cx}px, ${cy - 8}px) translate(-50%, -50%) scale(1.35)`,
                      },
                      {
                          offset: 0.55,
                          opacity: 0.9,
                          transform: `translate(${cx}px, ${cy - 28}px) translate(-50%, -50%) scale(1.05)`,
                      },
                      {
                          offset: 1,
                          opacity: 0,
                          transform: `translate(${cx}px, ${cy - 52}px) translate(-50%, -50%) scale(0.7)`,
                      },
                  ],
            {
                duration: mode === 'remove' ? 720 : 900,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                fill: 'forwards',
            },
        ).finished,
    );

    Promise.allSettled(jobs).then(() => layer.remove());
}

/**
 * Fly stars from a UI control into the cart / drawer tab, then open drawer.
 */
async function flyOpenDrawer(tab = 'cart', sourceEl = null, accent = '#1172BA') {
    const cart = getVisibleCartButton();
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!reduceMotion && cart) {
        await flyStarsToTarget({
            sourceEl,
            targetEl: cart,
            accent,
            mode: tab === 'track' ? 'track' : 'cart',
            particleCount: window.matchMedia('(max-width: 767px)').matches ? 10 : 14,
        });
    } else if (cart && tab === 'cart') {
        bumpCartCatch(cart, accent);
    }

    return { cart, tab };
}

window.evomiFlyToCart = flyToCart;
window.evomiFlyOpenDrawer = flyOpenDrawer;

function productTitle(product) {
    return product?.title || product?.name || 'Produk';
}

function productPrice(product) {
    return Number(product?.price || product?.harga || 0) || 0;
}

function productSoftAccent(product) {
    if (product?.soft_accent) return String(product.soft_accent);
    const personality = String(product?.personality_type || '').toLowerCase();
    const map = {
        prestige: '#9CD6FF',
        purpose_prestige: '#9CD6FF',
        peaceful_calm: '#C6F5B8',
        rebel_brave: '#FFBBB5',
        sweet_shy: '#F5D7E7',
    };
    return map[personality] || '#9CD6FF';
}

function productBadge(product) {
    if (product?.badge) return String(product.badge);
    const personality = String(product?.personality_type || '').toLowerCase();
    const map = {
        prestige: 'Optimis',
        purpose_prestige: 'Optimis',
        peaceful_calm: 'Damai',
        rebel_brave: 'Berani',
        sweet_shy: 'Manis',
    };
    return map[personality] || 'Evomi';
}

function productGenderLabel(product) {
    const raw = String(product?.gender || 'unisex').toLowerCase();
    if (raw === 'male' || raw === 'pria') return storefrontL('Pria', 'Male');
    if (raw === 'female' || raw === 'wanita') return storefrontL('Wanita', 'Female');
    if (raw === 'unisex') return 'Unisex';
    return product?.gender || 'Unisex';
}

// Konfigurasi marketplace disuntik server-side sebagai <script type="application/json">
// supaya config/evomi.php tetap satu-satunya sumber tautan.
let marketplaceConfigCache = null;

function marketplaceConfig() {
    if (marketplaceConfigCache) return marketplaceConfigCache;

    const el = document.getElementById('evomi-marketplace-config');

    try {
        marketplaceConfigCache = el ? JSON.parse(el.textContent || '{}') : {};
    } catch {
        marketplaceConfigCache = {};
    }

    return marketplaceConfigCache;
}

// Harga promo (harga coret + harga jual pengganti) juga disuntik server-side,
// sumbernya config/evomi.php yang sama dengan halaman detail belanja.
let pricingConfigCache = null;

function pricingConfig() {
    if (pricingConfigCache) return pricingConfigCache;

    const el = document.getElementById('evomi-pricing-config');

    try {
        pricingConfigCache = el ? JSON.parse(el.textContent || '{}') : {};
    } catch {
        pricingConfigCache = {};
    }

    return pricingConfigCache;
}

// Varian tanpa tautan -> tombolnya tidak dirender, sama seperti di halaman detail.
function productMarketplaceButtons(product) {
    const personality = String(product?.personality_type || '').toLowerCase();

    if (!personality) return [];

    const cfg = marketplaceConfig();
    const links = cfg.links?.[personality] || {};

    return (cfg.channels || [])
        .map((channel) => ({ ...channel, url: String(links[channel.key] || '').trim() }))
        .filter((channel) => channel.url !== '');
}

function productAccent(product) {
    if (product?.color) return String(product.color);
    if (product?.accent) return String(product.accent);
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

/** Shade a hex color by percent (-100..100). Negative darkens, positive lightens. */
function shadeHexColor(hex, percent = 0) {
    const raw = String(hex || '#1172BA').replace('#', '').trim();
    const full =
        raw.length === 3
            ? raw
                  .split('')
                  .map((c) => c + c)
                  .join('')
            : raw.padEnd(6, '0').slice(0, 6);
    const num = Number.parseInt(full, 16);
    if (Number.isNaN(num)) return '#1172BA';
    let r = (num >> 16) & 0xff;
    let g = (num >> 8) & 0xff;
    let b = num & 0xff;
    const amt = Math.max(-100, Math.min(100, Number(percent) || 0)) / 100;
    const mix = (channel) => {
        if (amt < 0) return Math.round(channel * (1 + amt));
        return Math.round(channel + (255 - channel) * amt);
    };
    r = Math.max(0, Math.min(255, mix(r)));
    g = Math.max(0, Math.min(255, mix(g)));
    b = Math.max(0, Math.min(255, mix(b)));
    return `#${((1 << 24) | (r << 16) | (g << 8) | b).toString(16).slice(1)}`;
}

function productImageFallback(product) {
    const personality = String(product?.personality_type || '').toLowerCase();
    const fallbacks = {
        prestige: '/src/images/section%205/purpose-prestige.webp',
        purpose_prestige: '/src/images/section%205/purpose-prestige.webp',
        peaceful_calm: '/src/images/section%205/peaceful-calm.webp',
        rebel_brave: '/src/images/section%205/rabel-brave.webp',
        sweet_shy: '/src/images/section%205/sweet-shy.webp',
    };
    return fallbacks[personality] || '/src/images/section%205/purpose-prestige.webp';
}

// Botol tunggal bertutup glossy satu warna, latar transparan.
function productBottleImage(product) {
    const personality = String(product?.personality_type || '').toLowerCase();
    const bottles = {
        prestige: '/src/images/beranda/botol-purpose-prestige.webp',
        purpose_prestige: '/src/images/beranda/botol-purpose-prestige.webp',
        peaceful_calm: '/src/images/beranda/botol-peaceful-calm.webp',
        rebel_brave: '/src/images/beranda/botol-rabel-brave.webp',
        sweet_shy: '/src/images/beranda/botol-sweet-shy.webp',
    };
    return bottles[personality] || '/src/images/beranda/botol-purpose-prestige.webp';
}

function productImage(product, prefer = 'default') {
    let path = '';
    if (prefer === 'wishlist') {
        path = product?.image_2 || product?.image_1 || product?.image || '';
    } else if (prefer === 'cart') {
        path = product?.image_2 || product?.image_1 || product?.image || '';
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

const ORDER_INVOICE_PREFIX = 'INV-';
const ORDER_INVOICE_SUFFIX_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';

function isInvoiceOrderCode(value) {
    return typeof value === 'string' && /^INV-[A-Z0-9]{6}$/.test(value.trim().toUpperCase());
}

function orderInvoiceRoot(idStr) {
    const id = String(idStr || '').trim().toUpperCase();
    const neat = id.match(/^(INV-[A-Z0-9]{6})(?:-\d+)?$/);
    if (neat) return neat[1];
    const legacy = id.match(/^(INV-\d+-\d+)(?:-\d+)?$/);
    if (legacy) return legacy[1];
    return id;
}

function orderDisplayNumberFromOrder(order) {
    if (order?.order_number) return String(order.order_number);
    if (order?.invoice) return String(order.invoice);
    return orderInvoiceRoot(order?.id || '');
}

function makePublicOrderNumber() {
    const chars = ORDER_INVOICE_SUFFIX_CHARS;
    let suffix = '';
    for (let i = 0; i < 6; i++) {
        suffix += chars[Math.floor(Math.random() * chars.length)];
    }
    return `${ORDER_INVOICE_PREFIX}${suffix}`;
}

function drawerTrackPublicNumber(row) {
    return row?.order_number || row?.invoice || row?.code || orderInvoiceRoot(row?.id);
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
            const invoiceRoot = orderInvoiceRoot(idStr);
            const orderNumber = orderDisplayNumberFromOrder(first);
            const invoice = orderNumber;
            const awaitingOnline = Boolean(first.is_awaiting_payment);
            const awaitingCod = isCodPayment(first) && pay === 'pending';
            const awaitingPay = awaitingOnline || awaitingCod;
            const paymentLabel = awaitingCod
                ? storefrontL('COD · Belum dibayar', 'COD · Unpaid')
                : awaitingOnline
                ? storefrontL('Menunggu pembayaran', 'Awaiting payment')
                : paymentStatusLabel(pay);

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
                orderNumber,
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
                paymentKey: awaitingOnline ? 'awaiting' : pay,
                paymentLabel,
                paymentClass: awaitingPay
                    ? 'bg-amber-50 text-amber-700 border-amber-200'
                    : paymentStatusBadgeClass(pay),
                showPaymentBadge: paymentLabel !== fulfill.label,
                paymentMethod: first.metode_pembayaran || '',
                isAwaitingPayment: awaitingOnline,
                isAwaitingCod: awaitingCod,
                isCod: awaitingCod || isCodPayment(first),
                paymentUrl: awaitingPay
                    ? `/pembayaran/${encodeURIComponent(invoiceRoot)}`
                    : null,
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
        return { cart: 0, wishlist: 0, history: 0, payments: 0, unread: 0 };
    }
    try {
        const res = await fetch('/api/badges', {
            headers: authHeaders(true),
            credentials: 'same-origin',
        });
        if (!res.ok) return { cart: 0, wishlist: 0, history: 0, payments: 0, unread: 0 };
        const data = await readApiJson(res);
        return {
            cart: Number(data?.data?.cart || 0),
            wishlist: Number(data?.data?.wishlist || 0),
            history: Number(data?.data?.history || 0),
            payments: Number(data?.data?.payments || 0),
            unread: Number(data?.data?.unread || 0),
        };
    } catch {
        return { cart: 0, wishlist: 0, history: 0, payments: 0, unread: 0 };
    }
}

window.evomiAuthApi = {
    getAuthToken,
    getAuthUser,
    setAuthSession,
    clearAuthSession,
    authHeaders,
};

const TRAFFIC_VISITOR_KEY = 'evomi_visitor_key_v1';
let trafficPingTimer = null;
let trafficHeartbeatTimer = null;

function getOrCreateVisitorKey() {
    try {
        let key = localStorage.getItem(TRAFFIC_VISITOR_KEY);
        if (!key || !/^[0-9a-f-]{36}$/i.test(key)) {
            key =
                typeof crypto !== 'undefined' && crypto.randomUUID
                    ? crypto.randomUUID()
                    : `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`.replace(/[xy]/g, (c) => {
                          const r = (Math.random() * 16) | 0;
                          const v = c === 'x' ? r : (r & 0x3) | 0x8;
                          return v.toString(16);
                      });
            localStorage.setItem(TRAFFIC_VISITOR_KEY, key);
        }
        return key;
    } catch {
        return null;
    }
}

async function pingSiteTraffic({ heartbeat = false } = {}) {
    if (typeof window === 'undefined') return;
    if (isDashboardPath(window.location.pathname)) return;

    const visitorKey = getOrCreateVisitorKey();
    if (!visitorKey) return;

    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    const token = getAuthToken();
    if (token) headers.Authorization = `Bearer ${token}`;

    try {
        const res = await fetch('/api/traffic/ping', {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            keepalive: true,
            body: JSON.stringify({
                visitor_key: visitorKey,
                path: window.location.pathname + window.location.search,
                full_url: window.location.href,
                referrer: document.referrer || '',
                heartbeat: Boolean(heartbeat),
            }),
        });
        if (!res.ok) return;
        const data = await res.json().catch(() => null);
        const nextKey = data?.data?.visitor_key;
        if (nextKey) {
            try {
                localStorage.setItem(TRAFFIC_VISITOR_KEY, nextKey);
            } catch {
                /* ignore */
            }
        }
    } catch {
        /* ignore tracking errors */
    }
}

function scheduleSiteTrafficPing() {
    if (trafficPingTimer) clearTimeout(trafficPingTimer);
    trafficPingTimer = setTimeout(() => {
        pingSiteTraffic({ heartbeat: false });
    }, 400);
}

// Interval harus lebih rapat dari SiteVisit::ONLINE_WINDOW_SECONDS (300 dtk)
// supaya pengunjung tidak keburu dianggap offline; 90 dtk memberi 3+ ping per
// jendela, cukup longgar bila satu ping meleset, tapi separuh beban tulis 45 dtk.
const TRAFFIC_HEARTBEAT_MS = 90000;

function startSiteTrafficHeartbeat() {
    if (trafficHeartbeatTimer) return;
    trafficHeartbeatTimer = setInterval(() => {
        if (document.visibilityState === 'hidden') return;
        pingSiteTraffic({ heartbeat: true });
    }, TRAFFIC_HEARTBEAT_MS);
}

/**
 * Satu frame, tetapi tidak pernah menggantung.
 *
 * Tab yang tersembunyi atau di-throttle berhenti memanggil
 * requestAnimationFrame, dan janji yang menunggunya tidak pernah selesai.
 * Apa pun yang berada setelahnya - membuka kelas transisi, melepas kunci
 * navigasi - ikut tertinggal, jadi selalu ada timer sebagai penyelamat.
 */
function nextFrame() {
    return new Promise((resolve) => {
        let selesai = false;
        const beres = () => {
            if (selesai) return;
            selesai = true;
            resolve();
        };
        requestAnimationFrame(beres);
        setTimeout(beres, 100);
    });
}

async function waitFrames(n = 2) {
    for (let i = 0; i < n; i += 1) {
        await nextFrame();
    }
}

const GUEST_CART_KEY = 'evomi_guest_cart_v1';
const GUEST_EMAIL_KEY = 'evomi_guest_email_v1';

function readGuestEmail() {
    try {
        const raw = String(localStorage.getItem(GUEST_EMAIL_KEY) || '').trim().toLowerCase();
        return raw.includes('@') ? raw : '';
    } catch {
        return '';
    }
}

function writeGuestEmail(email) {
    const next = String(email || '').trim().toLowerCase();
    try {
        if (next.includes('@')) localStorage.setItem(GUEST_EMAIL_KEY, next);
        else localStorage.removeItem(GUEST_EMAIL_KEY);
    } catch {
        /* ignore */
    }
    return next.includes('@') ? next : '';
}

function readGuestCart() {
    try {
        const raw = localStorage.getItem(GUEST_CART_KEY);
        const list = raw ? JSON.parse(raw) : [];
        return Array.isArray(list) ? list : [];
    } catch {
        return [];
    }
}

function writeGuestCart(items) {
    try {
        localStorage.setItem(GUEST_CART_KEY, JSON.stringify(items || []));
    } catch {
        /* ignore */
    }
    emitEvomiEvent('cart_updated');
}

function guestCartCount() {
    return readGuestCart().reduce((s, i) => s + (Number(i.quantity) || 0), 0);
}

function upsertGuestCartItem({ productId, quantity, title, price, image, accent, stock }) {
    const id = Number(productId);
    const qty = Math.max(1, Number(quantity) || 1);
    if (!Number.isFinite(id) || id <= 0) {
        throw new Error(storefrontL('Produk tidak valid.', 'Invalid product.'));
    }
    const items = readGuestCart();
    const idx = items.findIndex((i) => Number(i.product_id) === id);
    const available = Number(stock);
    if (idx >= 0) {
        const next = (Number(items[idx].quantity) || 0) + qty;
        if (Number.isFinite(available) && available > 0 && next > available) {
            throw new Error(
                storefrontL(`Stok tidak cukup. Tersedia: ${available}.`, `Insufficient stock. Available: ${available}.`),
            );
        }
        items[idx].quantity = next;
        if (title) items[idx].title = title;
        if (price != null) items[idx].price = Number(price) || items[idx].price;
        if (image) items[idx].image = image;
        if (accent) items[idx].accent = accent;
        if (Number.isFinite(available)) items[idx].stock = available;
    } else {
        if (Number.isFinite(available) && available > 0 && qty > available) {
            throw new Error(
                storefrontL(`Stok tidak cukup. Tersedia: ${available}.`, `Insufficient stock. Available: ${available}.`),
            );
        }
        items.push({
            id: `guest-${id}`,
            product_id: id,
            quantity: qty,
            title: title || `Produk #${id}`,
            price: Number(price) || 0,
            image: image || '',
            accent: accent || '#1172BA',
            stock: Number.isFinite(available) ? available : 99,
        });
    }
    writeGuestCart(items);
    return items;
}

async function mergeGuestCartIntoAccount() {
    const token = getAuthToken();
    if (!token) return;
    const guestItems = readGuestCart();
    if (!guestItems.length) return;
    for (const item of guestItems) {
        try {
            await fetch('/api/carts', {
                method: 'POST',
                headers: authHeaders(true),
                credentials: 'same-origin',
                body: JSON.stringify({
                    product_id: Number(item.product_id),
                    quantity: Number(item.quantity) || 1,
                }),
            });
        } catch {
            /* keep going */
        }
    }
    writeGuestCart([]);
}

document.addEventListener('alpine:init', () => {
    registerAdminCrud?.(Alpine, {
        authHeaders,
        readApiJson,
        apiErrorMessage,
        formatRupiah,
        storageUrl,
        mediaUrl,
        resolveAvatarUrl,
        fulfillmentStatusConfig,
        normalizePaymentStatus,
        isCodPayment,
        paymentStatusLabel,
        paymentStatusBadgeClass,
        orderGrandTotal,
        clearAuthSession,
        getAuthUser,
    });

    Alpine.store('evomiProductModal', {
        open: false,
        loading: false,
        error: '',
        product: null,
        qty: 1,
        statusMessage: '',
        statusTone: 'info',
        actionBusy: false,

        get accent() {
            if (this.loading || !this.product) return '#64748B';
            return productAccent(this.product);
        },

        get softAccent() {
            // Neutral while loading / empty — avoid #9CD6FF flash before product loads
            if (this.loading || !this.product) return '#F4F6F8';
            return productSoftAccent(this.product);
        },

        get badge() {
            return productBadge(this.product);
        },

        get stock() {
            return Number(this.product?.quantity ?? this.product?.stock ?? 0) || 0;
        },

        get isOutOfStock() {
            return this.stock < 1;
        },

        get unitPrice() {
            // Harga jual pengganti dari config; kosong -> harga produk apa adanya.
            const override = Number(pricingConfig().display) || 0;

            return override > 0 ? override : productPrice(this.product);
        },

        get priceLabel() {
            return formatRupiah(this.unitPrice);
        },

        get lineTotalLabel() {
            return formatRupiah(this.unitPrice * Math.max(1, this.qty || 1));
        },

        get ctaLabel() {
            if (this.isOutOfStock) return storefrontL('Stok habis', 'Out of stock');
            if (this.actionBusy) return storefrontL('Menambah...', 'Adding...');
            return `${storefrontL('Tambah ke Keranjang', 'Add to Cart')} · ${this.lineTotalLabel}`;
        },

        get imageUrl() {
            // Modal beranda menampilkan botol saja, bukan foto kemasan katalog.
            return productBottleImage(this.product);
        },

        get volumeLabel() {
            const size = Number(this.product?.bottle_size || 30) || 30;
            return `${size}ml`;
        },

        get longevityLabel() {
            return this.product?.longevity || storefrontL('8–12 jam', '8–12 hrs');
        },

        get typeLabel() {
            return this.product?.perfume_type || 'Eau de Parfum';
        },

        get genderLabel() {
            return productGenderLabel(this.product);
        },

        get ratingLabel() {
            const rating = this.product?.rating || '4.9';
            const reviews = this.product?.reviews_count || 128;
            return `${rating} (${reviews} ${storefrontL('ulasan', 'reviews')})`;
        },

        get scentNotes() {
            const p = this.product || {};
            return [p.top_note, p.middle_note, p.base_note].filter(Boolean);
        },

        get marketplaceButtons() {
            return productMarketplaceButtons(this.product);
        },

        get hasMarketplaces() {
            return this.marketplaceButtons.length > 0;
        },

        // Tombol dirender statis per kanal; yang tanpa tautan disembunyikan.
        marketplaceUrl(key) {
            return this.marketplaceButtons.find((mp) => mp.key === key)?.url || '';
        },

        async openProduct(productId) {
            const id = Number(productId);
            if (!Number.isFinite(id) || id <= 0) return;
            window.clearTimeout(this._closeClearTimer);
            this.open = true;
            this.loading = true;
            this.error = '';
            this.product = null;
            this.qty = 1;
            this.statusMessage = '';
            this.statusTone = 'info';
            this.actionBusy = false;
            document.documentElement.classList.add('overflow-hidden');
            try {
                const res = await fetch(`/api/products/${id}?locale=id`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await readApiJson(res);
                const product = data?.data || data;
                if (!res.ok || !product?.id) {
                    throw new Error(
                        apiErrorMessage(data, storefrontL('Produk tidak ditemukan.', 'Product not found.')),
                    );
                }
                this.product = product;
                this.qty = 1;
            } catch (err) {
                this.error =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal memuat produk.', 'Failed to load product.');
            } finally {
                this.loading = false;
            }
        },

        close() {
            if (!this.open && !this.product) return;
            this.open = false;
            this.actionBusy = false;
            document.documentElement.classList.remove('overflow-hidden');
            // Keep product/accent until leave transition finishes —
            // clearing early flashes softAccent fallback (#9CD6FF).
            window.clearTimeout(this._closeClearTimer);
            this._closeClearTimer = window.setTimeout(() => {
                if (this.open) return;
                this.loading = false;
                this.error = '';
                this.product = null;
                this.qty = 1;
                this.statusMessage = '';
                this.statusTone = 'info';
            }, 320);
        },

        changeQty(delta) {
            if (!this.product) return;
            const next = this.qty + delta;
            this.qty = Math.min(Math.max(1, next), Math.max(1, this.stock));
        },

        buyNow() {
            if (!this.product || this.isOutOfStock) return;
            const productId = Number(this.product.id);
            const params = new URLSearchParams({
                type: 'buynow',
                productId: String(productId),
                qty: String(this.qty),
                unitPrice: String(productPrice(this.product)),
                productDiscount: '0',
            });
            const qs = params.toString();
            try {
                sessionStorage.setItem('evomi_checkout_qs', qs);
            } catch {
                /* ignore */
            }
            this.close();
            softNavigate(`/checkout?${qs}`);
        },

        async addToCart() {
            if (!this.product || this.isOutOfStock || this.actionBusy) return;
            this.actionBusy = true;
            this.statusMessage = storefrontL('Menambah...', 'Adding...');
            this.statusTone = 'info';
            try {
                const payload = {
                    productId: this.product.id,
                    quantity: this.qty,
                    title: productTitle(this.product),
                    price: productPrice(this.product),
                    image: productImage(this.product, 'cart'),
                    accent: productAccent(this.product),
                    stock: this.stock,
                };
                if (getAuthToken()) {
                    const res = await fetch('/api/carts', {
                        method: 'POST',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            product_id: payload.productId,
                            quantity: payload.quantity,
                        }),
                    });
                    const data = await readApiJson(res);
                    if (!res.ok) {
                        throw new Error(
                            apiErrorMessage(data, storefrontL('Gagal menambah ke keranjang.', 'Failed to add to cart.')),
                        );
                    }
                    emitEvomiEvent('cart_updated');
                } else {
                    upsertGuestCartItem(payload);
                    this.statusMessage = storefrontL('Ditambahkan ke keranjang!', 'Added to cart!');
                    this.statusTone = 'success';
                    await flyToCart({
                        imageUrl: payload.image,
                        accent: payload.accent || '#1172BA',
                        sourceEl:
                            document.querySelector('.evomi-product-modal__image') ||
                            document.querySelector('.evomi-product-modal__cta'),
                    });
                    this.close();
                    const nav = window.__evomiNav;
                    if (nav && typeof nav.showGuestCartWarning === 'function') {
                        nav.showGuestCartWarning();
                    } else if (nav && typeof nav.openAccountDrawer === 'function') {
                        nav.openAccountDrawer();
                    }
                    return;
                }
                this.statusMessage = storefrontL('Ditambahkan ke keranjang!', 'Added to cart!');
                this.statusTone = 'success';
                await flyToCart({
                    imageUrl: payload.image,
                    accent: payload.accent || '#1172BA',
                    sourceEl:
                        document.querySelector('.evomi-product-modal__image') ||
                        document.querySelector('.evomi-product-modal__cta'),
                });
                this.close();
                window.setTimeout(() => {
                    const nav = window.__evomiNav;
                    if (nav && typeof nav.openAccountDrawer === 'function') {
                        nav.openAccountDrawer();
                    }
                }, 280);
            } catch (err) {
                this.statusMessage =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal menambah ke keranjang.', 'Failed to add to cart.');
                this.statusTone = 'error';
            } finally {
                this.actionBusy = false;
            }
        },
    });

    Alpine.store('evomiTrackModal', {
        open: false,
    });

    Alpine.store('evomiSettingsModal', {
        open: false,
    });

    Alpine.store('evomiWishlistModal', {
        open: false,
    });

    Alpine.store('evomiHistoryModal', {
        open: false,
    });

    Alpine.store('evomiHistoryDetailModal', {
        open: false,
        orderId: null,
    });

    Alpine.store('evomiChatModal', {
        open: false,
    });

    Alpine.store('evomiProductChat', {
        bubbles: [],
    });

    Alpine.store('evomiFaqModal', {
        open: false,
        loading: false,
        query: '',
        groups: [],

        get visibleGroups() {
            const q = String(this.query || '')
                .trim()
                .toLowerCase();
            if (!q) return this.groups;
            return this.groups
                .map((group) => ({
                    ...group,
                    items: (group.items || []).filter(
                        (item) =>
                            String(item.q || '')
                                .toLowerCase()
                                .includes(q) ||
                            String(item.a || '')
                                .toLowerCase()
                                .includes(q),
                    ),
                }))
                .filter((group) => group.items.length > 0);
        },

        async load() {
            this.loading = true;
            try {
                const locale = currentLocale();
                const res = await fetch(`/api/cms/faqs?locale=${encodeURIComponent(locale)}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await readApiJson(res);
                const rows = Array.isArray(data?.data) ? data.data : [];
                const map = {};
                for (const row of rows) {
                    const cat = row.category || 'Umum';
                    if (!map[cat]) map[cat] = [];
                    map[cat].push({
                        q: row.question || '',
                        a: row.answer || '',
                    });
                }
                this.groups = Object.entries(map).map(([category, items]) => ({
                    category,
                    items,
                }));
            } catch {
                this.groups = [];
            } finally {
                this.loading = false;
            }
        },
    });

    Alpine.store('evomiKontakModal', {
        open: false,
        loading: false,
        form: { name: '', email: '', subject: '', message: '' },
        status: { type: null, message: '' },
        ...turnstileState(),
        cms: {
            title: 'Hubungi Kami',
            subtitle: 'Punya pertanyaan atau ingin berkolaborasi? Tim Evomi siap mendengarkan Anda.',
            email_label: 'Email',
            email_value: 'hello@evomi.id',
            phone_label: 'WhatsApp',
            phone_value: '+62 812-3456-7890',
            address_label: 'Kantor Pusat',
            address_value: 'Jakarta, Indonesia',
        },

        async loadCms() {
            try {
                const locale = currentLocale();
                const res = await fetch(`/api/cms/kontak?locale=${encodeURIComponent(locale)}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await readApiJson(res);
                const g = data?.data || {};
                this.cms = {
                    title: g.header?.title || this.cms.title,
                    subtitle: g.header?.subtitle || this.cms.subtitle,
                    email_label: g.info?.email_label || this.cms.email_label,
                    email_value: g.info?.email_value || this.cms.email_value,
                    phone_label: g.info?.phone_label || this.cms.phone_label,
                    phone_value: g.info?.phone_value || this.cms.phone_value,
                    address_label: g.info?.address_label || this.cms.address_label,
                    address_value: g.info?.address_value || this.cms.address_value,
                };
            } catch {
                /* keep defaults */
            }
        },

        runTurnstile() {
            runTurnstile(this);
        },

        async submit() {
            this.loading = true;
            this.status = { type: null, message: '' };

            let captchaToken = '';
            if (this.hasTurnstile) {
                captchaToken = turnstileToken(this);
                if (!captchaToken) {
                    this.status = {
                        type: 'error',
                        message: turnstileRequiredMessage(),
                    };
                    this.loading = false;
                    return;
                }
            }

            try {
                const res = await fetch('/api/contact', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.form,
                        _hp: '',
                        captcha_token: captchaToken,
                    }),
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(
                        apiErrorMessage(data, storefrontL('Gagal mengirim pesan.', 'Failed to send message.')),
                    );
                }
                this.status = {
                    type: 'success',
                    message: storefrontL(
                        'Pesan terkirim. Tim Evomi akan membalas segera.',
                        'Message sent. Evomi support will reply soon.',
                    ),
                };
                this.form = { name: '', email: '', subject: '', message: '' };
            } catch (err) {
                this.status = {
                    type: 'error',
                    message:
                        err instanceof Error
                            ? err.message
                            : storefrontL('Gagal mengirim pesan.', 'Failed to send message.'),
                };
            } finally {
                resetTurnstile(this);
                this.loading = false;
            }
        },
    });

    async function ensureKontakModalTurnstile(store) {
        // Sama seperti halaman kontak: request dikirim tanpa token auth sehingga
        // server memperlakukannya sebagai tamu dan selalu meminta captcha.
        store.hasTurnstile = turnstileEnabled();

        if (!store.hasTurnstile) {
            resetTurnstile(store);
            return;
        }

        await new Promise((resolve) => requestAnimationFrame(resolve));

        if (store.turnstileWidgetId) {
            resetTurnstile(store);
            return;
        }

        await setupTurnstile(store, document.getElementById('evomi-kontak-modal-turnstile'), {
            theme: 'light',
        });
    }

    window.evomiOpenProduct = (id) => {
        try {
            Alpine.store('evomiProductModal').openProduct(id);
        } catch {
            /* alpine not ready */
        }
    };

    window.evomiOpenTrack = (resi = '') => {
        const q = String(resi || '').trim();
        const nav = window.__evomiNav;
        if (nav && typeof nav.openTrackModal === 'function') {
            nav.openTrackModal({ resi: q });
            return;
        }
        window.dispatchEvent(new CustomEvent('evomi-open-track', { detail: { resi: q } }));
    };

    window.evomiOpenFaq = () => {
        const nav = window.__evomiNav;
        if (nav && typeof nav.openFaqModal === 'function') {
            nav.openFaqModal();
            return;
        }
        window.dispatchEvent(new CustomEvent('evomi-open-faq'));
    };

    window.evomiOpenKontak = () => {
        const nav = window.__evomiNav;
        if (nav && typeof nav.openKontakModal === 'function') {
            nav.openKontakModal();
            return;
        }
        window.dispatchEvent(new CustomEvent('evomi-open-kontak'));
    };

    window.evomiOpenSettings = () => softNavigate('/profile');

    window.evomiOpenWishlist = () => softNavigate('/profile/wishlist');

    window.evomiOpenHistory = () => softNavigate('/profile/history');

    window.evomiOpenHistoryDetail = (orderId = '') => {
        const id = String(orderId || '').trim();
        if (!id) {
            softNavigate('/profile/history');
            return;
        }
        softNavigate(`/profile/history/${encodeURIComponent(id)}`);
    };

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
        accountDrawerOpen: false,
        drawerTab: 'cart',
        drawerCartLoading: false,
        drawerCartItems: [],
        drawerUpdatingId: null,
        drawerCartModal: { open: false, type: 'confirm', message: '', title: '', item: null },
        drawerTrackLoading: false,
        drawerTrackItems: [],
        drawerTrackSelectedId: null,
        drawerTrackCopied: false,
        drawerGuestResi: '',
        drawerGuestResiError: '',
        ordersModalOpen: false,
        ordersLoading: false,
        ordersError: '',
        ordersGroups: [],
        ordersFilter: 'all',
        ordersExpandedId: null,
        ordersToast: '',
        _ordersToastTimer: null,
        guestWarnOpen: false,
        guestWarnEmail: '',
        guestWarnBusy: false,
        guestWarnError: '',
        guestWarnStatus: '',
        guestEmailInput: '',
        guestOrdersEmail: '',
        guestOrdersNotify: true,
        guestTrackEmail: '',
        guestTrackNotify: true,
        guestTrackEmailError: '',
        guestCartEmailBusy: false,
        guestCartEmailStatus: '',
        logoutLoading: false,
        logoutModal: {
            open: false,
            type: 'confirm', // confirm | loading | success
        },
        badges: { cart: 0, wishlist: 0, history: 0, payments: 0, unread: 0 },
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

        get drawerCartCount() {
            return this.drawerCartItems.reduce((s, i) => s + (Number(i.quantity) || 0), 0);
        },

        get drawerTrackCount() {
            const live = Array.isArray(this.drawerTrackItems) ? this.drawerTrackItems.length : 0;
            if (live > 0) return live;
            return Number(this.badges?.history) || 0;
        },

        get drawerCartSubtotalLabel() {
            const total = this.drawerCartItems.reduce(
                (s, i) => s + (Number(i.unitPrice) || 0) * (Number(i.quantity) || 0),
                0,
            );
            return formatRupiah(total);
        },

        get ordersFilterTabs() {
            return [
                { key: 'all', label: storefrontL('Semua', 'All') },
                { key: 'menunggu', label: storefrontL('Menunggu', 'Pending') },
                { key: 'diproses', label: storefrontL('Diproses', 'Processing') },
                { key: 'dikirim', label: storefrontL('Dikirim', 'Shipped') },
                { key: 'selesai', label: storefrontL('Selesai', 'Completed') },
            ];
        },

        get filteredOrders() {
            if (this.ordersFilter === 'all') return this.ordersGroups;
            return this.ordersGroups.filter((g) => this.orderFilterKey(g) === this.ordersFilter);
        },

        ordersFilterCount(key) {
            if (key === 'all') return this.ordersGroups.length;
            return this.ordersGroups.filter((g) => this.orderFilterKey(g) === key).length;
        },

        orderFilterKey(group) {
            return this.orderBadge(group).filter;
        },

        orderBadge(group) {
            const iconBase = '/src/images/orders';
            if (
                group?.isAwaitingPayment ||
                group?.paymentKey === 'awaiting' ||
                group?.paymentKey === 'pending'
            ) {
                return {
                    filter: 'menunggu',
                    label: storefrontL('Menunggu', 'Pending'),
                    bg: '#5d5d5d',
                    icon: `${iconBase}/icon-process.svg`,
                };
            }
            const s = String(group?.status || '').toLowerCase();
            if (s === 'dalam_perjalanan') {
                return {
                    filter: 'dikirim',
                    label: storefrontL('Dalam Pengiriman', 'In Transit'),
                    bg: '#1172ba',
                    icon: `${iconBase}/icon-truck.svg`,
                };
            }
            if (s === 'selesai' || s === 'diterima') {
                return {
                    filter: 'selesai',
                    label: storefrontL('Selesai', 'Completed'),
                    bg: '#5ea14a',
                    icon: `${iconBase}/icon-check.svg`,
                };
            }
            if (s === 'dibatalkan') {
                return {
                    filter: 'selesai',
                    label: storefrontL('Dibatalkan', 'Cancelled'),
                    bg: '#b42318',
                    icon: `${iconBase}/icon-check.svg`,
                };
            }
            return {
                filter: 'diproses',
                label: storefrontL('Sedang Proses', 'Processing'),
                bg: '#ffbb45',
                icon: `${iconBase}/icon-process.svg`,
            };
        },

        orderMetaLine(group) {
            const first = group?.items?.[0] || {};
            const size =
                first.product?.size ||
                first.product?.volume ||
                first.size ||
                first.volume ||
                '50ml';
            const qty = Number(group?.quantity) || 1;
            return `${size} · Qty ${qty} · ${group?.dateLabel || ''}`;
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
            this.guestWarnEmail = readGuestEmail();
            this.guestEmailInput = this.guestWarnEmail;
            this.guestOrdersEmail = this.guestWarnEmail;
            this.guestTrackEmail = this.guestWarnEmail;
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
            this._onOpenTrack = (e) => {
                this.openTrackModal({ resi: e?.detail?.resi ?? '' });
            };
            window.addEventListener('evomi-open-track', this._onOpenTrack);
            this._onOpenFaq = () => this.openFaqModal();
            window.addEventListener('evomi-open-faq', this._onOpenFaq);
            this._onOpenKontak = () => this.openKontakModal();
            window.addEventListener('evomi-open-kontak', this._onOpenKontak);
            this._onOpenSettings = () => this.openSettingsModal();
            window.addEventListener('evomi-open-settings', this._onOpenSettings);
            this._onOpenWishlist = () => this.openWishlistModal();
            window.addEventListener('evomi-open-wishlist', this._onOpenWishlist);
            this._onOpenHistory = () => this.openHistoryModal();
            window.addEventListener('evomi-open-history', this._onOpenHistory);
            this._onOpenChat = () => this.openChatModal();
            window.addEventListener('evomi-open-chat', this._onOpenChat);
            this._onOpenHistoryDetail = (e) => {
                this.openHistoryDetailModal(e?.detail?.orderId || '');
            };
            window.addEventListener('evomi-open-history-detail', this._onOpenHistoryDetail);
            this._onOpenCart = () => {
                this.openAccountDrawer('cart');
            };
            window.addEventListener('evomi-open-cart', this._onOpenCart);
            this._onDrawerCartRefresh = () => {
                if (this.accountDrawerOpen) this.loadDrawerCart();
            };
            window.addEventListener('cart_updated', this._onDrawerCartRefresh);

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

            this.$watch('drawerTab', (tab) => {
                if (tab === 'track' && this.accountDrawerOpen) {
                    this.loadDrawerTrackings();
                }
            });

            // Deep link /pengiriman/{resi} → track modal
            this.$nextTick(() => {
                const path = window.location.pathname.replace(/\/$/, '') || '/';
                const trackMatch = path.match(/^\/pengiriman\/([^/]+)$/i);
                if (trackMatch) {
                    const resi = decodeURIComponent(trackMatch[1] || '').trim();
                    if (resi) {
                        this.openTrackModal({ resi });
                        try {
                            history.replaceState({ soft: true }, document.title, '/pengiriman');
                        } catch {
                            /* ignore */
                        }
                    }
                    return;
                }
                if (path === '/faq') {
                    this.openFaqModal();
                    return;
                }
                if (path === '/kontak') {
                    this.openKontakModal();
                    return;
                }

            });

            // Safety: clear stray scroll lock if no overlay is open
            this.$nextTick(() => {
                if (!this.bodyScrollLocked()) {
                    document.documentElement.classList.remove('overflow-hidden');
                }
            });
        },

        readAuth() {
            const token = getAuthToken();
            const user = getAuthUser();

            if (token && user) {
                const wasGuest = !this.isLoggedIn;
                this.isLoggedIn = true;
                this.userEmail = user.email || null;
                this.userName = user.name || user.nama_lengkap || null;
                this.isAdmin = Boolean(user.is_admin);
                this.userAvatar = avatarUrlFromUser(user);
                if (wasGuest && guestCartCount() > 0) {
                    mergeGuestCartIntoAccount().finally(() => this.refreshBadges());
                } else {
                    this.refreshBadges();
                }
            } else {
                this.isLoggedIn = false;
                this.isAdmin = false;
                this.userEmail = null;
                this.userName = null;
                this.userAvatar = null;
                this.accountMenuOpen = false;
                this.badges = {
                    cart: guestCartCount(),
                    wishlist: 0,
                    history: 0,
                    payments: 0,
                    unread: 0,
                };
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
                this.badges = {
                    cart: guestCartCount(),
                    wishlist: 0,
                    history: 0,
                    payments: 0,
                    unread: 0,
                };
                return;
            }
            this.badges = await fetchBadgeCounts();
        },

        toggleAccountMenu() {
            this.accountMenuOpen = !this.accountMenuOpen;
            if (this.accountMenuOpen) this.closeAccountDrawer();
        },

        closeAccountMenu() {
            this.accountMenuOpen = false;
        },

        mapDrawerCartItem(row, { isGuest = false } = {}) {
            if (isGuest) {
                const unit = Number(row.price) || 0;
                const qty = Number(row.quantity) || 1;
                return {
                    id: row.id || `guest-${row.product_id}`,
                    product_id: row.product_id,
                    title: row.title || `Produk #${row.product_id}`,
                    imageUrl: row.image || '',
                    accent: row.accent || '#1172BA',
                    meta: '30ml · EDP',
                    stock: Number(row.stock ?? 99) || 99,
                    quantity: qty,
                    unitPrice: unit,
                    priceLabel: formatRupiah(unit),
                    lineTotalLabel: formatRupiah(unit * qty),
                    isGuest: true,
                };
            }
            const unit = productPrice(row.product);
            const qty = Number(row.quantity) || 1;
            const size = row.product?.size || row.product?.volume || '30ml';
            const type = row.product?.type || row.product?.concentration || 'EDP';
            return {
                id: row.id,
                product_id: row.product_id || row.product?.id,
                title: productTitle(row.product),
                imageUrl: productImage(row.product, 'cart'),
                accent: productAccent(row.product),
                meta: `${size} · ${type}`,
                stock: Number(row.product?.stock ?? 99) || 99,
                quantity: qty,
                unitPrice: unit,
                priceLabel: formatRupiah(unit),
                lineTotalLabel: formatRupiah(unit * qty),
                isGuest: false,
            };
        },

        get drawerTrackSelected() {
            const id = this.drawerTrackSelectedId;
            if (!id) return this.drawerTrackItems[0] || null;
            return this.drawerTrackItems.find((i) => i.id === id) || this.drawerTrackItems[0] || null;
        },

        get drawerTrackSteps() {
            return [
                { key: 'diterima', label: storefrontL('Diterima', 'Received') },
                { key: 'dikemas', label: storefrontL('Dikemas', 'Packed') },
                { key: 'dikirim', label: storefrontL('Dikirim', 'Shipped') },
                { key: 'transit', label: storefrontL('Transit', 'Transit') },
                { key: 'terkirim', label: storefrontL('Terkirim', 'Delivered') },
            ];
        },

        drawerTrackToneClass(tone) {
            if (tone === 'success') return 'is-success';
            if (tone === 'info') return 'is-info';
            if (tone === 'warning') return 'is-warning';
            if (tone === 'danger') return 'is-danger';
            return 'is-muted';
        },

        drawerTrackStepDone(index) {
            const selected = this.drawerTrackSelected;
            if (!selected) return false;
            return Number(selected.progress_step) >= index;
        },

        drawerTrackStepCurrent(index) {
            const selected = this.drawerTrackSelected;
            if (!selected) return false;
            return Number(selected.progress_step) === index;
        },

        drawerTrackProgressPct() {
            const selected = this.drawerTrackSelected;
            if (!selected) return 0;
            const step = Math.max(0, Math.min(4, Number(selected.progress_step) || 0));
            return (step / 4) * 100;
        },

        formatDrawerTrackTime(raw) {
            if (!raw) return { time: '—', date: '' };
            const d = new Date(raw);
            if (Number.isNaN(d.getTime())) {
                const text = String(raw);
                return { time: text.slice(0, 5), date: text };
            }
            return {
                time: d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                date: d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }),
            };
        },

        selectDrawerTrack(id) {
            this.drawerTrackSelectedId = id;
            this.drawerTrackCopied = false;
        },

        scrollTrackChipsWheel(event) {
            const el = event.currentTarget;
            if (!el) return;
            const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY)
                ? event.deltaX
                : event.deltaY;
            if (!delta) return;
            const max = el.scrollWidth - el.clientWidth;
            if (max <= 0) return;
            const next = Math.min(max, Math.max(0, el.scrollLeft + delta));
            if (next === el.scrollLeft) return;
            event.preventDefault();
            el.scrollLeft = next;
        },

        async copyDrawerResi() {
            const selected = this.drawerTrackSelected;
            const resi = selected?.tracking_number || selected?.code || selected?.order_number;
            if (!resi) return;
            try {
                await navigator.clipboard.writeText(String(resi));
                this.drawerTrackCopied = true;
                window.setTimeout(() => {
                    this.drawerTrackCopied = false;
                }, 1600);
            } catch {
                /* ignore */
            }
        },

        async submitDrawerGuestResi() {
            const resi = String(this.drawerGuestResi || '').trim();
            if (!resi) {
                this.drawerGuestResiError = storefrontL(
                    'Masukkan nomor resi / nomor pesanan terlebih dahulu.',
                    'Please enter a tracking or order number first.',
                );
                return;
            }
            this.drawerGuestResiError = '';
            this.drawerTrackLoading = true;
            try {
                const res = await fetch(`/api/trackings/${encodeURIComponent(resi)}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(
                        apiErrorMessage(
                            data,
                            storefrontL(
                                'Nomor resi / nomor pesanan tidak ditemukan.',
                                'Tracking or order number not found.',
                            ),
                        ),
                    );
                }
                const row = data?.data || data;
                if (!row || (!row.id && !row.orderId && !row.order_id)) {
                    throw new Error(
                        storefrontL(
                            'Nomor resi / nomor pesanan tidak ditemukan.',
                            'Tracking or order number not found.',
                        ),
                    );
                }
                const mapped = {
                    ...row,
                    id: row.id || row.orderId || row.order_id,
                    invoice: row.invoice || row.order_number || row.code || resi,
                    code: row.order_number || row.code || row.invoice || resi,
                    order_number: row.order_number || row.code || row.invoice || resi,
                    title: row.title || storefrontL('Pesanan Evomi', 'Evomi Order'),
                    courier: row.courier && row.courier !== 'Belum ditentukan' ? row.courier : row.courier || null,
                    tracking_number: row.tracking_number || row.trackingNumber || null,
                    estimated_delivery: row.estimated_delivery || row.estimatedDelivery || null,
                    status_label: row.status_label || row.currentStatus || storefrontL('Diproses', 'Processing'),
                    status_tone: row.status_tone || 'muted',
                    progress_step: Number.isFinite(Number(row.progress_step)) ? Number(row.progress_step) : 0,
                    destination: row.destination || null,
                    recipient: row.recipient || null,
                    imageUrl: mediaUrl(row.image) || productImageFallback(row),
                    timeline: (row.timeline || []).map((entry) => {
                        const stamp = this.formatDrawerTrackTime(entry.time || entry.date);
                        return {
                            ...entry,
                            timeLabel: stamp.time,
                            dateLabel: stamp.date,
                            place: entry.description || '',
                        };
                    }),
                };
                this.drawerTrackItems = [mapped];
                this.drawerTrackSelectedId = mapped.id;
            } catch (err) {
                this.drawerTrackItems = [];
                this.drawerTrackSelectedId = null;
                this.drawerGuestResiError =
                    err instanceof Error
                        ? err.message
                        : storefrontL(
                              'Gagal memuat pelacakan.',
                              'Failed to load tracking.',
                          );
            } finally {
                this.drawerTrackLoading = false;
            }
        },

        async loadDrawerTrackings() {
            if (!this.isLoggedIn || !getAuthToken()) {
                return;
            }
            this.drawerTrackLoading = true;
            try {
                const res = await fetch('/api/my-trackings?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    this.readAuth();
                    this.drawerTrackItems = [];
                    return;
                }
                const data = await readApiJson(res);
                const list = Array.isArray(data) ? data : data.data || [];
                this.drawerTrackItems = list.map((row) => {
                    const invoiceNumber = drawerTrackPublicNumber(row);
                    return {
                        ...row,
                        invoice: invoiceNumber,
                        code: invoiceNumber,
                        order_number: invoiceNumber,
                        imageUrl: mediaUrl(row.image) || productImageFallback(row),
                        timeline: (row.timeline || []).map((entry) => {
                            const stamp = this.formatDrawerTrackTime(entry.time);
                            return {
                                ...entry,
                                timeLabel: stamp.time,
                                dateLabel: stamp.date,
                                place: entry.description || '',
                            };
                        }),
                    };
                });
                if (
                    !this.drawerTrackSelectedId ||
                    !this.drawerTrackItems.some((i) => i.id === this.drawerTrackSelectedId)
                ) {
                    this.drawerTrackSelectedId = this.drawerTrackItems[0]?.id || null;
                }
            } catch {
                this.drawerTrackItems = [];
            } finally {
                this.drawerTrackLoading = false;
            }
        },

        async openAccountDrawer(tab = 'cart') {
            const nextTab = tab === 'track' ? 'track' : 'cart';

            this.open = false;
            this.accountMenuOpen = false;
            this.closeTrackModal();
            this.closeHelpModals();
            this.closeAccountModals();
            this.guestEmailInput = readGuestEmail() || this.guestEmailInput;
            this.guestTrackEmail = readGuestEmail() || this.guestTrackEmail;
            this.drawerTab = nextTab;
            this.accountDrawerOpen = true;
            this.syncBodyScrollLock();

            const tasks = [];
            if (this.drawerTab === 'cart') {
                tasks.push(this.loadDrawerCart());
            }
            if (this.isLoggedIn) {
                tasks.push(this.loadDrawerTrackings());
            }
            await Promise.all(tasks);
        },

        switchDrawerTab(tab) {
            const nextTab = tab === 'track' ? 'track' : 'cart';
            if (this.drawerTab === nextTab) return;
            this.drawerTab = nextTab;
        },

        closeAccountDrawer() {
            this.drawerCartModal.open = false;
            this.accountDrawerOpen = false;
            this.syncBodyScrollLock();
        },

        handleDrawerEscape(event) {
            if (this.drawerCartModal?.open) {
                event?.preventDefault?.();
                event?.stopImmediatePropagation?.();
                this.closeDrawerCartModal();
                return;
            }
            if (this.accountDrawerOpen) this.closeAccountDrawer();
        },

        bodyScrollLocked() {
            return (
                this.accountDrawerOpen ||
                this.ordersModalOpen ||
                Alpine.store('evomiTrackModal').open ||
                Alpine.store('evomiFaqModal').open ||
                Alpine.store('evomiKontakModal').open ||
                Alpine.store('evomiSettingsModal').open ||
                Alpine.store('evomiWishlistModal').open ||
                Alpine.store('evomiHistoryModal').open ||
                Alpine.store('evomiHistoryDetailModal').open ||
                Alpine.store('evomiChatModal').open ||
                this.guestWarnOpen
            );
        },

        syncBodyScrollLock() {
            if (this.bodyScrollLocked()) {
                document.documentElement.classList.add('overflow-hidden');
            } else {
                document.documentElement.classList.remove('overflow-hidden');
            }
        },

        closeHelpModals() {
            Alpine.store('evomiFaqModal').open = false;
            Alpine.store('evomiKontakModal').open = false;
        },

        closeAccountModals() {
            Alpine.store('evomiSettingsModal').open = false;
            Alpine.store('evomiWishlistModal').open = false;
            Alpine.store('evomiHistoryModal').open = false;
            Alpine.store('evomiHistoryDetailModal').open = false;
            Alpine.store('evomiHistoryDetailModal').orderId = null;
            Alpine.store('evomiChatModal').open = false;
        },

        closeSettingsModal() {
            Alpine.store('evomiSettingsModal').open = false;
            this.syncBodyScrollLock();
        },

        closeWishlistModal() {
            Alpine.store('evomiWishlistModal').open = false;
            this.syncBodyScrollLock();
        },

        closeHistoryModal() {
            Alpine.store('evomiHistoryModal').open = false;
            this.syncBodyScrollLock();
        },

        closeChatModal() {
            Alpine.store('evomiChatModal').open = false;
            this.syncBodyScrollLock();
        },

        closeHistoryDetailModal({ backToList = false } = {}) {
            Alpine.store('evomiHistoryDetailModal').open = false;
            Alpine.store('evomiHistoryDetailModal').orderId = null;
            this.syncBodyScrollLock();
            if (backToList) {
                softNavigate('/profile/history');
            }
        },

        prepareAccountModal() {
            window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
            this.open = false;
            this.accountMenuOpen = false;
            this.closeAccountDrawer();
            this.closeOrdersModal();
            this.closeTrackModal();
            this.closeHelpModals();
            this.closeAccountModals();
        },

        async openSettingsModal() {
            if (!this.isLoggedIn || !getAuthToken()) {
                softNavigate('/login');
                return;
            }

            this.prepareAccountModal();
            Alpine.store('evomiSettingsModal').open = true;
            this.syncBodyScrollLock();
            window.dispatchEvent(new CustomEvent('evomi-settings-reload'));
        },

        async openWishlistModal() {
            if (!this.isLoggedIn || !getAuthToken()) {
                softNavigate('/login');
                return;
            }

            this.prepareAccountModal();
            Alpine.store('evomiWishlistModal').open = true;
            this.syncBodyScrollLock();
            window.dispatchEvent(new CustomEvent('evomi-wishlist-reload'));
        },

        async openHistoryModal() {
            if (!this.isLoggedIn || !getAuthToken()) {
                softNavigate('/login');
                return;
            }

            this.prepareAccountModal();
            Alpine.store('evomiHistoryModal').open = true;
            this.syncBodyScrollLock();
            window.dispatchEvent(new CustomEvent('evomi-history-reload'));
        },

        async openChatModal() {
            if (!this.isLoggedIn || !getAuthToken()) {
                softNavigate('/login');
                return;
            }

            this.prepareAccountModal();
            Alpine.store('evomiChatModal').open = true;
            this.syncBodyScrollLock();
            window.dispatchEvent(new CustomEvent('evomi-chat-reload'));
        },

        async openHistoryDetailModal(orderId = '') {
            if (!this.isLoggedIn || !getAuthToken()) {
                softNavigate('/login');
                return;
            }

            const id = String(orderId || '').trim();
            if (!id) {
                softNavigate('/profile/history');
                return;
            }

            // Prefer full profile page over legacy square modal
            this.open = false;
            this.accountMenuOpen = false;
            this.closeAccountDrawer();
            this.closeOrdersModal();
            this.closeTrackModal();
            this.closeHelpModals();
            Alpine.store('evomiSettingsModal').open = false;
            Alpine.store('evomiWishlistModal').open = false;
            Alpine.store('evomiHistoryModal').open = false;
            Alpine.store('evomiHistoryDetailModal').open = false;
            Alpine.store('evomiHistoryDetailModal').orderId = null;
            this.syncBodyScrollLock();
            softNavigate(`/profile/history/${encodeURIComponent(id)}`);
        },

        async openTrackModal({ resi = '' } = {}) {
            window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });

            this.open = false;
            this.accountMenuOpen = false;
            this.closeAccountDrawer();
            this.closeOrdersModal();
            this.closeHelpModals();
            this.closeAccountModals();

            const query = String(resi || '').trim();
            this.drawerGuestResi = query;
            this.drawerGuestResiError = '';
            if (!query) {
                this.drawerTrackItems = [];
                this.drawerTrackSelectedId = null;
            }

            Alpine.store('evomiTrackModal').open = true;
            this.syncBodyScrollLock();

            if (this.isLoggedIn && !query) {
                await this.loadDrawerTrackings();
            }

            if (query) {
                await this.submitDrawerGuestResi();
            }

            this.$nextTick(() => {
                document.querySelector('.evomi-track-modal__input')?.focus?.();
            });
        },

        closeTrackModal() {
            Alpine.store('evomiTrackModal').open = false;
            this.syncBodyScrollLock();
        },

        async openFaqModal() {
            window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });

            this.open = false;
            this.accountMenuOpen = false;
            this.closeAccountDrawer();
            this.closeOrdersModal();
            this.closeTrackModal();
            this.closeAccountModals();
            Alpine.store('evomiKontakModal').open = false;

            const store = Alpine.store('evomiFaqModal');
            store.query = '';
            store.open = true;
            this.syncBodyScrollLock();
            await store.load();
        },

        closeFaqModal() {
            Alpine.store('evomiFaqModal').open = false;
            this.syncBodyScrollLock();
        },

        async openKontakModal() {
            window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });

            this.open = false;
            this.accountMenuOpen = false;
            this.closeAccountDrawer();
            this.closeOrdersModal();
            this.closeTrackModal();
            this.closeAccountModals();
            Alpine.store('evomiFaqModal').open = false;

            const store = Alpine.store('evomiKontakModal');
            store.status = { type: null, message: '' };
            store.open = true;
            this.syncBodyScrollLock();
            await store.loadCms();
            await ensureKontakModalTurnstile(store);
        },

        closeKontakModal() {
            Alpine.store('evomiKontakModal').open = false;
            this.syncBodyScrollLock();
        },

        async openOrdersModal() {
            this.closeAccountDrawer();
            this.closeTrackModal();
            this.closeHelpModals();
            this.closeAccountModals();
            this.ordersModalOpen = true;
            this.ordersFilter = 'all';
            this.ordersExpandedId = null;
            this.guestOrdersEmail = readGuestEmail() || this.guestOrdersEmail;
            this.syncBodyScrollLock();
            if (this.isLoggedIn) {
                await this.loadOrders();
            } else if (this.guestOrdersEmail) {
                await this.loadGuestOrders({ notify: false });
            } else {
                this.ordersGroups = [];
                this.ordersError = '';
                this.ordersLoading = false;
            }
        },

        closeOrdersModal() {
            this.ordersModalOpen = false;
            this.ordersExpandedId = null;
            this.syncBodyScrollLock();
        },

        showGuestCartWarning() {
            this.guestWarnEmail = readGuestEmail();
            this.guestWarnError = '';
            this.guestWarnStatus = '';
            this.guestWarnBusy = false;
            this.guestWarnOpen = true;
            this.syncBodyScrollLock();
        },

        closeGuestCartWarning() {
            this.guestWarnOpen = false;
            this.guestWarnBusy = false;
            this.syncBodyScrollLock();
        },

        async continueAsGuestFromWarning({ sendEmail = false } = {}) {
            if (this.guestWarnBusy) return;
            this.guestWarnError = '';
            this.guestWarnStatus = '';
            const email = String(this.guestWarnEmail || '').trim().toLowerCase();
            if (sendEmail) {
                if (!email.includes('@')) {
                    this.guestWarnError = storefrontL(
                        'Masukkan email yang valid untuk mengirim salinan keranjang.',
                        'Enter a valid email to send your cart copy.',
                    );
                    return;
                }
                this.guestWarnBusy = true;
                try {
                    await this.emailGuestCartSnapshot(email);
                    writeGuestEmail(email);
                    this.guestOrdersEmail = email;
                    this.guestTrackEmail = email;
                    this.guestWarnStatus = storefrontL(
                        'Salinan keranjang sudah dikirim ke email Anda.',
                        'A cart copy has been sent to your email.',
                    );
                } catch (err) {
                    this.guestWarnError =
                        err instanceof Error
                            ? err.message
                            : storefrontL('Gagal mengirim email keranjang.', 'Failed to send cart email.');
                    this.guestWarnBusy = false;
                    return;
                }
                this.guestWarnBusy = false;
            } else if (email.includes('@')) {
                writeGuestEmail(email);
                this.guestOrdersEmail = email;
                this.guestTrackEmail = email;
            }
            this.closeGuestCartWarning();
            await this.openAccountDrawer('cart');
        },

        goGuestAuth(path) {
            this.closeGuestCartWarning();
            this.closeAccountDrawer();
            this.closeOrdersModal();
            softNavigate(path);
        },

        async emailGuestCartSnapshot(emailOverride = null) {
            const email = String(emailOverride || this.guestEmailInput || readGuestEmail() || '')
                .trim()
                .toLowerCase();
            if (!email.includes('@')) {
                throw new Error(
                    storefrontL('Masukkan email yang valid.', 'Enter a valid email.'),
                );
            }
            const items = readGuestCart();
            if (!items.length) {
                throw new Error(
                    storefrontL('Keranjang masih kosong.', 'Cart is still empty.'),
                );
            }
            const res = await fetch('/api/guest/cart-email', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    email,
                    items: items.map((row) => ({
                        product_id: Number(row.product_id),
                        title: row.title || `Produk #${row.product_id}`,
                        quantity: Number(row.quantity) || 1,
                        price: Number(row.price) || 0,
                        image: row.image || '',
                    })),
                }),
            });
            const data = await readApiJson(res);
            if (!res.ok) {
                throw new Error(
                    apiErrorMessage(data, storefrontL('Gagal mengirim email keranjang.', 'Failed to send cart email.')),
                );
            }
            writeGuestEmail(email);
            return data;
        },

        async sendGuestCartEmailFromDrawer() {
            if (this.guestCartEmailBusy) return;
            this.guestCartEmailBusy = true;
            this.guestCartEmailStatus = '';
            try {
                await this.emailGuestCartSnapshot(this.guestEmailInput);
                this.guestCartEmailStatus = storefrontL(
                    'Salinan keranjang terkirim. Cek inbox email Anda.',
                    'Cart copy sent. Check your email inbox.',
                );
            } catch (err) {
                this.guestCartEmailStatus =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal mengirim email keranjang.', 'Failed to send cart email.');
            } finally {
                this.guestCartEmailBusy = false;
            }
        },

        async loadOrders() {
            this.ordersLoading = true;
            this.ordersError = '';
            try {
                if (!this.isLoggedIn) {
                    await this.loadGuestOrders({ notify: false });
                    return;
                }
                const res = await fetch('/api/shopping-history?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    this.readAuth();
                    await this.loadGuestOrders({ notify: false });
                    return;
                }
                const data = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(
                        apiErrorMessage(data, storefrontL('Gagal memuat pesanan.', 'Failed to load orders.')),
                    );
                }
                const list = Array.isArray(data) ? data : data.data || [];
                this.ordersGroups = groupOrdersByCreatedAt(list);
                cacheHistoryGroups(this.ordersGroups);
                emitEvomiEvent('history_updated');
            } catch (err) {
                this.ordersError =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal memuat pesanan.', 'Failed to load orders.');
                this.ordersGroups = [];
            } finally {
                this.ordersLoading = false;
            }
        },

        async loadGuestOrders({ notify = false } = {}) {
            const email = String(this.guestOrdersEmail || readGuestEmail() || '')
                .trim()
                .toLowerCase();
            if (!email.includes('@')) {
                this.ordersGroups = [];
                this.ordersError = storefrontL(
                    'Masukkan email yang dipakai saat checkout guest.',
                    'Enter the email used for guest checkout.',
                );
                this.ordersLoading = false;
                return;
            }
            this.ordersLoading = true;
            this.ordersError = '';
            try {
                const res = await fetch('/api/guest/orders', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        email,
                        notify: Boolean(notify),
                        locale: 'id',
                    }),
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(
                        apiErrorMessage(data, storefrontL('Gagal memuat pesanan guest.', 'Failed to load guest orders.')),
                    );
                }
                writeGuestEmail(email);
                const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
                this.ordersGroups = groupOrdersByCreatedAt(list);
                cacheHistoryGroups(this.ordersGroups);
                if (notify) {
                    this.showOrdersToast(
                        storefrontL('Ringkasan pesanan telah dikirim ke email.', 'Order summary sent to your email.'),
                    );
                }
            } catch (err) {
                this.ordersGroups = [];
                this.ordersError =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal memuat pesanan guest.', 'Failed to load guest orders.');
            } finally {
                this.ordersLoading = false;
            }
        },

        async submitDrawerGuestEmailTrack() {
            const email = String(this.guestTrackEmail || '').trim().toLowerCase();
            if (!email.includes('@')) {
                this.guestTrackEmailError = storefrontL(
                    'Masukkan email yang valid.',
                    'Enter a valid email.',
                );
                return;
            }
            this.guestTrackEmailError = '';
            writeGuestEmail(email);
            this.drawerTrackLoading = true;
            try {
                const res = await fetch('/api/guest/trackings', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        email,
                        notify: Boolean(this.guestTrackNotify),
                        locale: 'id',
                    }),
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(
                        apiErrorMessage(data, storefrontL('Gagal memuat pelacakan.', 'Failed to load tracking.')),
                    );
                }
                const list = Array.isArray(data?.data) ? data.data : [];
                this.drawerTrackItems = list.map((row) => {
                    const invoiceNumber = drawerTrackPublicNumber(row);
                    return {
                        ...row,
                        invoice: invoiceNumber,
                        code: invoiceNumber,
                        order_number: invoiceNumber,
                        imageUrl: mediaUrl(row.image) || productImageFallback(row),
                        timeline: (row.timeline || []).map((entry) => {
                            const stamp = this.formatDrawerTrackTime(entry.time);
                            return {
                                ...entry,
                                timeLabel: stamp.time,
                                dateLabel: stamp.date,
                                place: entry.description || '',
                            };
                        }),
                    };
                });
                this.drawerTrackSelectedId = this.drawerTrackItems[0]?.id || null;
                if (!this.drawerTrackItems.length) {
                    this.guestTrackEmailError = storefrontL(
                        'Belum ada pesanan untuk email ini.',
                        'No orders found for this email.',
                    );
                }
            } catch (err) {
                this.drawerTrackItems = [];
                this.drawerTrackSelectedId = null;
                this.guestTrackEmailError =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal memuat pelacakan.', 'Failed to load tracking.');
            } finally {
                this.drawerTrackLoading = false;
            }
        },

        toggleOrderDetail(group) {
            const id = group?.groupId;
            this.ordersExpandedId = this.ordersExpandedId === id ? null : id;
        },

        goOrderDetailPage(group) {
            if (!group?.groupId) return;
            this.closeOrdersModal();
            if (!this.isLoggedIn) {
                this.openTrackModal({ resi: group.groupId });
                return;
            }
            if (group.isAwaitingCod) {
                const href = group.paymentUrl || `/pembayaran/${encodeURIComponent(orderInvoiceRoot(String(group.groupId)))}`;
                softNavigate(href);
                return;
            }
            softNavigate(`/profile/history/${encodeURIComponent(group.groupId)}`);
        },

        showOrdersToast(message, ms = 2200) {
            this.ordersToast = message;
            if (this._ordersToastTimer) window.clearTimeout(this._ordersToastTimer);
            this._ordersToastTimer = window.setTimeout(() => {
                this.ordersToast = '';
            }, ms);
        },

        async requestOrderConfirm(group) {
            if (!group?.canConfirm || !Array.isArray(group.items)) return;
            const ok = window.confirm(
                storefrontL(
                    'Apakah Anda yakin telah menerima paket pesanan ini dengan baik? Jika ya, pesanan akan diselesaikan.',
                    'Have you received this package in good condition? If yes, the order will be completed.',
                ),
            );
            if (!ok) return;
            try {
                for (const item of group.items) {
                    const res = await fetch(`/api/orders/${item.id}/confirm`, {
                        method: 'PATCH',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        const data = await readApiJson(res);
                        throw new Error(
                            apiErrorMessage(data, storefrontL('Gagal konfirmasi pesanan.', 'Failed to confirm order.')),
                        );
                    }
                }
                emitEvomiEvent('history_updated');
                await this.loadOrders();
                this.showOrdersToast(
                    storefrontL('Pesanan telah berhasil diselesaikan.', 'Order completed successfully.'),
                );
            } catch (err) {
                this.showOrdersToast(
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal konfirmasi pesanan.', 'Failed to confirm order.'),
                );
            }
        },

        async loadDrawerCart() {
            this.drawerCartLoading = true;
            try {
                if (!getAuthToken()) {
                    this.drawerCartItems = readGuestCart().map((row) => this.mapDrawerCartItem(row, { isGuest: true }));
                    return;
                }
                const res = await fetch('/api/carts?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    this.readAuth();
                    this.drawerCartItems = readGuestCart().map((row) => this.mapDrawerCartItem(row, { isGuest: true }));
                    return;
                }
                const data = await readApiJson(res);
                const list = Array.isArray(data) ? data : data.data || [];
                this.drawerCartItems = list.map((row) => this.mapDrawerCartItem(row));
            } catch {
                this.drawerCartItems = [];
            } finally {
                this.drawerCartLoading = false;
            }
        },

        async drawerChangeQty(item, delta) {
            const next = (Number(item.quantity) || 1) + delta;
            if (next < 1) {
                this.requestDrawerRemove(item);
                return;
            }
            if (item.stock && next > item.stock) return;
            if (this.drawerUpdatingId === item.id) return;

            const prevQty = item.quantity;
            this.drawerUpdatingId = item.id;
            item.quantity = next;
            item.lineTotalLabel = formatRupiah(item.unitPrice * next);
            try {
                if (item.isGuest || !getAuthToken()) {
                    const items = readGuestCart().map((row) => {
                        if (Number(row.product_id) === Number(item.product_id)) {
                            return { ...row, quantity: next };
                        }
                        return row;
                    });
                    writeGuestCart(items);
                    this.badges.cart = guestCartCount();
                } else {
                    const res = await fetch(`/api/carts/${item.id}`, {
                        method: 'PUT',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                        body: JSON.stringify({ quantity: next }),
                    });
                    const data = await readApiJson(res);
                    if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Gagal mengubah jumlah.', 'Failed to update quantity.')));
                    emitEvomiEvent('cart_updated');
                }
            } catch {
                item.quantity = prevQty;
                item.lineTotalLabel = formatRupiah(item.unitPrice * prevQty);
            } finally {
                this.drawerUpdatingId = null;
            }
        },

        requestDrawerRemove(item) {
            if (!item) return;
            this.drawerCartModal = {
                open: true,
                type: 'confirm',
                message: storefrontL('Hapus produk ini dari keranjang?', 'Remove this product from cart?'),
                title: String(item.title || '').trim(),
                item,
            };
        },

        closeDrawerCartModal() {
            this.drawerCartModal.open = false;
        },

        async confirmDrawerRemove() {
            const item = this.drawerCartModal.item;
            this.closeDrawerCartModal();
            if (item) await this.drawerRemoveItem(item);
        },

        async drawerRemoveItem(item) {
            if (this.drawerUpdatingId === item.id) return;
            this.drawerUpdatingId = item.id;
            try {
                if (item.isGuest || !getAuthToken()) {
                    writeGuestCart(readGuestCart().filter((row) => Number(row.product_id) !== Number(item.product_id)));
                    this.drawerCartItems = this.drawerCartItems.filter((i) => i.id !== item.id);
                    this.badges.cart = guestCartCount();
                } else {
                    const res = await fetch(`/api/carts/${item.id}`, {
                        method: 'DELETE',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        const data = await readApiJson(res);
                        throw new Error(apiErrorMessage(data, storefrontL('Gagal menghapus item.', 'Failed to remove item.')));
                    }
                    this.drawerCartItems = this.drawerCartItems.filter((i) => i.id !== item.id);
                    emitEvomiEvent('cart_updated');
                }
            } catch {
                /* keep item on failure */
            } finally {
                this.drawerUpdatingId = null;
            }
        },

        goDrawerLink(href, requireAuth = false) {
            if (requireAuth && !this.isLoggedIn) {
                this.closeAccountDrawer();
                softNavigate('/login');
                return;
            }
            this.closeAccountDrawer();
            softNavigate(href);
        },

        goDrawerCheckout() {
            if (!this.drawerCartItems.length) return;
            this.closeAccountDrawer();
            try {
                sessionStorage.setItem('evomi_checkout_qs', 'type=cart');
            } catch {
                /* ignore */
            }
            softNavigate('/checkout?type=cart');
        },

        askLogout() {
            this.open = false;
            this.accountMenuOpen = false;
            this.closeAccountDrawer();
            this.closeOrdersModal();
            this.logoutModal = { open: true, type: 'confirm' };
        },

        cancelLogout() {
            if (this.logoutModal.type === 'loading') return;
            this.logoutModal = { open: false, type: 'confirm' };
        },

        async confirmLogout() {
            if (this.logoutLoading) return;
            this.logoutLoading = true;
            this.logoutModal = { open: true, type: 'loading' };
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
                this.logoutModal = { open: true, type: 'success' };
                window.setTimeout(() => {
                    this.logoutModal = { open: false, type: 'confirm' };
                    softNavigate('/');
                }, 1200);
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
            const pillIndex = Number(index);
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
        description: payload.description || '',
        accent: payload.accent || DEFAULT_THEME_BLUE,
        price: Number(payload.price) || 0,
        stock: Math.max(0, Number(payload.stock) || 0),
        gallery: Array.isArray(payload.gallery) ? payload.gallery : [],
        characterUrl: payload.characterUrl || '',
        shareImage: payload.shareImage || '',
        kurirs: Array.isArray(payload.kurirs) ? payload.kurirs : [],
        promo: Math.max(0, Number(payload.promo) || 0),
        checkoutPromo: payload.checkoutPromo || null,
        loginUrl: payload.loginUrl || '/login',
        applyTheme: payload.applyTheme !== false,
        freeShipping: Boolean(payload.freeShipping),

        currentIndex: 0,
        // Index of the leftmost thumbnail in the visible window of three.
        thumbStart: 0,
        thumbShift: 0,
        _thumbTouchX: null,
        quantity: 1,
        selectedKurir: null,
        showKurirList: false,
        showShareModal: false,
        isChatOpen: false,
        isCopied: false,
        shareHint: '',
        isWishlisted: false,
        statusMessage: '',
        statusTone: 'success',
        wishlistMessage: '',
        draft: '',
        chatBubbles: [],
        chatError: '',
        chatSending: false,
        chatLoading: false,
        _chatPoll: null,
        ...chatCaptcha(`evomi-chat-turnstile-produk-${payload.id ?? 'x'}`, {
            persistSession: false,
            freshOnOpen: true,
        }),
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

        /* ---------- Thumbnail slider ----------
         * Six images, three on screen. The window slides one step at a time
         * and follows the hero image, so the active thumbnail is always the
         * one you can see - including while the hero rotates on its own. */

        get thumbsPerView() {
            return Math.min(this.gallery.length, 3);
        },

        get maxThumbStart() {
            return Math.max(0, this.gallery.length - this.thumbsPerView);
        },

        get canSlideThumbs() {
            return this.gallery.length > this.thumbsPerView;
        },

        /**
         * The shift is measured from the live layout and applied in pixels.
         * A percentage-based calc would work too, but pixels keep the step
         * exactly one thumbnail wide whatever the container rounds to, and
         * they are trivial to assert against getBoundingClientRect in a test.
         */
        get thumbTrackStyle() {
            return {
                '--thumb-per-view': String(this.thumbsPerView),
                transform: `translateX(-${this.thumbShift}px)`,
            };
        },

        /** Width of one thumbnail plus its gap, measured from the live layout. */
        measureThumbStep() {
            const viewport = this.$refs.thumbViewport;
            if (!viewport) return 0;

            const width = viewport.getBoundingClientRect().width;
            if (!width) return 0;

            const track = this.$refs.thumbTrack;
            const gap = track ? parseFloat(getComputedStyle(track).columnGap) || 0 : 0;

            return (width + gap) / this.thumbsPerView;
        },

        syncThumbShift() {
            this.thumbShift = Math.round(this.thumbStart * this.measureThumbStep());
        },

        slideThumbs(direction) {
            // Sliding by hand means the visitor took over. Without this the
            // 4s hero rotation would drag the window back a moment later and
            // undo the gesture.
            this.stopGalleryRotation();

            const next = this.thumbStart + direction;
            this.thumbStart = Math.min(Math.max(next, 0), this.maxThumbStart);
            this.syncThumbShift();
        },

        /** Keep the active thumbnail inside the window, moving as little as possible. */
        ensureThumbVisible() {
            const perView = this.thumbsPerView;
            let start = this.thumbStart;

            if (this.currentIndex < start) {
                start = this.currentIndex;
            } else if (this.currentIndex > start + perView - 1) {
                start = this.currentIndex - perView + 1;
            }

            this.thumbStart = Math.min(Math.max(start, 0), this.maxThumbStart);
            this.syncThumbShift();
        },

        /** Picking a thumbnail is a deliberate choice; stop the auto-rotation. */
        selectImage(index) {
            this.currentIndex = index;
            this.stopGalleryRotation();
        },

        stopGalleryRotation() {
            if (this._galleryTimer) {
                window.clearInterval(this._galleryTimer);
                this._galleryTimer = null;
            }
        },

        onThumbTouchStart(event) {
            this._thumbTouchX = event.changedTouches?.[0]?.clientX ?? null;
        },

        onThumbTouchEnd(event) {
            if (this._thumbTouchX === null) return;

            const endX = event.changedTouches?.[0]?.clientX ?? this._thumbTouchX;
            const delta = endX - this._thumbTouchX;
            this._thumbTouchX = null;

            // Below this the gesture reads as a tap on a thumbnail, not a swipe.
            if (Math.abs(delta) < 40) return;

            this.slideThumbs(delta < 0 ? 1 : -1);
        },

        get productSubtotal() {
            return Math.max(this.price * this.quantity, 0);
        },

        get shippingCost() {
            if (this.freeShipping) return 0;
            return this.selectedKurir ? Number(this.selectedKurir.harga) || 0 : 0;
        },

        get hasCheckoutPromo() {
            const promo = this.checkoutPromo;
            if (promo) {
                return (
                    (Number(promo.persentase_promo) || 0) > 0 ||
                    (Number(promo.harga_promo) || 0) > 0
                );
            }
            return this.promo > 0;
        },

        get totalWithShipping() {
            return Math.max(this.productSubtotal + this.shippingCost, 0);
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
            return typeof window !== 'undefined' ? window.location.href.split('#')[0] : '';
        },

        get shareImageUrl() {
            const raw = this.shareImage || this.gallery?.[0] || '';
            if (!raw) return '';
            try {
                return new URL(raw, window.location.origin).href;
            } catch {
                return raw;
            }
        },

        get shareCaption() {
            const title = (this.title || 'Evomi').trim();
            const desc = String(this.description || '')
                .replace(/<[^>]+>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
            const shortDesc = desc.length > 180 ? `${desc.slice(0, 177)}…` : desc;
            const lines = [title];
            if (shortDesc) lines.push('', shortDesc);
            lines.push('', this.productUrl);
            return lines.join('\n');
        },

        get sharePreviewDesc() {
            const desc = String(this.description || '')
                .replace(/<[^>]+>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
            return desc.length > 110 ? `${desc.slice(0, 107)}…` : desc;
        },

        get shareLinks() {
            const title = (this.title || 'Evomi').trim();
            const desc = String(this.description || '')
                .replace(/<[^>]+>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
            const shortDesc = desc.length > 140 ? `${desc.slice(0, 137)}…` : desc;
            const waBody = [title, shortDesc, this.productUrl].filter(Boolean).join('\n\n');
            const twBody = [title, shortDesc].filter(Boolean).join(' — ');
            const url = encodeURIComponent(this.productUrl);
            return {
                whatsapp: `https://api.whatsapp.com/send?text=${encodeURIComponent(waBody)}`,
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
                twitter: `https://twitter.com/intent/tweet?url=${url}&text=${encodeURIComponent(twBody)}`,
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

            // Whatever moves the hero image - a dot, a thumbnail, the timer -
            // the thumbnail window follows it.
            this.$watch('currentIndex', () => this.ensureThumbVisible());

            this.$nextTick(() => {
                this.syncDetailHeight();
                this.syncThumbShift();
            });

            if (typeof ResizeObserver !== 'undefined') {
                this._resizeObserver = new ResizeObserver(() => this.syncDetailHeight());
                if (this.$refs.diskusiBox) this._resizeObserver.observe(this.$refs.diskusiBox);
                if (this.$refs.jaminanBox) this._resizeObserver.observe(this.$refs.jaminanBox);
            }

            this._onResize = () => {
                this.syncDetailHeight();
                this.syncThumbShift();
            };
            window.addEventListener('resize', this._onResize);

            this._onWishlistSync = () => this.syncWishlistState();
            window.addEventListener('wishlist_updated', this._onWishlistSync);
            window.addEventListener('auth-change', this._onWishlistSync);
            this.syncWishlistState();

            this._onChatReload = () => {
                if (this.isChatOpen && getAuthToken()) this.loadChatThread(true);
            };
            this._onProductChatSync = (event) => {
                if (Array.isArray(event.detail)) this.chatBubbles = event.detail;
            };
            window.addEventListener('chat_updated', this._onChatReload);
            window.addEventListener('evomi-chat-reload', this._onChatReload);
            window.addEventListener('evomi-product-chat-sync', this._onProductChatSync);

            const shared = Alpine.store('evomiProductChat')?.bubbles;
            if (Array.isArray(shared) && shared.length) {
                this.chatBubbles = shared;
            }
        },

        destroy() {
            this.stopGalleryRotation();
            if (this._resizeObserver) this._resizeObserver.disconnect();
            if (this._onResize) window.removeEventListener('resize', this._onResize);
            if (this._onWishlistSync) {
                window.removeEventListener('wishlist_updated', this._onWishlistSync);
                window.removeEventListener('auth-change', this._onWishlistSync);
            }
            this.stopChatPoll();
            if (this._onChatReload) {
                window.removeEventListener('chat_updated', this._onChatReload);
                window.removeEventListener('evomi-chat-reload', this._onChatReload);
            }
            if (this._onProductChatSync) {
                window.removeEventListener('evomi-product-chat-sync', this._onProductChatSync);
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
            const productId = Number(this.id);
            if (!Number.isFinite(productId) || productId <= 0) {
                this.requireLogin(storefrontL('Produk tidak valid untuk checkout.', 'Invalid product for checkout.'));
                return;
            }
            const kurirId = this.selectedKurir?.id || '';
            const params = new URLSearchParams({
                type: 'buynow',
                productId: String(productId),
                qty: String(this.quantity),
                unitPrice: String(this.price),
            });
            if (kurirId) params.set('kurirId', String(kurirId));
            const qs = params.toString();
            try {
                sessionStorage.setItem('evomi_checkout_qs', qs);
            } catch {
                /* ignore */
            }
            softNavigate(`/checkout?${qs}`);
        },

        async addToCart(event) {
            if (this.isOutOfStock || this.actionBusy) return;
            this.actionBusy = true;
            this.statusMessage = storefrontL('Menambah...', 'Adding...');
            this.statusTone = 'info';
            const sourceEl =
                event?.currentTarget ||
                document.querySelector('[data-belanja-add-cart]') ||
                document.querySelector('[data-belanja-hero-image]');
            const imageUrl = this.gallery?.[0] || this.shareImage || '';
            const accent = this.accent || '#1172BA';
            try {
                if (getAuthToken()) {
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
                        upsertGuestCartItem({
                            productId: this.id,
                            quantity: this.quantity,
                            title: this.title,
                            price: this.price,
                            image: imageUrl,
                            accent,
                            stock: this.stock,
                        });
                        this.flashStatus(storefrontL('Ditambahkan ke keranjang!', 'Added to cart!'), 'success');
                        await flyToCart({ imageUrl, accent, sourceEl });
                        return;
                    }
                    if (!res.ok) {
                        throw new Error(apiErrorMessage(data, storefrontL('Gagal menambah ke keranjang.', 'Failed to add to cart.')));
                    }
                    emitEvomiEvent('cart_updated');
                } else {
                    upsertGuestCartItem({
                        productId: this.id,
                        quantity: this.quantity,
                        title: this.title,
                        price: this.price,
                        image: imageUrl,
                        accent,
                        stock: this.stock,
                    });
                    this.flashStatus(storefrontL('Ditambahkan ke keranjang!', 'Added to cart!'), 'success');
                    await flyToCart({ imageUrl, accent, sourceEl });
                    window.setTimeout(() => {
                        const nav = window.__evomiNav;
                        if (nav && typeof nav.showGuestCartWarning === 'function') {
                            nav.showGuestCartWarning();
                        }
                    }, 200);
                    return;
                }
                this.flashStatus(storefrontL('Ditambahkan ke keranjang!', 'Added to cart!'), 'success');
                await flyToCart({ imageUrl, accent, sourceEl });
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

        async toggleWishlist(event) {
            if (!getAuthToken()) {
                this.requireLogin(storefrontL('Silakan login terlebih dahulu untuk menambah wishlist.', 'Please log in first to add to wishlist.'));
                return;
            }
            if (this.actionBusy || this.wishlistBusy) return;
            this.wishlistBusy = true;
            this.wishlistMessage = '';
            const sourceEl = event?.currentTarget || null;
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
                    spawnWishlistHeartBurst(sourceEl, { mode: 'remove', color: this.accent || DEFAULT_THEME_BLUE });
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
                            spawnWishlistHeartBurst(sourceEl, { mode: 'add', color: this.accent || DEFAULT_THEME_BLUE });
                            return;
                        }
                        throw new Error(message);
                    }
                    this.isWishlisted = true;
                    this.wishlistId = data?.id || data?.data?.id || null;
                    if (!this.wishlistId) await this.syncWishlistState();
                    this.wishlistMessage = storefrontL('Ditambahkan ke wishlist!', 'Added to wishlist!');
                    emitEvomiEvent('wishlist_updated');
                    spawnWishlistHeartBurst(sourceEl, { mode: 'add', color: this.accent || DEFAULT_THEME_BLUE });
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

        applySharedChat(bubbles) {
            this.chatBubbles = Array.isArray(bubbles) ? bubbles : [];
            const store = Alpine.store('evomiProductChat');
            if (store) store.bubbles = this.chatBubbles;
            window.dispatchEvent(
                new CustomEvent('evomi-product-chat-sync', { detail: this.chatBubbles }),
            );
        },

        async loadChatThread(silent = false) {
            const user = getAuthUser();
            if (!getAuthToken() || !user?.email) {
                this.applySharedChat([]);
                return;
            }
            if (!silent) this.chatLoading = true;
            try {
                const bubbles = await fetchContactThread(user.email);
                this.applySharedChat(bubbles);
                this.$nextTick(() => this.scrollChatLatest());
            } catch {
                /* keep previous bubbles */
            } finally {
                this.chatLoading = false;
            }
        },

        scrollChatLatest() {
            const pane = this.$refs.chatThread;
            if (!pane) return;
            pane.scrollTop = pane.scrollHeight;
        },

        startChatPoll() {
            this.stopChatPoll();
            this._chatPoll = window.setInterval(() => {
                if (this.isChatOpen && getAuthToken()) this.loadChatThread(true);
            }, 30000);
        },

        stopChatPoll() {
            if (this._chatPoll) {
                window.clearInterval(this._chatPoll);
                this._chatPoll = null;
            }
        },

        closeChat() {
            this.stopChatCaptcha();
            this.isChatOpen = false;
            this.stopChatPoll();
            this.chatError = '';
        },

        async openChat() {
            this.isChatOpen = true;
            this.chatError = '';
            await this.$nextTick();
            await this.startFreshCaptcha();
            await this.loadChatThread();
            this.startChatPoll();
        },

        async sendChat() {
            const text = (this.draft || '').trim();
            if (!text || this.chatSending) return;
            if (!getAuthToken()) {
                this.requireLogin(storefrontL('Anda harus login terlebih dahulu untuk mengirim pesan ke admin.', 'Please log in first to message admin.'));
                this.closeChat();
                return;
            }

            const captchaToken = this.chatCaptchaToken();
            if (captchaToken === null) {
                this.chatError = turnstileRequiredMessage();
                return;
            }

            this.chatSending = true;
            this.chatError = '';

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
                        _hp: '',
                        captcha_token: captchaToken,
                        captcha_scope: this.captchaScope || undefined,
                    }),
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    if (data?.captcha_required) {
                        await this.askChatCaptchaAgain();
                    }
                    if (data?.captcha_required || res.status === 429) {
                        this.chatError = apiErrorMessage(data, storefrontL('Gagal mengirim pesan.', 'Failed to send message.'));
                        return;
                    }
                    throw new Error(apiErrorMessage(data, storefrontL('Gagal mengirim pesan.', 'Failed to send message.')));
                }
                this.markChatCaptchaPassed();
                this.draft = '';
                await this.loadChatThread(true);
                emitEvomiEvent('chat_updated');
                emitEvomiEvent('evomi-chat-reload');
            } catch (err) {
                this.requireLogin(err instanceof Error ? err.message : storefrontL('Gagal mengirim pesan.', 'Failed to send message.'));
                this.closeChat();
            } finally {
                this.chatSending = false;
            }
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.shareCaption);
                this.isCopied = true;
                this.shareHint = storefrontL(
                    'Judul, deskripsi, dan link produk disalin.',
                    'Product title, description, and link copied.',
                );
                window.setTimeout(() => {
                    this.isCopied = false;
                    this.shareHint = '';
                }, 2500);
            } catch {
                this.isCopied = false;
                this.shareHint = '';
            }
        },

        async shareInstagram() {
            const payload = {
                title: this.title || 'Evomi',
                text: this.shareCaption,
                url: this.productUrl,
            };
            if (navigator.share) {
                try {
                    await navigator.share(payload);
                    this.shareHint = storefrontL(
                        'Bagikan ke Instagram dari menu share perangkat.',
                        'Share to Instagram from your device share sheet.',
                    );
                    window.setTimeout(() => {
                        this.shareHint = '';
                    }, 2500);
                    return;
                } catch (err) {
                    if (err && err.name === 'AbortError') return;
                }
            }
            await this.copyLink();
            this.shareHint = storefrontL(
                'Caption + link disalin. Tempel di Instagram (preview gambar muncul dari link).',
                'Caption + link copied. Paste in Instagram (image preview comes from the link).',
            );
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
        // Form kontak dikirim tanpa header auth, jadi server selalu menilainya
        // sebagai tamu — captcha wajib tampil walau pengunjung sedang login.
        ...turnstileState(),

        async init() {
            await this.$nextTick();
            await setupTurnstile(this, this.$refs.turnstile, { theme: 'light' });
        },

        runTurnstile() {
            runTurnstile(this);
        },

        async submit() {
            this.loading = true;
            this.status = { type: null, message: '' };

            let captchaToken = '';
            if (this.hasTurnstile) {
                captchaToken = turnstileToken(this);
                if (!captchaToken) {
                    this.status = {
                        type: 'error',
                        message: turnstileRequiredMessage(),
                    };
                    this.loading = false;
                    return;
                }
            }

            try {
                const res = await fetch('/api/contact', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.form,
                        _hp: '',
                        captcha_token: captchaToken,
                    }),
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
                resetTurnstile(this);
                this.loading = false;
            }
        },
    }));

    Alpine.data('evomiNewsletter', (ctaLabel = 'Daftar') => ({
        email: '',
        submitting: false,
        toast: null,
        ctaLabel,
        ...turnstileState(),

        async init() {
            await this.$nextTick();
            await setupTurnstile(this, this.$refs.turnstile, { theme: 'light' });
        },

        runTurnstile() {
            runTurnstile(this);
        },

        async submit() {
            const value = (this.email || '').trim();
            if (!value) {
                this.toast = {
                    type: 'error',
                    title: storefrontL('Perhatian', 'Notice'),
                    message: storefrontL(
                        'Harap masukkan alamat email Anda terlebih dahulu.',
                        'Please enter your email address first.',
                    ),
                };
                setTimeout(() => {
                    this.toast = null;
                }, 3000);
                return;
            }

            let captchaToken = '';
            if (this.hasTurnstile) {
                captchaToken = turnstileToken(this);
                if (!captchaToken) {
                    this.toast = {
                        type: 'error',
                        title: storefrontL('Perhatian', 'Notice'),
                        message: turnstileRequiredMessage(),
                    };
                    setTimeout(() => {
                        this.toast = null;
                    }, 3000);
                    return;
                }
            }

            this.submitting = true;
            this.toast = {
                type: 'loading',
                title: storefrontL('Memproses...', 'Processing...'),
                message: storefrontL(
                    'Sedang mendaftarkan email Anda ke Buletin Evomi.',
                    'Subscribing your email to Evomi Bulletin.',
                ),
            };

            try {
                const res = await fetch('/api/newsletter/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ email: value, _hp: '', captcha_token: captchaToken }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(
                        data.message || storefrontL('Gagal mendaftar buletin.', 'Failed to subscribe to the bulletin.'),
                    );
                }
                this.toast = {
                    type: 'success',
                    title: storefrontL('Berhasil!', 'Success!'),
                    message: storefrontL(
                        'Terima kasih telah berlangganan Buletin Evomi.',
                        'Thanks for subscribing to Evomi Bulletin.',
                    ),
                };
                this.email = '';
            } catch (err) {
                this.toast = {
                    type: 'error',
                    title: storefrontL('Pendaftaran Gagal', 'Subscription Failed'),
                    message:
                        err && err.message
                            ? err.message
                            : storefrontL(
                                  'Terjadi kesalahan pada server. Coba lagi nanti.',
                                  'A server error occurred. Please try again later.',
                              ),
                };
            } finally {
                resetTurnstile(this);
                this.submitting = false;
                setTimeout(() => {
                    if (this.toast && (this.toast.type === 'success' || this.toast.type === 'error')) {
                        this.toast = null;
                    }
                }, 3000);
            }
        },
    }));

    Alpine.data('evomiKuis', (questions = [], results = {}) => ({
        questions,
        results,
        step: 0,
        finished: false,
        ready: false,
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

        init() {
            this.ready = false;
            if (this._readyTimer) window.clearTimeout(this._readyTimer);
            this._readyTimer = window.setTimeout(() => {
                this.ready = true;
            }, 550);
        },

        destroy() {
            if (this._readyTimer) window.clearTimeout(this._readyTimer);
        },

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
                playBelanjaEntrance(this.$el);
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
            this.ready = true;
            this.scores = {
                peaceful_calm: 0,
                purpose_prestige: 0,
                sweet_shy: 0,
                rebel_brave: 0,
            };
            restoreProductTheme();
            this.$nextTick(() => {
                playBelanjaEntrance(this.$el);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        },
    }));

    Alpine.data('evomiAuth', (mode = 'login') => ({
        mode,
        loading: false,
        error: '',
        showPassword: false,
        form: { name: '', email: '', password: '', remember: true },
        ...turnstileState(),
        modal: {
            show: false,
            type: 'success',
            title: '',
            message: '',
            cta: 'Tutup',
            go: null,
        },

        async init() {
            await this.$nextTick();
            await setupTurnstile(this, this.$refs.turnstile, { theme: 'dark' });
        },

        runTurnstile() {
            runTurnstile(this);
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

            let captchaToken = '';
            if (this.hasTurnstile) {
                captchaToken = turnstileToken(this);
                if (!captchaToken) {
                    if (this.mode === 'login') {
                        this.openModal({
                            type: 'warning',
                            title: 'Verifikasi Diperlukan',
                            message: turnstileRequiredMessage(),
                            cta: 'Mengerti',
                        });
                    } else {
                        this.error = turnstileRequiredMessage();
                    }
                    return;
                }
            }

            this.loading = true;

            try {
                const endpoint = this.mode === 'login' ? '/api/login' : '/api/register';
                const remember = !!this.form.remember;
                const body =
                    this.mode === 'login'
                        ? { email: this.form.email, password, remember, captcha_token: captchaToken }
                        : {
                              name: this.form.name.trim(),
                              email: this.form.email,
                              password,
                              remember,
                              _hp: '',
                              captcha_token: captchaToken,
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
                    resetTurnstile(this);
                    return;
                }

                const user = data.user || {};
                const token = data.token || '';
                if (!token) {
                    throw new Error('Token tidak diterima dari server.');
                }

                setAuthSession(token, user, remember, data.expires_at || null);
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
                resetTurnstile(this);
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

    Alpine.data('evomiForgotPassword', () => ({
        loading: false,
        error: '',
        sent: false,
        sentTo: '',
        form: { email: '', _hp: '' },
        ...turnstileState(),
        modal: { show: false, type: 'success', title: '', message: '', cta: 'Tutup', go: null },

        async init() {
            await this.$nextTick();
            await setupTurnstile(this, this.$refs.turnstile, { theme: 'dark' });
        },

        runTurnstile() {
            runTurnstile(this);
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

        reopen() {
            this.sent = false;
            this.error = '';
            resetTurnstile(this);
        },

        async submit() {
            this.error = '';
            const email = (this.form.email || '').trim();

            if (!email) {
                this.error = 'Email wajib diisi.';
                return;
            }

            let captchaToken = '';
            if (this.hasTurnstile) {
                captchaToken = turnstileToken(this);
                if (!captchaToken) {
                    this.error = turnstileRequiredMessage();
                    return;
                }
            }

            this.loading = true;

            try {
                const res = await fetch('/api/forgot-password', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        email,
                        _hp: this.form._hp || '',
                        captcha_token: captchaToken,
                    }),
                });

                const data = await readApiJson(res);

                if (!res.ok) {
                    this.error = apiErrorMessage(
                        data,
                        'Permintaan reset gagal dikirim. Silakan coba lagi.',
                    );
                    resetTurnstile(this);
                    return;
                }

                this.sentTo = email;
                this.sent = true;
                this.form._hp = '';
            } catch (err) {
                this.error =
                    err instanceof Error ? err.message : 'Tidak dapat terhubung ke server.';
                resetTurnstile(this);
            } finally {
                this.loading = false;
                // Token Turnstile sekali pakai: siapkan widget untuk permintaan berikutnya.
                if (this.sent) resetTurnstile(this);
            }
        },

        closeModal() {
            const go = this.modal.go;
            const type = this.modal.type;
            this.modal.show = false;
            if (go && type === 'success') softNavigate(go);
        },
    }));

    Alpine.data('evomiResetPassword', (token = '', email = '') => ({
        loading: false,
        error: '',
        showPassword: false,
        form: { email, password: '', password_confirmation: '' },
        token,
        ...turnstileState(),
        modal: { show: false, type: 'success', title: '', message: '', cta: 'Tutup', go: null },

        async init() {
            await this.$nextTick();
            await setupTurnstile(this, this.$refs.turnstile, { theme: 'dark' });
        },

        runTurnstile() {
            runTurnstile(this);
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

        async submit() {
            this.error = '';
            const password = this.form.password || '';

            if (password.length < 8) {
                this.error = 'Password minimal 8 karakter.';
                return;
            }

            if (password !== this.form.password_confirmation) {
                this.error = 'Konfirmasi password tidak cocok.';
                return;
            }

            let captchaToken = '';
            if (this.hasTurnstile) {
                captchaToken = turnstileToken(this);
                if (!captchaToken) {
                    this.error = turnstileRequiredMessage();
                    return;
                }
            }

            this.loading = true;

            try {
                const res = await fetch('/api/reset-password', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        token: this.token,
                        email: this.form.email,
                        password,
                        password_confirmation: this.form.password_confirmation,
                        captcha_token: captchaToken,
                    }),
                });

                const data = await readApiJson(res);

                if (!res.ok) {
                    this.error = apiErrorMessage(
                        data,
                        'Reset password gagal. Silakan minta tautan baru.',
                    );
                    resetTurnstile(this);
                    return;
                }

                this.openModal({
                    type: 'success',
                    title: 'Password Diperbarui',
                    message:
                        'Password baru Anda sudah aktif. Silakan masuk kembali menggunakan password tersebut.',
                    cta: 'Masuk Sekarang',
                    go: '/login',
                });
            } catch (err) {
                this.error =
                    err instanceof Error ? err.message : 'Tidak dapat terhubung ke server.';
                resetTurnstile(this);
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
        chartPeriod: 'day',
        chartPeriodOptions: [
            { id: 'day', label: 'Hari' },
            { id: 'week', label: 'Minggu' },
            { id: 'month', label: 'Bulan' },
        ],
        chartOrders: [],
        chartData: [],
        chartTable: [],
        chartPeriodLabel: 'hari',
        salesChart: null,
        chartHover: null,
        recentOrders: [],

        async init() {
            await this.load();
        },

        applyChartPeriod(period = this.chartPeriod) {
            const mode = period === 'week' || period === 'month' ? period : 'day';
            this.chartPeriod = mode;
            this.chartHover = null;

            const { chartData, tableRows, totalRevenue, period: resolved } = buildSalesSeries(
                this.chartOrders,
                mode,
            );
            this.chartData = chartData;
            this.chartTable = tableRows.map((row) => ({
                ...row,
                totalLabel: formatRupiah(row.total),
            }));
            this.stats.totalRevenue = totalRevenue;
            this.chartPeriodLabel =
                resolved === 'month' ? 'bulan' : resolved === 'week' ? 'minggu' : 'hari';
            this.salesChart = buildSalesChartModel(this.chartData, formatRupiah, 'Pendapatan');

            this.$nextTick(() => {
                requestAnimationFrame(() => this.paintSalesChart());
            });
        },

        setChartPeriod(period) {
            if (this.chartPeriod === period) return;
            this.applyChartPeriod(period);
        },

        paintSalesChart(attempt = 0) {
            const mount = this.$refs?.salesChartMount;
            if (!mount) {
                if (attempt < 12) {
                    requestAnimationFrame(() => this.paintSalesChart(attempt + 1));
                }
                return;
            }

            this.chartHover = null;
            salesChartClearHover(this.$refs?.salesChartBox);

            if (!this.chartData.length || !this.salesChart?.svg) {
                mount.innerHTML =
                    '<div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Belum ada data penjualan.</div>';
                return;
            }

            mount.innerHTML = this.salesChart.svg;
        },

        async load() {
            this.loading = true;
            this.error = '';
            this.chartHover = null;
            this.salesChart = null;
            this.chartData = [];
            this.chartTable = [];
            this.chartOrders = [];
            try {
                const headers = authHeaders(true);
                const [productsRes, ordersRes, usersRes] = await Promise.all([
                    fetch('/api/products', { headers: { Accept: 'application/json' } }),
                    fetch('/api/admin/orders', { headers }),
                    fetch('/api/admin/users', { headers }),
                ]);

                if (ordersRes.status === 401 || ordersRes.status === 403) {
                    clearAuthSession();
                    window.location.replace('/login');
                    return;
                }

                const products = await readApiJson(productsRes);
                const orders = await readApiJson(ordersRes);
                const users = await readApiJson(usersRes);

                if (!ordersRes.ok) {
                    throw new Error(apiErrorMessage(orders, 'Gagal memuat pesanan admin.'));
                }

                const productsList = products?.data || products || [];
                const ordersRaw = orders?.data || orders || [];
                const ordersList = Array.isArray(ordersRaw)
                    ? ordersRaw
                    : Array.isArray(ordersRaw?.data)
                      ? ordersRaw.data
                      : [];
                const usersList = users?.data || users || [];

                this.stats = {
                    totalProducts: Array.isArray(productsList) ? productsList.length : 0,
                    totalOrders: ordersList.length,
                    activeUsers: Array.isArray(usersList) ? usersList.length : 0,
                    totalRevenue: 0,
                };

                this.chartOrders = ordersList;
                this.applyChartPeriod(this.chartPeriod);

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
                this.$nextTick(() => {
                    requestAnimationFrame(() => this.paintSalesChart());
                });
            }
        },

        onChartMove(event) {
            if (!this.salesChart?.coords?.length || !this.$refs?.salesChartBox) {
                this.chartHover = null;
                return;
            }
            this.chartHover = salesChartHoverAt(
                this.salesChart,
                event.clientX,
                this.$refs.salesChartBox,
            );
        },

        onChartLeave() {
            this.chartHover = null;
            salesChartClearHover(this.$refs?.salesChartBox);
        },

        formatRupiah,
        salesPeriodUnitLabel,
    }));

    Alpine.data('evomiProfileShell', (initialKey = 'settings') => ({
        ready: false,
        activeKey: initialKey || 'settings',
        badges: { cart: 0, wishlist: 0, history: 0, payments: 0, unread: 0 },
        // Identitas pemilik akun, ditampilkan di kepala menu.
        userName: '',
        userEmail: '',
        avatarUrl: '',
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

        get userInitial() {
            const sumber = (this.userName || this.userEmail || '?').trim();

            return (sumber.charAt(0) || '?').toUpperCase();
        },

        syncIdentity() {
            const user = getAuthUser() || {};
            this.userName = user.name || user.nama_lengkap || '';
            this.userEmail = user.email || '';
            this.avatarUrl = avatarUrlFromUser(user) || '';
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
                if (this.activeKey === 'cart') {
                    this.ready = true;
                    this.badges = {
                        cart: guestCartCount(),
                        wishlist: 0,
                        history: 0,
                        payments: 0,
                        unread: 0,
                    };
                    this._onBadge = () => {
                        this.badges = {
                            ...this.badges,
                            cart: guestCartCount(),
                        };
                    };
                    window.addEventListener('cart_updated', this._onBadge);
                    this.$nextTick(() => {
                        this.moveIndicator(this.activeKey, false);
                        bindSoftLinks(this.$el);
                    });
                    return;
                }
                window.location.replace('/login');
                return;
            }
            this.ready = true;
            this.syncIdentity();
            await this.refreshBadges();
            this._onBadge = () => {
                this.syncIdentity();
                this.refreshBadges();
            };
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
        // Tiap baris biodata tampil sebagai teks sampai tombol Ubah ditekan.
        editing: {},
        avatarPreview: null,
        avatarFile: null,
        avatarPath: null,
        passwordChangedAt: null,
        // Nilai terakhir yang datang dari server, dipakai menghitung apa saja
        // yang benar-benar berubah supaya modalnya bisa menyebutkannya.
        snapshot: {},
        successModal: { open: false, changes: [] },
        _successTimer: null,
        lastLoginAt: null,
        lastSeenAt: null,
        lastLoginLabel: '',
        lastSeenLabel: '',
        lastSeenExact: '',

        get initial() {
            return userDisplayInitial(this.form.name || this.form.email);
        },

        async init() {
            this._onReload = () => {
                if (getAuthToken()) this.load();
            };
            window.addEventListener('evomi-settings-reload', this._onReload);

            const inModal = !!this.$el?.closest?.('.evomi-settings-modal');
            if (inModal) {
                this.loading = false;
                if (Alpine.store('evomiSettingsModal')?.open && getAuthToken()) {
                    await this.load();
                }
                return;
            }

            if (!getAuthToken()) {
                this.loading = false;
                return;
            }
            await this.load();
        },

        destroy() {
            if (this._onReload) {
                window.removeEventListener('evomi-settings-reload', this._onReload);
            }
            if (this._toastTimer) window.clearTimeout(this._toastTimer);
            if (this._successTimer) window.clearTimeout(this._successTimer);
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        },

        isEditing(field) {
            return !!this.editing[field];
        },

        toggleEdit(field) {
            this.editing = { ...this.editing, [field]: !this.editing[field] };
        },

        /** Apa yang tampil saat baris sedang tidak diubah. */
        displayValue(field) {
            const nilai = String(this.form[field] ?? '').trim();
            if (nilai !== '') return nilai;

            return storefrontL('Belum diisi', 'Not set');
        },

        /**
         * Keterangan pengganti kata sandi.
         *
         * Nilai aslinya tidak pernah bisa ditampilkan - yang tersimpan cuma hash
         * bcrypt - jadi barisnya memberi kepastian lewat tanggal terakhir diubah.
         * Akun lama belum punya catatan itu, dan menebaknya dari updated_at akan
         * menampilkan tanggal keliru, jadi cukup ditulis "Tersimpan" saja.
         */
        get passwordStatusLabel() {
            if (!this.passwordChangedAt) {
                return storefrontL('Tersimpan', 'Saved');
            }

            const tanggal = this.formatPresence(this.passwordChangedAt);

            return storefrontL('Tersimpan · terakhir diubah ', 'Saved · last changed ') + tanggal;
        },

        /** Daftar hal yang berubah, untuk ditulis di modal sukses. */
        collectChanges() {
            const label = {
                name: storefrontL('Nama', 'Name'),
                email: storefrontL('Email', 'Email'),
                phone: storefrontL('Nomor telepon', 'Phone number'),
                address: storefrontL('Alamat pengiriman', 'Shipping address'),
            };

            const out = Object.keys(label).filter(
                (k) => String(this.form[k] ?? '').trim() !== String(this.snapshot[k] ?? '').trim(),
            ).map((k) => label[k]);

            if (this.form.password) out.push(storefrontL('Kata sandi', 'Password'));
            if (this.avatarFile) out.push(storefrontL('Foto profil', 'Profile photo'));

            return out;
        },

        openSuccessModal(changes) {
            this.successModal = { open: true, changes };

            // Mengunci scroll menghilangkan batang gulir, dan halaman di
            // belakangnya melompat selebar batang itu. Lebarnya diganti dengan
            // padding supaya tidak ada yang bergeser saat modal muncul.
            const lebarBatangGulir = window.innerWidth - document.documentElement.clientWidth;
            document.body.style.overflow = 'hidden';
            if (lebarBatangGulir > 0) {
                document.body.style.paddingRight = `${lebarBatangGulir}px`;
            }

            if (this._successTimer) window.clearTimeout(this._successTimer);
            this._successTimer = window.setTimeout(() => this.closeSuccessModal(), 6000);
        },

        closeSuccessModal() {
            if (this._successTimer) {
                window.clearTimeout(this._successTimer);
                this._successTimer = null;
            }
            this.successModal = { ...this.successModal, open: false };
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
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
                this.snapshot = { ...this.form, password: '' };
                this.passwordChangedAt = user.password_changed_at || null;
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
                if (!this._enterPlayed && !this.$el?.closest?.('.evomi-settings-modal')) {
                    this._enterPlayed = true;
                    this.$nextTick(() => playBelanjaEntrance(this.$el));
                }
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
            // Tidak ada spanduk "menyimpan" di dalam kartu: memunculkannya
            // mendorong seluruh isi biodata ke bawah, lalu isinya melompat balik
            // saat spanduk itu hilang. Tombolnya sendiri sudah berubah menjadi
            // "Menyimpan..." dan itu tidak menggeser apa pun.
            this.status = { type: '', message: '' };
            try {
                // Baris kata sandi yang dibuka berarti pemilik memang berniat
                // menggantinya; menyimpannya dalam keadaan kosong hampir pasti
                // tidak disengaja, jadi ditolak alih-alih diam-diam dilewati.
                if (this.editing.password && !String(this.form.password || '').trim()) {
                    throw new Error(
                        storefrontL(
                            'Kata sandi baru belum diisi. Isi kata sandinya, atau tutup baris itu bila tidak jadi mengubah.',
                            'The new password is empty. Fill it in, or close that row if you are not changing it.',
                        ),
                    );
                }

                if (this.form.password && this.form.password.length < 8) {
                    throw new Error(storefrontL('Kata sandi baru minimal 8 karakter.', 'The new password must be at least 8 characters.'));
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
                const changes = this.collectChanges();

                this.status = { type: '', message: '' };
                // Baris yang tadi dibuka dikembalikan ke tampilan teks: perubahannya
                // sudah tersimpan, jadi membiarkannya terbuka hanya mengundang
                // pengiriman ulang yang tidak perlu.
                this.editing = {};
                this.snapshot = { ...this.form, password: '' };
                this.passwordChangedAt = user.password_changed_at || this.passwordChangedAt;
                this.openSuccessModal(changes);
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
        removingId: null,
        checkingOut: false,
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

        goProduct(item) {
            if (!item?.product_id) return;
            const href = `/belanja/${encodeURIComponent(String(item.product_id))}`;
            if (item.accent) rememberProductChrome(href, item.accent);
            if (typeof window.softNavigate === 'function') {
                window.softNavigate(href, { chromeHint: item.accent || '' });
            } else {
                window.location.assign(href);
            }
        },

        mapItem(row) {
            const product = row.product || {};
            const unit = productPrice(product);
            const qty = Number(row.quantity) || 1;
            const sizeRaw = product.bottle_size || product.size || product.volume || '';
            const sizeNum = Number(String(sizeRaw).replace(/[^\d.]/g, ''));
            const sizeLabel = sizeRaw
                ? sizeNum
                    ? `${sizeNum}ml`
                    : String(sizeRaw).toLowerCase().includes('ml')
                      ? String(sizeRaw)
                      : `${sizeRaw}ml`
                : '';
            return {
                id: row.id,
                product_id: row.product_id || product.id,
                title: productTitle(product),
                sizeLabel,
                genderLabel: productGenderLabel(product),
                imageUrl: productImage(product, 'cart'),
                accent: productAccent(product),
                stock: Number(product.quantity ?? product.stock ?? 0) || 0,
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
                if (!getAuthToken()) {
                    const list = readGuestCart();
                    this.items = list.map((row) => ({
                        id: row.id || `guest-${row.product_id}`,
                        product_id: row.product_id,
                        title: row.title || `Produk #${row.product_id}`,
                        imageUrl: row.image || '',
                        accent: row.accent || '#1172BA',
                        stock: Number(row.stock ?? 99) || 99,
                        quantity: Number(row.quantity) || 1,
                        unitPrice: Number(row.price) || 0,
                        priceLabel: formatRupiah(Number(row.price) || 0),
                        lineTotalLabel: formatRupiah((Number(row.price) || 0) * (Number(row.quantity) || 1)),
                        isGuest: true,
                    }));
                    emitEvomiEvent('cart_updated');
                    return;
                }
                const res = await fetch('/api/carts?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    const list = readGuestCart();
                    this.items = list.map((row) => ({
                        id: row.id || `guest-${row.product_id}`,
                        product_id: row.product_id,
                        title: row.title || `Produk #${row.product_id}`,
                        imageUrl: row.image || '',
                        accent: row.accent || '#1172BA',
                        stock: Number(row.stock ?? 99) || 99,
                        quantity: Number(row.quantity) || 1,
                        unitPrice: Number(row.price) || 0,
                        priceLabel: formatRupiah(Number(row.price) || 0),
                        lineTotalLabel: formatRupiah((Number(row.price) || 0) * (Number(row.quantity) || 1)),
                        isGuest: true,
                    }));
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
                if (item.isGuest || !getAuthToken()) {
                    const items = readGuestCart().map((row) => {
                        if (Number(row.product_id) === Number(item.product_id)) {
                            return { ...row, quantity: next };
                        }
                        return row;
                    });
                    writeGuestCart(items);
                } else {
                    const res = await fetch(`/api/carts/${item.id}`, {
                        method: 'PUT',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                        body: JSON.stringify({ quantity: next }),
                    });
                    const data = await readApiJson(res);
                    if (!res.ok) throw new Error(apiErrorMessage(data, storefrontL('Gagal mengubah jumlah.', 'Failed to update quantity.')));
                    emitEvomiEvent('cart_updated');
                }
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
            if (this.removingId === item.id) return;
            this.removingId = item.id;
            try {
                if (item.isGuest || !getAuthToken()) {
                    writeGuestCart(readGuestCart().filter((row) => Number(row.product_id) !== Number(item.product_id)));
                    await wait(180);
                    this.items = this.items.filter((i) => i.id !== item.id);
                    this.showToast(storefrontL('Item dihapus dari keranjang.', 'Item removed from cart.'));
                    return;
                }
                const res = await fetch(`/api/carts/${item.id}`, {
                    method: 'DELETE',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    const data = await readApiJson(res);
                    throw new Error(apiErrorMessage(data, storefrontL('Gagal menghapus item.', 'Failed to remove item.')));
                }
                await wait(180);
                this.items = this.items.filter((i) => i.id !== item.id);
                emitEvomiEvent('cart_updated');
                this.showToast(storefrontL('Item dihapus dari keranjang.', 'Item removed from cart.'));
            } catch (err) {
                this.openError(err instanceof Error ? err.message : storefrontL('Gagal menghapus.', 'Failed to remove.'));
            } finally {
                this.removingId = null;
            }
        },

        async goCheckout() {
            if (!this.items.length) {
                this.showToast(storefrontL('Keranjang masih kosong.', 'Cart is still empty.'));
                return;
            }
            if (this.checkingOut) return;
            this.checkingOut = true;
            const qs = 'type=cart';
            try {
                sessionStorage.setItem('evomi_checkout_qs', qs);
            } catch {
                /* ignore */
            }
            await wait(220);
            if (typeof window.softNavigate === 'function') {
                window.softNavigate(`/checkout?${qs}`);
            } else {
                window.location.assign(`/checkout?${qs}`);
            }
            window.setTimeout(() => {
                this.checkingOut = false;
            }, 1200);
        },
    }));

    Alpine.data('evomiProfileWishlist', () => ({
        loading: true,
        error: '',
        items: [],
        toast: '',
        addingId: null,
        removingId: null,
        modal: { open: false, type: 'confirm', message: '', targetId: null },

        async init() {
            this._onReload = () => {
                if (getAuthToken()) this.load();
            };
            window.addEventListener('evomi-wishlist-reload', this._onReload);

            const inModal = !!this.$el?.closest?.('.evomi-wishlist-modal');
            if (inModal) {
                this.loading = false;
                if (Alpine.store('evomiWishlistModal')?.open && getAuthToken()) {
                    await this.load();
                }
                return;
            }

            if (!getAuthToken()) {
                this.loading = false;
                return;
            }
            await this.load();
        },

        destroy() {
            if (this._onReload) {
                window.removeEventListener('evomi-wishlist-reload', this._onReload);
            }
            if (this._toastTimer) window.clearTimeout(this._toastTimer);
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
                message: storefrontL(
                    `Hapus “${item.title || 'produk ini'}” dari wishlist?`,
                    `Remove “${item.title || 'this product'}” from wishlist?`,
                ),
                targetId: item.id,
            };
        },

        openError(message) {
            this.modal = { open: true, type: 'error', message, targetId: null };
        },

        goProduct(item) {
            if (!item?.product_id) return;
            const href = `/belanja/${encodeURIComponent(String(item.product_id))}`;
            if (item.accent) rememberProductChrome(href, item.accent);
            if (typeof window.softNavigate === 'function') {
                window.softNavigate(href, { chromeHint: item.accent || '' });
            } else {
                window.location.assign(href);
            }
        },

        mapItem(row) {
            const product = row.product || {};
            const sizeRaw = product.bottle_size || product.size || product.volume || '30';
            const sizeNum = Number(String(sizeRaw).replace(/[^\d.]/g, ''));
            const sizeLabel = sizeNum
                ? `${sizeNum}ml`
                : String(sizeRaw).toLowerCase().includes('ml')
                  ? String(sizeRaw)
                  : `${sizeRaw}ml`;
            const desc = String(product.description || product.deskripsi || product.short_description || '')
                .replace(/<[^>]+>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
            return {
                id: row.id,
                product_id: row.product_id || product.id,
                title: productTitle(product),
                description: desc || storefrontL('Aroma favorit Evomi.', 'An Evomi favorite scent.'),
                sizeLabel,
                genderLabel: productGenderLabel(product),
                imageUrl: productImage(product, 'wishlist'),
                accent: productAccent(product),
                priceLabel: formatRupiah(productPrice(product)),
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
            if (this.removingId === item.id) return;
            this.removingId = item.id;
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
                await wait(180);
                this.items = this.items.filter((i) => i.id !== item.id);
                emitEvomiEvent('wishlist_updated');
                this.showToast(storefrontL('Produk dihapus dari wishlist.', 'Product removed from wishlist.'));
            } catch (err) {
                this.openError(err instanceof Error ? err.message : storefrontL('Gagal menghapus.', 'Failed to remove.'));
            } finally {
                this.removingId = null;
            }
        },

        async moveToCart(item, event) {
            if (!getAuthToken()) {
                this.openError('Silakan login terlebih dahulu untuk menambahkan produk.');
                return;
            }
            if (this.addingId === item.id || this.removingId === item.id) return;
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
                    this.removingId = item.id;
                    await wait(160);
                    this.items = this.items.filter((i) => i.id !== item.id);
                    this.removingId = null;
                }
                emitEvomiEvent('cart_updated');
                emitEvomiEvent('wishlist_updated');
                await flyToCart({
                    imageUrl: item.imageUrl || item.image || '',
                    accent: item.accent || '#1172BA',
                    sourceEl: event?.currentTarget || null,
                });
                this.showToast(storefrontL('Ditambahkan ke keranjang.', 'Added to cart.'));
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

        goDetail(group) {
            const id = group?.groupId;
            if (id == null || id === '') return;
            const href = group?.isAwaitingCod
                ? (group.paymentUrl || `/pembayaran/${encodeURIComponent(orderInvoiceRoot(String(id)))}`)
                : `/profile/history/${encodeURIComponent(String(id))}`;
            if (typeof window.softNavigate === 'function') {
                window.softNavigate(href);
                return;
            }
            window.location.assign(href);
        },

        async init() {
            this._onReload = () => {
                if (getAuthToken()) this.load();
            };
            window.addEventListener('evomi-history-reload', this._onReload);

            const inModal = !!this.$el?.closest?.('.evomi-history-modal');
            if (inModal) {
                this.loading = false;
                this.perPage = 4;
                if (Alpine.store('evomiHistoryModal')?.open && getAuthToken()) {
                    await this.load();
                }
                return;
            }

            if (!getAuthToken()) {
                this.loading = false;
                return;
            }
            await this.load();
        },

        destroy() {
            if (this._onReload) {
                window.removeEventListener('evomi-history-reload', this._onReload);
            }
            if (this._toastTimer) window.clearTimeout(this._toastTimer);
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

        Alpine.data('evomiProfileHistoryShow', (orderId = null) => ({
        orderId: orderId != null ? String(orderId) : '',
        loading: true,
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

        get headerStyle() {
            const accent = this.themeColor;
            const dark = shadeHexColor(accent, -18);
            const light = shadeHexColor(accent, 12);
            return {
                background: `linear-gradient(135deg, ${dark} 0%, ${accent} 52%, ${light} 100%)`,
            };
        },

        get statusIcon() {
            const s = String(this.group?.status || '').toLowerCase();
            if (s === 'menunggu_konfirmasi') return 'clock';
            if (s === 'pengemasan') return 'box';
            if (s === 'dalam_perjalanan') return 'truck';
            return 'check';
        },

        async init() {
            const inModal = !!this.$el?.closest?.('.evomi-history-detail-modal');
            if (inModal) {
                const storeId = Alpine.store('evomiHistoryDetailModal')?.orderId;
                if (Alpine.store('evomiHistoryDetailModal')?.open && storeId) {
                    this.orderId = String(storeId);
                    await this.hydrate();
                } else {
                    this.loading = false;
                }
                return;
            }

            if (!this.orderId) {
                this.loading = false;
                return;
            }
            await this.hydrate();
        },

        destroy() {
            if (this._toastTimer) window.clearTimeout(this._toastTimer);
        },

        async onModalReload(e) {
            const id = String(e?.detail?.orderId || Alpine.store('evomiHistoryDetailModal')?.orderId || '').trim();
            if (!id) return;
            this.orderId = id;
            this.group = null;
            this.error = '';
            await this.hydrate();
        },

        async hydrate() {
            const cached = findCachedHistoryGroup(this.orderId);
            if (cached) {
                this.group = cached;
                this.error = '';
                if (this.redirectUnpaidCod(cached)) return;
            }
            await this.load({ silent: Boolean(cached) });
        },

        redirectUnpaidCod(group) {
            if (!group?.isAwaitingCod) return false;
            const href = group.paymentUrl || `/pembayaran/${encodeURIComponent(orderInvoiceRoot(String(group.groupId || this.orderId)))}`;
            if (this.$el?.closest?.('.evomi-history-detail-modal')) {
                window.__evomiNav?.closeHistoryDetailModal?.();
            }
            if (typeof window.softNavigate === 'function') {
                window.softNavigate(href);
            } else {
                window.location.assign(href);
            }
            return true;
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
            if (!silent && !this.group) this.loading = true;
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
                    groups.find((g) => orderInvoiceRoot(String(g.groupId)) === orderInvoiceRoot(String(this.orderId))) ||
                    null;
                if (found) {
                    this.group = found;
                    this.error = '';
                    if (this.redirectUnpaidCod(found)) return;
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
                            if (Alpine.store('evomiHistoryDetailModal')?.open) {
                                window.__evomiNav?.closeHistoryDetailModal?.({ backToList: true });
                            } else {
                                softNavigate('/profile/history');
                            }
                        }, 600);
                        return;
                    }
                    await this.load({ silent: true });
                    this.showToast('Item pesanan telah dihapus.');
                },
            });
        },
    }));

    Alpine.data('evomiProfileChat', (mountId = 'evomi-chat-page-turnstile') => ({
        loading: true,
        refreshing: false,
        sending: false,
        draft: '',
        sendError: '',
        ...chatCaptcha(mountId),
        messages: [],
        showJumpLatest: false,
        hints: [
            storefrontL('Cek status pesanan saya', 'Check my order status'),
            storefrontL('Rekomendasi aroma untuk saya', 'Scent recommendations for me'),
            storefrontL('Info pengiriman & ongkir', 'Shipping & delivery info'),
        ],
        _poll: null,

        async init() {
            this._onReload = () => {
                if (getAuthToken()) this.load();
            };
            window.addEventListener('evomi-chat-reload', this._onReload);

            const inModal = !!this.$el?.closest?.('.evomi-chat-modal');
            if (inModal) {
                this.loading = false;
                this._unwatchModal = this.$watch?.(() => Alpine.store('evomiChatModal')?.open, (open) => {
                    if (open) {
                        this.mountChatCaptcha();
                        this.load();
                        if (!this._poll) {
                            this._poll = window.setInterval(() => {
                                if (Alpine.store('evomiChatModal')?.open) this.load(true);
                            }, 30000);
                        }
                    } else if (this._poll) {
                        window.clearInterval(this._poll);
                        this._poll = null;
                    }
                });
                if (Alpine.store('evomiChatModal')?.open && getAuthToken()) {
                    await this.mountChatCaptcha();
                    await this.load();
                    this._poll = window.setInterval(() => {
                        if (Alpine.store('evomiChatModal')?.open) this.load(true);
                    }, 30000);
                }
                return;
            }

            if (!getAuthToken()) {
                this.loading = false;
                return;
            }
            await this.mountChatCaptcha();
            await this.load();
            this._poll = window.setInterval(() => this.load(true), 30000);
        },

        destroy() {
            if (this._poll) window.clearInterval(this._poll);
            if (this._onReload) window.removeEventListener('evomi-chat-reload', this._onReload);
            if (typeof this._unwatchModal === 'function') this._unwatchModal();
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
            if (this.dayKey(iso) === this.dayKey(today.toISOString())) {
                return storefrontL('Hari ini', 'Today');
            }
            if (this.dayKey(iso) === this.dayKey(yday.toISOString())) {
                return storefrontL('Kemarin', 'Yesterday');
            }
            const locale = document.documentElement.lang === 'en' ? 'en-US' : 'id-ID';
            return d.toLocaleDateString(locale, {
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
                softNavigate('/login');
                return;
            }
            if (!silent) this.loading = true;
            else this.refreshing = true;
            try {
                this.messages = await fetchContactThread(user.email);

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

            const captchaToken = this.chatCaptchaToken();
            if (captchaToken === null) {
                this.sendError = turnstileRequiredMessage();
                return;
            }

            this.sending = true;
            this.sendError = '';
            const pendingId = `pending-${Date.now()}`;
            this.messages.push({
                id: pendingId,
                type: 'user',
                text,
                createdAt: new Date().toISOString(),
                subject: storefrontL('Pesan Dukungan Pelanggan', 'Customer Support Message'),
                isReadByAdmin: false,
                isNew: false,
                pending: true,
                timeLabel: new Date().toLocaleString(
                    document.documentElement.lang === 'en' ? 'en-US' : 'id-ID',
                    { hour: '2-digit', minute: '2-digit' },
                ),
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
                        subject: storefrontL('Pesan Dukungan Pelanggan', 'Customer Support Message'),
                        message: text,
                        _hp: '',
                        captcha_token: captchaToken,
                    }),
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    if (data?.captcha_required) await this.askChatCaptchaAgain();
                    throw new Error(apiErrorMessage(data, storefrontL('Gagal mengirim pesan.', 'Failed to send message.')));
                }
                this.markChatCaptchaPassed();
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
        showKurirList: false,
        paymentMethod: 'cod',
        paymentSettings: null,
        qrisAvailable: false,
        qrisDesc: 'Bayar dengan QRIS',
        qrisModal: { open: false },
        qrisData: null,
        _qrisPollTimer: null,
        bankTransferAvailable: false,
        bankTransferDesc: 'Transfer via Virtual Account',
        selectedBank: 'bca',
        vaBanks: [
            { id: 'bca', label: 'BCA' },
            { id: 'bni', label: 'BNI' },
            { id: 'bri', label: 'BRI' },
            { id: 'mandiri', label: 'Mandiri' },
            { id: 'permata', label: 'Permata' },
        ],
        vaModal: { open: false },
        vaData: null,
        vaCopied: false,
        _vaPollTimer: null,
        _vaCopyTimer: null,
        codNotice: { open: false },
        checkoutPromo: null,
        // Shipping quote context (manual tarif per kota & berat)
        unitWeightGrams: 60,
        shippingCity: null,
        shippingWeightGrams: 0,
        shippingOriginCity: 'Cisauk',
        shippingOptionsError: '',
        freeShipping: false,
        _preferredKurirId: null,
        orderNote: '',
        editingAddress: false,
        savingAddress: false,
        shippingCities: [
            'Jakarta',
            'Bogor',
            'Depok',
            'Tangerang',
            'Bekasi',
            'Bandung',
            'Surabaya',
            'Yogyakarta',
            'Semarang',
            'Medan',
            'Makassar',
        ],
        form: { name: '', email: '', phone: '', address: '', city: '' },
        draft: { name: '', email: '', phone: '', address: '', city: '' },
        modal: { open: false, type: 'success', title: '', message: '' },
        completedOrderId: '',

        get qrisImageUrl() {
            const raw = this.qrisData?.qr_string;
            if (!raw) return '';
            return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(raw)}`;
        },

        get hasAddress() {
            const city = String(this.form.city || '').trim() || this.detectShippingCity(this.form.address);
            return Boolean(
                this.form.name?.trim() &&
                    this.form.email?.trim() &&
                    this.form.phone?.trim() &&
                    this.form.address?.trim() &&
                    city,
            );
        },

        applyAddress(next, { openEditorIfIncomplete = true } = {}) {
            const normalized = {
                name: String(next?.name || '').trim(),
                email: String(next?.email || '').trim(),
                phone: String(next?.phone || '').trim(),
                address: String(next?.address || '').trim(),
                city: String(next?.city || '').trim(),
            };
            this.form = { ...normalized };
            this.draft = { ...normalized };
            if (openEditorIfIncomplete) {
                this.editingAddress = !this.hasAddress;
            }
        },

        addressFromUser(user) {
            if (!user || typeof user !== 'object') {
                return { name: '', email: '', phone: '', address: '', city: '' };
            }
            const address = String(
                user.alamat_lengkap || user.alamat || user.address || '',
            ).trim();
            const explicitCity = String(user.kota || user.city || user.shipping_city || '').trim();
            return {
                name: String(user.name || user.nama_lengkap || user.nama || '').trim(),
                email: String(user.email || '').trim(),
                phone: String(user.phone || user.no_hp || '').trim(),
                address,
                city: explicitCity || this.detectShippingCity(address) || '',
            };
        },

        resolveShippingCity() {
            const explicit = String(this.form.city || '').trim();
            if (explicit) return explicit;
            return this.detectShippingCity(this.form.address);
        },

        detectShippingCity(address) {
            const s = String(address || '').toLowerCase();
            if (!s) return null;

            const rules = [
                ['Jakarta', ['jakarta', 'jakbar', 'jaksel', 'jakpus', 'jaktim', 'jakut']],
                ['Bogor', ['bogor']],
                ['Depok', ['depok']],
                ['Tangerang', ['tangerang', 'tangerang selatan', 'tangerang sel', 'cisauk', 'serpong']],
                ['Bekasi', ['bekasi']],
                ['Bandung', ['bandung']],
                ['Surabaya', ['surabaya']],
                ['Yogyakarta', ['yogyakarta', 'jogja']],
                ['Semarang', ['semarang']],
                ['Medan', ['medan']],
                ['Makassar', ['makassar']],
            ];

            for (const [city, needles] of rules) {
                for (const n of needles) {
                    if (n && s.includes(n)) return city;
                }
            }

            return null;
        },

        computeShippingWeightGrams() {
            const unit = Math.max(0, Number(this.unitWeightGrams) || 60);
            const raw = this.items.reduce(
                (sum, i) => sum + Math.max(0, Number(i.quantity || 0)) * unit,
                0,
            );
            return Math.max(0, raw);
        },

        async refreshShippingOptions(preferredId = null) {
            this.shippingOptionsError = '';
            this.shippingCity = null;
            this.shippingWeightGrams = 0;
            this.kurirs = [];
            const currentPreferredId = preferredId ?? this.selectedKurir?.id ?? null;
            this.selectedKurir = null;

            if (this.freeShipping) return;

            if (!this.hasAddress) return;

            const city = this.resolveShippingCity();
            const weight = this.computeShippingWeightGrams();

            this.shippingCity = city;
            this.shippingWeightGrams = weight;

            if (!city) {
                this.shippingOptionsError = storefrontL(
                    'Pilih kota tujuan atau tuliskan nama kota di alamat lengkap.',
                    'Select a destination city or include the city name in your full address.',
                );
                return;
            }
            if (!Number.isFinite(weight) || weight <= 0) return;

            try {
                const origin = encodeURIComponent(this.shippingOriginCity || 'Cisauk');
                const res = await fetch(
                    `/api/kurirs/quote?origin_city=${origin}&city=${encodeURIComponent(city)}&weight_grams=${encodeURIComponent(
                        weight,
                    )}`,
                    { headers: { Accept: 'application/json' } },
                );
                const data = await readApiJson(res);
                const list = Array.isArray(data) ? data : data.data || [];
                this.kurirs = list;
                if (!this.kurirs.length) {
                    this.shippingOptionsError = storefrontL(
                        'Ongkir untuk kota & berat ini belum tersedia.',
                        'Shipping for this city & weight is not available.',
                    );
                    return;
                }

                const preferred = currentPreferredId
                    ? this.kurirs.find((k) => String(k.id) === String(currentPreferredId))
                    : null;
                this.selectedKurir = preferred || this.kurirs[0];
            } catch {
                this.shippingOptionsError = storefrontL(
                    'Gagal memuat ongkir. Coba lagi nanti.',
                    'Failed to load shipping. Please try again.',
                );
            }
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
            return Math.max(
                this.items.reduce((sum, i) => sum + Number(i.price) * Number(i.quantity), 0),
                0,
            );
        },

        get promoDiscount() {
            if (this.itemCount < 1) return 0;
            return checkoutPromoDiscount(this.productSubtotal, this.checkoutPromo);
        },

        get shippingCost() {
            if (this.freeShipping) return 0;
            return Number(this.selectedKurir?.customer_harga ?? this.selectedKurir?.harga ?? 0);
        },

        get shippingAdminSubsidy() {
            return Math.max(0, Number(this.selectedKurir?.admin_subsidy ?? 0));
        },

        get total() {
            return Math.max(this.productSubtotal + this.shippingCost - this.promoDiscount, 0);
        },

        get courierLabel() {
            if (!this.selectedKurir) return '';
            return `${this.selectedKurir.nama || ''}${this.selectedKurir.jenis ? ' ' + this.selectedKurir.jenis : ''}`.trim();
        },

        get shippingEtaLabel() {
            if (!this.selectedKurir) {
                return this.shippingOptionsError
                    || storefrontL('Isi alamat & kota tujuan untuk melihat ongkir.', 'Enter address and destination city to see shipping.');
            }
            const days = Number(this.selectedKurir?.estimasi_hari || 3);
            const date = new Date();
            date.setDate(date.getDate() + (Number.isFinite(days) && days > 0 ? days : 3));
            return `Estimasi tiba ${date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}`;
        },

        get shippingWeightLabel() {
            const grams = Number(this.shippingWeightGrams) || this.computeShippingWeightGrams();
            if (!grams) return '';
            return storefrontL(
                `Berat paket ± ${grams} g (${this.itemCount} item × ${this.unitWeightGrams} g)`,
                `Package weight ± ${grams} g (${this.itemCount} item × ${this.unitWeightGrams} g)`,
            );
        },

        formatPrice(value) {
            return formatRupiah(value);
        },

        itemUnitPrice(item) {
            return Number(item.price) || 0;
        },

        resolveCheckoutParams() {
            let params = new URLSearchParams(window.location.search);
            const hasIntent =
                params.get('type') === 'cart' ||
                Boolean(params.get('productId') || params.get('product_id'));
            if (!hasIntent) {
                try {
                    const cached = sessionStorage.getItem('evomi_checkout_qs');
                    if (cached) {
                        params = new URLSearchParams(cached);
                        const next = `${window.location.pathname}?${params.toString()}`;
                        history.replaceState({ soft: true }, document.title, next);
                    }
                } catch {
                    /* ignore */
                }
            }
            return params;
        },

        destroy() {
            this.stopQrisPolling();
            this.stopVaPolling();
            if (this._vaCopyTimer) {
                window.clearTimeout(this._vaCopyTimer);
                this._vaCopyTimer = null;
            }
        },

        async boot() {
            const params = this.resolveCheckoutParams();
            this.type = (params.get('type') || 'buynow').toLowerCase();
            this._preferredKurirId = params.get('kurirId') || null;
            this.shippingOriginCity =
                String(this.$el?.dataset?.shippingOrigin || '').trim() || this.shippingOriginCity;
            this.freeShipping = this.$el?.dataset?.freeShipping === '1';

            try {
                await Promise.all([
                    this.loadItems(params),
                    this.loadPaymentSettings(),
                    this.loadCheckoutPromo(),
                    this.loadShippingSettings(),
                ]);
                await this.prefillProfile();
                try {
                    sessionStorage.removeItem('evomi_checkout_qs');
                } catch {
                    /* ignore */
                }

                if (this.hasAddress) {
                    await this.refreshShippingOptions(this._preferredKurirId);
                }
            } catch (err) {
                this.fatalError = err instanceof Error ? err.message : storefrontL('Gagal memuat checkout.', 'Failed to load checkout.');
            } finally {
                this.loading = false;
                applyProductTheme(this.brand);
            }
        },

        async loadCheckoutPromo() {
            try {
                const res = await fetch('/api/promos?active=1', {
                    headers: { Accept: 'application/json' },
                });
                const data = await readApiJson(res);
                const list = Array.isArray(data) ? data : data.data || [];
                this.checkoutPromo = list[0] || null;
            } catch {
                this.checkoutPromo = null;
            }
        },

        async loadPaymentSettings() {
            try {
                const res = await fetch('/api/payment-settings', {
                    headers: { Accept: 'application/json' },
                });
                const data = await readApiJson(res);
                const settings = data?.data || data || {};
                this.paymentSettings = settings;
                const provider = String(settings.provider || 'manual').toLowerCase();
                const configured = Boolean(settings.configured);
                this.qrisAvailable =
                    (provider === 'midtrans' || provider === 'xendit') && configured;
                this.bankTransferAvailable = this.qrisAvailable;

                if (provider === 'xendit') {
                    this.qrisDesc = storefrontL(
                        'Bayar dengan QRIS melalui Xendit',
                        'Pay with QRIS via Xendit',
                    );
                    this.bankTransferDesc = storefrontL(
                        'Virtual Account melalui Xendit',
                        'Virtual Account via Xendit',
                    );
                } else if (provider === 'midtrans') {
                    this.qrisDesc = storefrontL(
                        'Bayar dengan QRIS melalui Midtrans',
                        'Pay with QRIS via Midtrans',
                    );
                    this.bankTransferDesc = storefrontL(
                        'Virtual Account melalui Midtrans',
                        'Virtual Account via Midtrans',
                    );
                } else {
                    this.qrisDesc = storefrontL('Bayar dengan QRIS', 'Pay with QRIS');
                    this.bankTransferDesc = storefrontL(
                        'Transfer via Virtual Account',
                        'Pay via Virtual Account',
                    );
                }

                this.paymentMethod = this.qrisAvailable ? 'qris' : 'cod';
            } catch {
                this.paymentSettings = { provider: 'manual', configured: true };
                this.qrisAvailable = false;
                this.bankTransferAvailable = false;
                this.paymentMethod = 'cod';
            }
        },

        async loadShippingSettings() {
            try {
                const res = await fetch('/api/shipping-settings', {
                    headers: { Accept: 'application/json' },
                });
                const data = await readApiJson(res);
                const settings = data?.data || data || {};
                if (typeof settings.free_shipping !== 'undefined') {
                    this.freeShipping = Boolean(settings.free_shipping);
                }
            } catch {
                /* keep whatever data-attr gave us */
            }
        },

        async loadKurirs(preferredId, { city = null, weightGrams = null } = {}) {
            const cityNorm = city ? String(city).trim() : null;
            const weightNorm = weightGrams !== null ? Number(weightGrams) : null;

            const shouldQuote =
                cityNorm && Number.isFinite(weightNorm) && Number(weightNorm) > 0;

            const url = shouldQuote
                ? `/api/kurirs/quote?city=${encodeURIComponent(cityNorm)}&weight_grams=${encodeURIComponent(
                      weightNorm,
                  )}`
                : '/api/kurirs';

            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await readApiJson(res);
            const list = Array.isArray(data) ? data : data.data || [];
            this.kurirs = list;
            if (!this.kurirs.length) {
                this.selectedKurir = null;
                return;
            }
            const preferred = this.kurirs.find((k) => String(k.id) === String(preferredId));
            this.selectedKurir = preferred || this.kurirs[0];
        },

        async loadItems(params) {
            if (this.type === 'cart') {
                if (!getAuthToken()) {
                    const list = readGuestCart();
                    this.items = list
                        .map((row) => ({
                            id: row.id || `guest-${row.product_id}`,
                            product_id: row.product_id,
                            title: row.title || `Produk #${row.product_id}`,
                            price: Number(row.price) || 0,
                            quantity: Number(row.quantity || 1),
                            stock: Number(row.stock ?? 99),
                            image: row.image || '',
                            personality_type: '',
                        }))
                        .filter((i) => i.product_id);
                    if (!this.items.length) throw new Error(storefrontL('Keranjang kosong.', 'Cart is empty.'));
                    this.brand = DEFAULT_THEME_BLUE;
                    return;
                }
                const res = await fetch('/api/carts?locale=id', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                if (res.status === 401) {
                    clearAuthSession();
                    const list = readGuestCart();
                    this.items = list
                        .map((row) => ({
                            id: row.id || `guest-${row.product_id}`,
                            product_id: row.product_id,
                            title: row.title || `Produk #${row.product_id}`,
                            price: Number(row.price) || 0,
                            quantity: Number(row.quantity || 1),
                            stock: Number(row.stock ?? 99),
                            image: row.image || '',
                            personality_type: '',
                        }))
                        .filter((i) => i.product_id);
                    if (!this.items.length) {
                        window.location.replace('/login');
                        throw new Error(storefrontL('Sesi berakhir.', 'Session expired.'));
                    }
                    this.brand = DEFAULT_THEME_BLUE;
                    return;
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

            const productId = Number(params.get('productId') || params.get('product_id') || 0);
            const qty = Math.max(1, Number(params.get('qty') || 1));
            const unitPrice = Number(params.get('unitPrice') || 0);
            if (!Number.isFinite(productId) || productId <= 0) {
                throw new Error(storefrontL('Produk checkout tidak valid.', 'Invalid checkout product.'));
            }

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
                city: (this.draft.city || '').trim(),
            };

            if (!next.name || !next.email || !next.phone || !next.address || !next.city) {
                this.formError = storefrontL(
                    'Lengkapi nama, email, telepon, alamat, dan kota tujuan.',
                    'Complete name, email, phone, address, and destination city.',
                );
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
            if (this.hasAddress) {
                await this.refreshShippingOptions();
            }

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

            // Ongkir bergantung pada berat total, jadi refresh setelah qty berubah.
            if (this.hasAddress) {
                this.refreshShippingOptions();
            }
        },

        async validateForm() {
            if (this.editingAddress) {
                const saved = await this.saveAddress();
                if (!saved) return false;
            }
            if (!this.hasAddress || !this.form.city) {
                this.formError = storefrontL(
                    'Lengkapi alamat dan kota tujuan sebelum checkout.',
                    'Complete address and destination city before checkout.',
                );
                this.editingAddress = true;
                this.draft = { ...this.form };
                return false;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                this.formError = 'Format email tidak valid.';
                return false;
            }
            if (!this.freeShipping && !this.selectedKurir) {
                this.formError =
                    this.shippingOptionsError || storefrontL('Pilih kurir pengiriman.', 'Select a courier.');
                return false;
            }
            if (!this.items.length) {
                this.formError = storefrontL('Tidak ada item untuk di-checkout.', 'No items to checkout.');
                return false;
            }
            if (
                this.paymentMethod === 'bank_transfer' &&
                this.bankTransferAvailable &&
                !this.selectedBank
            ) {
                this.formError = storefrontL('Pilih bank untuk transfer.', 'Select a bank for transfer.');
                return false;
            }
            this.formError = '';
            return true;
        },

        makeInvoiceId() {
            return makePublicOrderNumber();
        },

        stopQrisPolling() {
            if (this._qrisPollTimer) {
                window.clearInterval(this._qrisPollTimer);
                this._qrisPollTimer = null;
            }
        },

        startQrisPolling() {
            this.stopQrisPolling();
            // Immediate check, then every 3s — same feel as Next.js checkout
            this.checkQrisStatus();
            this._qrisPollTimer = window.setInterval(() => {
                this.checkQrisStatus();
            }, 3000);
        },

        closeQrisModal() {
            this.qrisModal.open = false;
            this.stopQrisPolling();
        },

        stopVaPolling() {
            if (this._vaPollTimer) {
                window.clearInterval(this._vaPollTimer);
                this._vaPollTimer = null;
            }
        },

        startVaPolling() {
            this.stopVaPolling();
            this.checkVaStatus();
            this._vaPollTimer = window.setInterval(() => {
                this.checkVaStatus();
            }, 3000);
        },

        closeVaModal() {
            this.vaModal.open = false;
            this.stopVaPolling();
        },

        selectPayment(method) {
            this.paymentMethod = method;
            this.codNotice.open = method === 'cod';
        },

        closeCodNotice() {
            this.codNotice.open = false;
        },

        async copyVaNumber() {
            const value = String(this.vaData?.va_number || '').trim();
            if (!value) return;
            try {
                await navigator.clipboard.writeText(value.replace(/\s+/g, ''));
                this.vaCopied = true;
                if (this._vaCopyTimer) window.clearTimeout(this._vaCopyTimer);
                this._vaCopyTimer = window.setTimeout(() => {
                    this.vaCopied = false;
                }, 1800);
            } catch {
                /* ignore */
            }
        },

        bankLabel(bankId) {
            const found = this.vaBanks.find((b) => b.id === bankId);
            return found?.label || String(bankId || '').toUpperCase();
        },

        async submitCheckout() {
            if (this.processing) return;
            if (!(await this.validateForm())) return;

            const token = getAuthToken();
            if (this.type === 'cart' && !token && !readGuestCart().length && !this.items.length) {
                window.location.href = '/login';
                return;
            }

            const invoiceId = this.makeInvoiceId();
            const provider = String(this.paymentSettings?.provider || '').toLowerCase();
            const gatewayReady =
                this.qrisAvailable && ['midtrans', 'xendit'].includes(provider);

            if (this.paymentMethod === 'qris' && gatewayReady) {
                await this.startOnlineCheckout(invoiceId, 'qris', provider);
                return;
            }

            if (this.paymentMethod === 'bank_transfer' && gatewayReady) {
                await this.startOnlineCheckout(invoiceId, 'va', provider);
                return;
            }

            await this.processInternalCheckout(invoiceId);
        },

        async createPendingOrder(invoiceId, { paymentLabel, channel, provider }) {
            const token = getAuthToken();
            const isGuestCheckout = !token;
            const payload = {
                invoice_id: invoiceId,
                payment_method: paymentLabel,
                payment_status: 'pending',
                payment_channel: channel,
                payment_provider: provider,
                payment_window_hours: 24,
                total: this.total,
                shipping_cost: this.shippingCost,
                promo_discount: this.promoDiscount,
                recipient_name: this.form.name,
                recipient_phone: this.form.phone,
                recipient_address: this.form.address,
                courier: this.courierLabel,
                kurir_id: this.selectedKurir?.id ?? null,
                shipping_city: this.shippingCity,
                shipping_origin_city: this.shippingOriginCity,
                shipping_weight_grams: this.shippingWeightGrams,
                note: this.orderNote || undefined,
                items: this.items.map((item) => ({
                    product_id: Number(item.product_id),
                    quantity: Number(item.quantity),
                    price: Number(item.price),
                    title: item.title,
                })),
            };

            if (isGuestCheckout) {
                const res = await fetch('/api/checkout/guest', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ...payload,
                        guest_email: this.form.email,
                        _hp: '',
                    }),
                });
                const data = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(
                        apiErrorMessage(data, storefrontL('Checkout gagal.', 'Checkout failed.')),
                    );
                }
                if (this.type === 'cart') writeGuestCart([]);
                return data;
            }

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
            if (!res.ok) {
                throw new Error(
                    apiErrorMessage(data, storefrontL('Checkout gagal.', 'Checkout failed.')),
                );
            }

            try {
                await fetch('/api/trackings', {
                    method: 'POST',
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        order_id: invoiceId,
                        status: storefrontL('Menunggu Pembayaran', 'Awaiting Payment'),
                        courier: this.courierLabel,
                        recipient_name: this.form.name,
                        recipient_phone: this.form.phone,
                        recipient_address: this.form.address,
                    }),
                });
            } catch {
                /* optional */
            }
            emitEvomiEvent('cart_updated');
            return data;
        },

        async attachPaymentIntent(invoiceId, { provider, channel, paymentRef, meta }) {
            const headers = getAuthToken()
                ? authHeaders(true)
                : {
                      Accept: 'application/json',
                      'Content-Type': 'application/json',
                  };
            const res = await fetch(
                `/api/orders/${encodeURIComponent(invoiceId)}/payment-intent`,
                {
                    method: 'POST',
                    headers,
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        provider,
                        channel,
                        payment_ref: paymentRef,
                        meta,
                    }),
                },
            );
            const json = await readApiJson(res);
            if (!res.ok || json?.success === false) {
                throw new Error(
                    apiErrorMessage(
                        json,
                        storefrontL(
                            'Gagal menyimpan detail pembayaran.',
                            'Failed to save payment details.',
                        ),
                    ),
                );
            }
            return json;
        },

        async startOnlineCheckout(invoiceId, channel, provider) {
            this.processing = true;
            this.formError = '';
            const paymentLabel =
                channel === 'qris'
                    ? 'QRIS'
                    : `Bank Transfer · ${this.bankLabel(this.selectedBank)}`;

            try {
                await this.createPendingOrder(invoiceId, {
                    paymentLabel,
                    channel,
                    provider,
                });

                let paymentRef = '';
                let meta = {};

                if (channel === 'qris') {
                    if (provider === 'midtrans') {
                        const first = this.items[0];
                        const res = await fetch('/api/payments/midtrans/qris', {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                order_id: invoiceId,
                                amount: this.total,
                                customer_name: this.form.name,
                                customer_email: this.form.email,
                                customer_phone: this.form.phone,
                                item_name: first?.title
                                    ? `Evomi — ${first.title}`
                                    : 'Pesanan Evomi',
                                item_id: first?.product_id
                                    ? String(first.product_id)
                                    : 'evomi-order',
                            }),
                        });
                        const json = await readApiJson(res);
                        if (!res.ok || json?.success === false) {
                            throw new Error(
                                apiErrorMessage(
                                    json,
                                    storefrontL(
                                        'Gagal membuat QRIS Midtrans.',
                                        'Failed to create Midtrans QRIS.',
                                    ),
                                ),
                            );
                        }
                        const data = json?.data || json;
                        if (!data?.qr_string) {
                            throw new Error(
                                storefrontL(
                                    'Respons QRIS Midtrans tidak lengkap.',
                                    'Incomplete Midtrans QRIS response.',
                                ),
                            );
                        }
                        paymentRef = data.order_id || invoiceId;
                        meta = { qr_string: data.qr_string };
                    } else {
                        const expiresAt = new Date(
                            Date.now() + 24 * 60 * 60 * 1000,
                        ).toISOString();
                        const res = await fetch('/api/payments/xendit/qr', {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                reference_id: invoiceId,
                                amount: this.total,
                                expires_at: expiresAt,
                            }),
                        });
                        const json = await readApiJson(res);
                        if (!res.ok || json?.success === false) {
                            throw new Error(
                                apiErrorMessage(
                                    json,
                                    storefrontL(
                                        'Gagal membuat QRIS Xendit.',
                                        'Failed to create Xendit QRIS.',
                                    ),
                                ),
                            );
                        }
                        const data = json?.data || json;
                        if (!data?.id || !data?.qr_string) {
                            throw new Error(
                                storefrontL(
                                    'Respons QRIS Xendit tidak lengkap.',
                                    'Incomplete Xendit QRIS response.',
                                ),
                            );
                        }
                        paymentRef = data.id;
                        meta = { qr_string: data.qr_string };
                    }
                } else {
                    const bank = String(this.selectedBank || 'bca').toLowerCase();
                    if (provider === 'midtrans') {
                        const first = this.items[0];
                        const res = await fetch('/api/payments/midtrans/va', {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                order_id: invoiceId,
                                amount: this.total,
                                bank,
                                customer_name: this.form.name,
                                customer_email: this.form.email,
                                customer_phone: this.form.phone,
                                item_name: first?.title
                                    ? `Evomi — ${first.title}`
                                    : 'Pesanan Evomi',
                                item_id: first?.product_id
                                    ? String(first.product_id)
                                    : 'evomi-order',
                            }),
                        });
                        const json = await readApiJson(res);
                        if (!res.ok || json?.success === false) {
                            throw new Error(
                                apiErrorMessage(
                                    json,
                                    storefrontL(
                                        'Gagal membuat Virtual Account Midtrans.',
                                        'Failed to create Midtrans Virtual Account.',
                                    ),
                                ),
                            );
                        }
                        const data = json?.data || json;
                        if (!data?.va_number) {
                            throw new Error(
                                storefrontL(
                                    'Respons Virtual Account Midtrans tidak lengkap.',
                                    'Incomplete Midtrans Virtual Account response.',
                                ),
                            );
                        }
                        paymentRef = data.order_id || invoiceId;
                        meta = {
                            va_number: data.va_number,
                            bank: data.bank || bank,
                            biller_code: data.biller_code || null,
                            bill_key: data.bill_key || null,
                        };
                    } else {
                        const expiresAt = new Date(
                            Date.now() + 24 * 60 * 60 * 1000,
                        ).toISOString();
                        const res = await fetch('/api/payments/xendit/va', {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                external_id: invoiceId,
                                amount: this.total,
                                bank,
                                customer_name: this.form.name,
                                expires_at: expiresAt,
                            }),
                        });
                        const json = await readApiJson(res);
                        if (!res.ok || json?.success === false) {
                            throw new Error(
                                apiErrorMessage(
                                    json,
                                    storefrontL(
                                        'Gagal membuat Virtual Account Xendit.',
                                        'Failed to create Xendit Virtual Account.',
                                    ),
                                ),
                            );
                        }
                        const data = json?.data || json;
                        if (!data?.id || !data?.va_number) {
                            throw new Error(
                                storefrontL(
                                    'Respons Virtual Account Xendit tidak lengkap.',
                                    'Incomplete Xendit Virtual Account response.',
                                ),
                            );
                        }
                        paymentRef = data.id;
                        meta = {
                            va_number: data.va_number,
                            bank: data.bank || bank,
                        };
                    }
                }

                await this.attachPaymentIntent(invoiceId, {
                    provider,
                    channel,
                    paymentRef,
                    meta,
                });

                emitEvomiEvent('history_updated');
                const payUrl = `/pembayaran/${encodeURIComponent(invoiceId)}`;
                if (typeof window.softNavigate === 'function') {
                    window.softNavigate(payUrl);
                } else {
                    window.location.href = payUrl;
                }
            } catch (err) {
                this.modal = {
                    open: true,
                    type: 'error',
                    title: storefrontL('Checkout Gagal', 'Checkout Failed'),
                    message:
                        err instanceof Error
                            ? err.message
                            : storefrontL(
                                  'Gagal memulai pembayaran',
                                  'Failed to start payment',
                              ),
                };
            } finally {
                this.processing = false;
            }
        },

        async startQrisPayment(invoiceId) {
            this.processing = true;
            this.formError = '';
            const provider = String(this.paymentSettings?.provider || '').toLowerCase();

            try {
                if (provider === 'midtrans') {
                    const first = this.items[0];
                    const res = await fetch('/api/payments/midtrans/qris', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            order_id: invoiceId,
                            amount: this.total,
                            customer_name: this.form.name,
                            customer_email: this.form.email,
                            customer_phone: this.form.phone,
                            item_name: first?.title
                                ? `Evomi — ${first.title}`
                                : 'Pesanan Evomi',
                            item_id: first?.product_id
                                ? String(first.product_id)
                                : 'evomi-order',
                        }),
                    });
                    const json = await readApiJson(res);
                    if (!res.ok || json?.success === false) {
                        throw new Error(
                            apiErrorMessage(
                                json,
                                storefrontL(
                                    'Gagal membuat QRIS Midtrans.',
                                    'Failed to create Midtrans QRIS.',
                                ),
                            ),
                        );
                    }
                    const data = json?.data || json;
                    if (!data?.qr_string) {
                        throw new Error(
                            storefrontL(
                                'Respons QRIS Midtrans tidak lengkap.',
                                'Incomplete Midtrans QRIS response.',
                            ),
                        );
                    }
                    this.qrisData = {
                        id: data.order_id || invoiceId,
                        qr_string: data.qr_string,
                        invoice_id: invoiceId,
                        provider: 'midtrans',
                    };
                } else if (provider === 'xendit') {
                    const expiresAt = new Date(Date.now() + 15 * 60000).toISOString();
                    const res = await fetch('/api/payments/xendit/qr', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            reference_id: invoiceId,
                            amount: this.total,
                            expires_at: expiresAt,
                        }),
                    });
                    const json = await readApiJson(res);
                    if (!res.ok || json?.success === false) {
                        throw new Error(
                            apiErrorMessage(
                                json,
                                storefrontL(
                                    'Gagal membuat QRIS Xendit.',
                                    'Failed to create Xendit QRIS.',
                                ),
                            ),
                        );
                    }
                    const data = json?.data || json;
                    if (!data?.id || !data?.qr_string) {
                        throw new Error(
                            storefrontL(
                                'Respons QRIS Xendit tidak lengkap.',
                                'Incomplete Xendit QRIS response.',
                            ),
                        );
                    }
                    this.qrisData = {
                        id: data.id,
                        qr_string: data.qr_string,
                        invoice_id: invoiceId,
                        provider: 'xendit',
                    };
                } else {
                    throw new Error(
                        storefrontL(
                            'Provider pembayaran QRIS belum dikonfigurasi.',
                            'QRIS payment provider is not configured.',
                        ),
                    );
                }

                this.qrisModal.open = true;
                this.startQrisPolling();
            } catch (err) {
                this.modal = {
                    open: true,
                    type: 'error',
                    title: storefrontL('Checkout Gagal', 'Checkout Failed'),
                    message:
                        err instanceof Error
                            ? err.message
                            : storefrontL(
                                  'Gagal menggenerate QRIS dari sistem',
                                  'Failed to generate QRIS from the system',
                              ),
                };
            } finally {
                this.processing = false;
            }
        },

        async startBankTransferPayment(invoiceId) {
            this.processing = true;
            this.formError = '';
            this.vaCopied = false;
            const provider = String(this.paymentSettings?.provider || '').toLowerCase();
            const bank = String(this.selectedBank || 'bca').toLowerCase();

            try {
                if (provider === 'midtrans') {
                    const first = this.items[0];
                    const res = await fetch('/api/payments/midtrans/va', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            order_id: invoiceId,
                            amount: this.total,
                            bank,
                            customer_name: this.form.name,
                            customer_email: this.form.email,
                            customer_phone: this.form.phone,
                            item_name: first?.title
                                ? `Evomi — ${first.title}`
                                : 'Pesanan Evomi',
                            item_id: first?.product_id
                                ? String(first.product_id)
                                : 'evomi-order',
                        }),
                    });
                    const json = await readApiJson(res);
                    if (!res.ok || json?.success === false) {
                        throw new Error(
                            apiErrorMessage(
                                json,
                                storefrontL(
                                    'Gagal membuat Virtual Account Midtrans.',
                                    'Failed to create Midtrans Virtual Account.',
                                ),
                            ),
                        );
                    }
                    const data = json?.data || json;
                    if (!data?.va_number) {
                        throw new Error(
                            storefrontL(
                                'Respons Virtual Account Midtrans tidak lengkap.',
                                'Incomplete Midtrans Virtual Account response.',
                            ),
                        );
                    }
                    this.vaData = {
                        id: data.order_id || invoiceId,
                        va_number: data.va_number,
                        bank: data.bank || bank,
                        biller_code: data.biller_code || null,
                        bill_key: data.bill_key || null,
                        invoice_id: invoiceId,
                        provider: 'midtrans',
                    };
                } else if (provider === 'xendit') {
                    const expiresAt = new Date(Date.now() + 24 * 60 * 60000).toISOString();
                    const res = await fetch('/api/payments/xendit/va', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            external_id: invoiceId,
                            amount: this.total,
                            bank,
                            customer_name: this.form.name,
                            expires_at: expiresAt,
                        }),
                    });
                    const json = await readApiJson(res);
                    if (!res.ok || json?.success === false) {
                        throw new Error(
                            apiErrorMessage(
                                json,
                                storefrontL(
                                    'Gagal membuat Virtual Account Xendit.',
                                    'Failed to create Xendit Virtual Account.',
                                ),
                            ),
                        );
                    }
                    const data = json?.data || json;
                    if (!data?.id || !data?.va_number) {
                        throw new Error(
                            storefrontL(
                                'Respons Virtual Account Xendit tidak lengkap.',
                                'Incomplete Xendit Virtual Account response.',
                            ),
                        );
                    }
                    this.vaData = {
                        id: data.id,
                        va_number: data.va_number,
                        bank: data.bank || bank,
                        invoice_id: invoiceId,
                        provider: 'xendit',
                    };
                } else {
                    throw new Error(
                        storefrontL(
                            'Provider transfer bank belum dikonfigurasi.',
                            'Bank transfer provider is not configured.',
                        ),
                    );
                }

                this.vaModal.open = true;
                this.startVaPolling();
            } catch (err) {
                this.modal = {
                    open: true,
                    type: 'error',
                    title: storefrontL('Checkout Gagal', 'Checkout Failed'),
                    message:
                        err instanceof Error
                            ? err.message
                            : storefrontL(
                                  'Gagal membuat Virtual Account dari sistem',
                                  'Failed to create Virtual Account from the system',
                              ),
                };
            } finally {
                this.processing = false;
            }
        },

        async checkQrisStatus() {
            if (!this.qrisData || !this.qrisModal.open || this.processing) return;

            try {
                if (this.qrisData.provider === 'midtrans') {
                    const res = await fetch(
                        `/api/payments/midtrans/qris/${encodeURIComponent(this.qrisData.id)}`,
                        { headers: { Accept: 'application/json' } },
                    );
                    const json = await readApiJson(res);
                    if (!res.ok || json?.success === false) return;
                    const data = json?.data || json;
                    const status = String(data?.status || '').toLowerCase();
                    const paid =
                        status === 'settlement' ||
                        status === 'capture' ||
                        status === 'success';
                    if (!paid) return;

                    this.stopQrisPolling();
                    this.qrisModal.open = false;
                    await this.processInternalCheckout(this.qrisData.invoice_id);
                    return;
                }

                const res = await fetch(
                    `/api/payments/xendit/qr/${encodeURIComponent(this.qrisData.id)}`,
                    { headers: { Accept: 'application/json' } },
                );
                const json = await readApiJson(res);
                if (!res.ok || json?.success === false) return;
                const data = json?.data || json;
                const status = String(data?.status || '').toUpperCase();
                const paid =
                    status === 'INACTIVE' ||
                    status === 'COMPLETED' ||
                    status === 'SUCCEEDED';
                if (!paid) return;

                this.stopQrisPolling();
                this.qrisModal.open = false;
                await this.processInternalCheckout(this.qrisData.invoice_id);
            } catch (err) {
                console.error('Gagal mengecek status QRIS:', err);
            }
        },

        async checkVaStatus() {
            if (!this.vaData || !this.vaModal.open || this.processing) return;

            try {
                if (this.vaData.provider === 'midtrans') {
                    const res = await fetch(
                        `/api/payments/midtrans/va/${encodeURIComponent(this.vaData.id)}`,
                        { headers: { Accept: 'application/json' } },
                    );
                    const json = await readApiJson(res);
                    if (!res.ok || json?.success === false) return;
                    const data = json?.data || json;
                    const status = String(data?.status || '').toLowerCase();
                    const paid =
                        status === 'settlement' ||
                        status === 'capture' ||
                        status === 'success';
                    if (!paid) return;

                    this.stopVaPolling();
                    this.vaModal.open = false;
                    await this.processInternalCheckout(this.vaData.invoice_id);
                    return;
                }

                const res = await fetch(
                    `/api/payments/xendit/va/${encodeURIComponent(this.vaData.id)}`,
                    { headers: { Accept: 'application/json' } },
                );
                const json = await readApiJson(res);
                if (!res.ok || json?.success === false) return;
                const data = json?.data || json;
                const status = String(data?.status || '').toUpperCase();
                // Closed single-use VA becomes INACTIVE after successful payment.
                const paid = status === 'INACTIVE';
                if (!paid) return;

                this.stopVaPolling();
                this.vaModal.open = false;
                await this.processInternalCheckout(this.vaData.invoice_id);
            } catch (err) {
                console.error('Gagal mengecek status VA:', err);
            }
        },

        async processInternalCheckout(invoiceId) {
            if (this.processing) return;

            const token = getAuthToken();
            const isGuestCheckout = !token;

            this.processing = true;
            let paymentLabel = 'Cash on Delivery';
            let paymentStatus = 'pending';
            let paymentChannel = 'cod';
            if (this.paymentMethod === 'qris') {
                paymentLabel = 'QRIS';
                paymentStatus = 'success';
                paymentChannel = 'qris';
            } else if (this.paymentMethod === 'bank_transfer') {
                paymentLabel = `Bank Transfer · ${this.bankLabel(this.vaData?.bank || this.selectedBank)}`;
                paymentStatus = 'success';
                paymentChannel = 'va';
            }

            const payload = {
                invoice_id: invoiceId,
                payment_method: paymentLabel,
                payment_status: paymentStatus,
                payment_channel: paymentChannel,
                total: this.total,
                shipping_cost: this.shippingCost,
                promo_discount: this.promoDiscount,
                recipient_name: this.form.name,
                recipient_phone: this.form.phone,
                recipient_address: this.form.address,
                courier: this.courierLabel,
                kurir_id: this.selectedKurir?.id ?? null,
                shipping_city: this.shippingCity,
                shipping_origin_city: this.shippingOriginCity,
                shipping_weight_grams: this.shippingWeightGrams,
                note: this.orderNote || undefined,
                items: this.items.map((item) => ({
                    product_id: Number(item.product_id),
                    quantity: Number(item.quantity),
                    price: Number(item.price),
                    title: item.title,
                })),
            };

            try {
                if (isGuestCheckout) {
                    const res = await fetch('/api/checkout/guest', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            ...payload,
                            guest_email: this.form.email,
                            _hp: '',
                        }),
                    });
                    const data = await readApiJson(res);
                    if (!res.ok) {
                        throw new Error(
                            apiErrorMessage(
                                data,
                                storefrontL('Checkout gagal.', 'Checkout failed.'),
                            ),
                        );
                    }
                    if (this.type === 'cart') writeGuestCart([]);
                } else {
                    const res = await fetch('/api/checkout', {
                        method: 'POST',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            ...payload,
                            guest_email: this.form.email,
                            _hp: '',
                        }),
                    });
                    const data = await readApiJson(res);
                    if (!res.ok) {
                        throw new Error(
                            apiErrorMessage(
                                data,
                                storefrontL('Checkout gagal.', 'Checkout failed.'),
                            ),
                        );
                    }

                    try {
                        await fetch('/api/trackings', {
                            method: 'POST',
                            headers: authHeaders(true),
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                order_id: invoiceId,
                                status: storefrontL(
                                    'Menunggu Konfirmasi',
                                    'Awaiting Confirmation',
                                ),
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

                emitEvomiEvent('history_updated');
                if (paymentChannel === 'cod') {
                    const payUrl = `/pembayaran/${encodeURIComponent(invoiceId)}`;
                    if (typeof window.softNavigate === 'function') {
                        window.softNavigate(payUrl);
                    } else {
                        window.location.href = payUrl;
                    }
                    return;
                }
                this.completedOrderId = invoiceId;
                this.modal = {
                    open: true,
                    type: 'success',
                    title: storefrontL('Checkout Berhasil!', 'Checkout Successful!'),
                    message: storefrontL(
                        `Pesanan ${invoiceId} sudah dibuat. Notifikasi email telah dikirim. Kamu akan kembali ke beranda.`,
                        `Order ${invoiceId} has been created. An email notification was sent. You will return to home.`,
                    ),
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
                if (typeof window.softNavigate === 'function') {
                    window.softNavigate('/');
                } else {
                    window.location.href = '/';
                }
            }
        },
    }));

    Alpine.data('evomiPaymentPage', (invoiceId = '') => ({
        invoiceId: String(invoiceId || ''),
        loading: true,
        error: '',
        data: null,
        brand: '#1172BA',
        brandDark: '#0B4F86',
        copied: false,
        cancelModal: { open: false, busy: false },
        _pollTimer: null,
        _tickTimer: null,
        secondsLeft: 0,

        get qrisImageUrl() {
            const raw = this.data?.meta?.qr_string;
            if (!raw) return '';
            return `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(raw)}`;
        },

        get isCod() {
            if (this.data?.is_cod === true || this.data?.is_awaiting_cod === true) return true;
            const ch = String(this.data?.payment_channel || '').toLowerCase();
            return ch === 'cod';
        },

        get isAwaiting() {
            if (this.data?.payment_status !== 'pending') return false;
            return Boolean(this.data?.is_awaiting_payment) || Boolean(this.data?.is_awaiting_cod) || this.isCod;
        },

        get isPaid() {
            return this.data?.payment_status === 'success';
        },

        get isExpired() {
            return (
                this.data?.payment_status === 'cancelled' ||
                this.data?.sync_status === 'expired'
            );
        },

        get statusTitle() {
            if (this.isPaid) return storefrontL('Pembayaran berhasil', 'Payment successful');
            if (this.isExpired) return storefrontL('Pembayaran kedaluwarsa', 'Payment expired');
            if (this.isCod) return storefrontL('Bayar saat barang tiba', 'Pay on delivery');
            return storefrontL('Menunggu pembayaran', 'Awaiting payment');
        },

        get statusSubtitle() {
            if (this.isPaid) {
                return storefrontL(
                    'Pesananmu sudah kami terima dan sedang diproses.',
                    'Your order has been received and is being processed.',
                );
            }
            if (this.isExpired) {
                return storefrontL(
                    'Lewat 24 jam tanpa pembayaran — pesanan dibatalkan.',
                    'No payment within 24 hours — order cancelled.',
                );
            }
            if (this.isCod) {
                return storefrontL(
                    'Cash on Delivery: bayar saat barang tiba. Bisa dibatalkan sebelum dikirim.',
                    'Cash on Delivery: pay when the goods arrive. You can cancel before we ship.',
                );
            }
            return storefrontL(
                'Selesaikan pembayaran dalam 24 jam agar pesanan tidak dibatalkan.',
                'Complete payment within 24 hours so the order is not cancelled.',
            );
        },

        get badgeLabel() {
            if (this.isPaid) return storefrontL('Sudah dibayar', 'Paid');
            if (this.isExpired) return storefrontL('Dibatalkan', 'Cancelled');
            return storefrontL('Belum dibayar', 'Unpaid');
        },

        get badgeClass() {
            if (this.isPaid) return 'bg-emerald-50 text-emerald-700';
            if (this.isExpired) return 'bg-rose-50 text-rose-700';
            return 'bg-amber-50 text-amber-800';
        },

        get countdownLabel() {
            const s = Math.max(0, this.secondsLeft);
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = s % 60;
            return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
        },

        get deadlineLabel() {
            const raw = this.data?.payment_expires_at;
            if (!raw) return '';
            try {
                const d = new Date(raw);
                return d.toLocaleString('id-ID', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            } catch {
                return '';
            }
        },

        get channelLabel() {
            const ch = String(this.data?.payment_channel || '').toLowerCase();
            if (ch === 'qris') return 'QRIS';
            if (ch === 'va') return storefrontL('Transfer Bank (VA)', 'Bank Transfer (VA)');
            if (ch === 'cod' || this.isCod) return 'Cash on Delivery';
            return this.data?.payment_method || '';
        },

        get hasPaymentDetails() {
            if (this.isCod) return true;
            const meta = this.data?.meta || {};
            if (this.data?.payment_channel === 'qris') return Boolean(meta.qr_string);
            if (this.data?.payment_channel === 'va') return Boolean(meta.va_number);
            return false;
        },

        formatPrice(value) {
            return formatRupiah(value);
        },

        itemImage(item) {
            const raw = item?.image;
            if (!raw) return '';
            if (String(raw).startsWith('http')) return raw;
            return `/storage/${String(raw).replace(/^\/+/, '')}`;
        },

        async boot() {
            if (!this.invoiceId) {
                this.error = storefrontL('Invoice tidak valid.', 'Invalid invoice.');
                this.loading = false;
                return;
            }
            await this.load();
            this.startPolling();
            this.startTicker();
        },

        destroy() {
            if (this._pollTimer) window.clearInterval(this._pollTimer);
            if (this._tickTimer) window.clearInterval(this._tickTimer);
        },

        startPolling() {
            if (this._pollTimer) window.clearInterval(this._pollTimer);
            this._pollTimer = window.setInterval(() => this.sync(), 4000);
        },

        startTicker() {
            if (this._tickTimer) window.clearInterval(this._tickTimer);
            this._tickTimer = window.setInterval(() => {
                if (this.secondsLeft > 0) {
                    this.secondsLeft -= 1;
                    if (this.secondsLeft === 0) this.load();
                }
            }, 1000);
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch(
                    `/api/payments/orders/${encodeURIComponent(this.invoiceId)}`,
                    { headers: getAuthToken() ? authHeaders(true) : { Accept: 'application/json' } },
                );
                const json = await readApiJson(res);
                if (!res.ok || json?.success === false) {
                    throw new Error(
                        apiErrorMessage(
                            json,
                            storefrontL('Pembayaran tidak ditemukan.', 'Payment not found.'),
                        ),
                    );
                }
                this.applyData(json.data);
            } catch (err) {
                this.error =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal memuat pembayaran.', 'Failed to load payment.');
            } finally {
                this.loading = false;
                if (!this._enterPlayed) {
                    this._enterPlayed = true;
                    this.$nextTick(() => playBelanjaEntrance(this.$el));
                }
            }
        },

        applyData(data) {
            this.data = data;
            this.brand = data?.brand_color || '#1172BA';
            this.brandDark = this.mixDark(this.brand);
            this.secondsLeft = Math.max(0, Number(data?.seconds_remaining || 0));
            applyProductTheme(this.brand);
            if (data?.payment_status === 'success' || data?.payment_status === 'cancelled') {
                if (this._pollTimer) {
                    window.clearInterval(this._pollTimer);
                    this._pollTimer = null;
                }
            }
        },

        mixDark(hex) {
            try {
                const h = String(hex).replace('#', '');
                const full =
                    h.length === 3
                        ? h
                              .split('')
                              .map((c) => c + c)
                              .join('')
                        : h;
                const n = parseInt(full, 16);
                const r = Math.round(((n >> 16) & 255) * 0.45);
                const g = Math.round(((n >> 8) & 255) * 0.45);
                const b = Math.round((n & 255) * 0.45);
                return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
            } catch {
                return '#0B4F86';
            }
        },

        async sync() {
            if (!this.data || !this.isAwaiting || this.isCod) return;
            try {
                const res = await fetch(
                    `/api/payments/orders/${encodeURIComponent(this.invoiceId)}/sync`,
                    {
                        method: 'POST',
                        headers: { Accept: 'application/json' },
                    },
                );
                const json = await readApiJson(res);
                if (!res.ok || json?.success === false) return;
                const d = json.data || {};
                if (typeof d.seconds_remaining === 'number') {
                    this.secondsLeft = Math.max(0, d.seconds_remaining);
                }
                if (d.paid || d.expired || d.payment_status !== this.data.payment_status) {
                    await this.load();
                    if (d.paid) emitEvomiEvent('history_updated');
                }
            } catch (err) {
                console.error('Payment sync failed', err);
            }
        },

        async copyVa() {
            const value = String(this.data?.meta?.va_number || '').trim();
            if (!value) return;
            try {
                await navigator.clipboard.writeText(value.replace(/\s+/g, ''));
                this.copied = true;
                window.setTimeout(() => {
                    this.copied = false;
                }, 1600);
            } catch {
                /* ignore */
            }
        },

        requestCancel() {
            if (!this.data?.can_cancel || !this.isAwaiting) return;
            this.cancelModal = { open: true, busy: false };
        },

        closeCancelModal() {
            if (this.cancelModal.busy) return;
            this.cancelModal = { open: false, busy: false };
        },

        async confirmCancel() {
            if (!this.invoiceId || this.cancelModal.busy) return;
            if (!getAuthToken()) {
                window.location.replace('/login');
                return;
            }
            this.cancelModal.busy = true;
            try {
                const res = await fetch(
                    `/api/payments/orders/${encodeURIComponent(this.invoiceId)}/cancel`,
                    {
                        method: 'POST',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    },
                );
                const json = await readApiJson(res);
                if (!res.ok || json?.success === false) {
                    throw new Error(
                        apiErrorMessage(
                            json,
                            storefrontL('Gagal membatalkan pesanan.', 'Failed to cancel the order.'),
                        ),
                    );
                }
                this.cancelModal = { open: false, busy: false };
                emitEvomiEvent('history_updated');
                await this.load();
            } catch (err) {
                this.cancelModal.busy = false;
                this.error =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal membatalkan pesanan.', 'Failed to cancel the order.');
                this.cancelModal.open = false;
            }
        },
    }));

    Alpine.data('evomiProfilePayments', () => ({
        loading: true,
        error: '',
        items: [],
        cancelModal: { open: false, row: null, busy: false },
        _tick: null,
        _expiredReload: false,

        formatRupiah,

        get hasCod() {
            return this.items.some((row) => Boolean(row?.is_cod));
        },

        get hasOnline() {
            return this.items.some((row) => !row?.is_cod);
        },

        imageUrl(raw) {
            if (!raw) return '';
            if (String(raw).startsWith('http')) return raw;
            return `/storage/${String(raw).replace(/^\/+/, '')}`;
        },

        formatCountdown(seconds) {
            const s = Math.max(0, Number(seconds) || 0);
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            if (h > 0) return `${h} jam ${m} mnt`;
            return `${m} mnt`;
        },

        requestCancel(row) {
            if (!row?.can_cancel) return;
            this.cancelModal = { open: true, row, busy: false };
        },

        closeCancelModal() {
            if (this.cancelModal.busy) return;
            this.cancelModal = { open: false, row: this.cancelModal.row, busy: false };
        },

        async confirmCancel() {
            const invoiceId = this.cancelModal.row?.invoice_id;
            if (!invoiceId || this.cancelModal.busy) return;
            this.cancelModal.busy = true;
            try {
                const res = await fetch(
                    `/api/payments/orders/${encodeURIComponent(invoiceId)}/cancel`,
                    {
                        method: 'POST',
                        headers: authHeaders(true),
                        credentials: 'same-origin',
                    },
                );
                const json = await readApiJson(res);
                if (!res.ok || json?.success === false) {
                    throw new Error(
                        apiErrorMessage(
                            json,
                            storefrontL('Gagal membatalkan pesanan.', 'Failed to cancel the order.'),
                        ),
                    );
                }
                this.cancelModal = { open: false, row: null, busy: false };
                emitEvomiEvent('history_updated');
            } catch (err) {
                this.cancelModal.busy = false;
                this.error =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal membatalkan pesanan.', 'Failed to cancel the order.');
                this.cancelModal.open = false;
            }
        },

        async init() {
            await this.load();
            this._onHist = () => this.load();
            window.addEventListener('history_updated', this._onHist);
            this._tick = window.setInterval(() => {
                let shouldReload = false;
                this.items = this.items.map((row) => {
                    const current = Number(row.seconds_remaining) || 0;
                    if (current <= 0) return row;
                    const next = current - 1;
                    if (next === 0) shouldReload = true;
                    return { ...row, seconds_remaining: next };
                });
                if (shouldReload && !this._expiredReload) {
                    this._expiredReload = true;
                    this.load().finally(() => {
                        this._expiredReload = false;
                    });
                }
            }, 1000);
        },

        destroy() {
            if (this._onHist) window.removeEventListener('history_updated', this._onHist);
            if (this._tick) window.clearInterval(this._tick);
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                if (!getAuthToken()) {
                    window.location.replace('/login');
                    return;
                }
                const res = await fetch('/api/payments/pending', {
                    headers: authHeaders(true),
                    credentials: 'same-origin',
                });
                const json = await readApiJson(res);
                if (!res.ok) {
                    throw new Error(
                        apiErrorMessage(
                            json,
                            storefrontL('Gagal memuat tagihan.', 'Failed to load bills.'),
                        ),
                    );
                }
                this.items = Array.isArray(json?.data) ? json.data : [];
            } catch (err) {
                this.error =
                    err instanceof Error
                        ? err.message
                        : storefrontL('Gagal memuat tagihan.', 'Failed to load bills.');
            } finally {
                this.loading = false;
            }
        },
    }));
});

if (isDashboardPath(window.location.pathname)) {
    loadAdminModules()
        .catch((err) => console.error('[evomi] gagal memuat modul admin', err))
        .finally(() => Alpine.start());
} else {
    Alpine.start();
}

async function softNavigate(href, { push = true, navIndex = null, force = false, chromeHint = '' } = {}) {
    const url = new URL(href, window.location.origin);

    if (url.origin !== window.location.origin) {
        window.location.href = href;
        return;
    }

    // Browser back/forward already updated location; never treat that as a no-op.
    if (softNavBusy && !force) return;

    const samePath = pathKey(url) === pathKey(window.location.href);
    const nav = window.__evomiNav;

    // Lacak / FAQ / Kontak → modal on click. History restore must load the real page.
    if (!force) {
        const trackMatch = url.pathname.match(/^\/pengiriman\/([^/]+)\/?$/i);
        if (trackMatch) {
            if (nav) nav.open = false;
            const resi = decodeURIComponent(trackMatch[1] || '').trim();
            window.dispatchEvent(new CustomEvent('evomi-open-track', { detail: { resi } }));
            return;
        }

        const helpPath = url.pathname.replace(/\/$/, '') || '/';
        if (helpPath === '/faq') {
            if (nav) nav.open = false;
            window.dispatchEvent(new CustomEvent('evomi-open-faq'));
            return;
        }
        if (helpPath === '/kontak') {
            if (nav) nav.open = false;
            window.dispatchEvent(new CustomEvent('evomi-open-kontak'));
            return;
        }
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

    // Same full URL — no-op (unless force, e.g. locale switch / popstate)
    if (
        !force &&
        samePath &&
        !url.hash &&
        !window.location.hash &&
        url.search === window.location.search
    ) {
        if (nav && navIndex !== null && !Number.isNaN(navIndex)) {
            nav.setActive(navIndex, true);
        }
        return;
    }

    const token = ++softNavToken;
    softNavBusy = true;

    if (nav && navIndex !== null && !Number.isNaN(navIndex)) {
        nav.setActive(navIndex, true);
    }

    const main = document.getElementById('evomi-main');
    const footerWrap = document.getElementById('evomi-footer-wrap');
    const fromPath = lastSoftPath;
    const toPath = url.pathname.replace(/\/$/, '') || '/';
    const belanjaFlow = isBelanjaFlowPath(fromPath) && isBelanjaFlowPath(toPath);
    document.body.classList.toggle('evomi-belanja-flow', belanjaFlow);

    // Set underlay BEFORE fade — beranda needs blue, not white body flash
    setSurfaceForPath(url.pathname);
    previewChromeThemeForNav(fromPath, toPath, chromeHint);

    const fromProfile = isProfilePath(fromPath);
    const toProfile = isProfilePath(toPath);
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

            if (content) {
                content.classList.add('profile-content-panel');
                // Lock frame size during swap so tab height/width never jump
                const lockH = Math.max(content.offsetHeight || 0, content.getBoundingClientRect().height || 0);
                if (lockH > 0) {
                    content.style.height = `${lockH}px`;
                    content.style.minHeight = `${lockH}px`;
                    content.style.maxHeight = `${lockH}px`;
                }
            }

            if (content) {
                content.classList.add('is-leaving');
            }

            const [res] = await Promise.all([
                fetchPromise,
                wait(300),
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

            if (token !== softNavToken) return;

            if (window.Alpine?.destroyTree) {
                Alpine.destroyTree(content);
            }
            content.innerHTML = nextContent.innerHTML;
            if (nextTitle) document.title = nextTitle;
            profileShell.setAttribute('data-active-menu', nextMenu);

            // URL must update BEFORE Alpine init — checkout/boot reads location.search
            if (push) {
                history.pushState({ soft: true }, nextTitle || '', url.pathname + url.search + url.hash);
            } else {
                history.replaceState({ soft: true }, nextTitle || '', url.pathname + url.search + url.hash);
            }

            if (window.Alpine?.initTree) {
                Alpine.initTree(content);
            }
            bindSoftLinks(content);

            // Kartu pengaturan memakai .evomi-soft-enter, yang menahan isinya di
            // opacity 0 sampai kelas siapnya dipasang. Tanpa panggilan ini,
            // berpindah menu ke Pengaturan Profil menukar isinya dengan benar
            // tetapi halamannya tetap kosong - dan kosong selamanya, bukan
            // sekadar terlambat muncul. Halaman Pesan tidak memakai penanda itu,
            // jadi hanya satu arah yang terlihat rusak.
            playBelanjaEntrance(content);

            if (window.__evomiProfileShell) {
                window.__evomiProfileShell.setActive(nextMenu, true);
            }

            window.scrollTo({ top: 0, left: 0 });

            lastSoftPath = toPath;
            scheduleSiteTrafficPing();

            content.classList.remove('is-leaving');
            content.classList.add('is-entering');
            // Force reflow so enter transition always runs
            void content.offsetWidth;
            await waitFrames(2);
            await nextFrame();
            content.classList.remove('is-entering');
            await wait(360);

            // Release lock — CSS fixed frame takes over again
            content.style.height = '';
            content.style.minHeight = '';
            content.style.maxHeight = '';

            if (token === softNavToken) softNavBusy = false;
            return;
        } catch (err) {
            // Fall through to full soft-nav if partial swap fails
            if (String(err?.message || err) !== 'profile soft-nav fallback') {
                console.warn(err);
            }
            const content = profileShell.querySelector('[data-profile-content]');
            content?.classList.remove('is-leaving', 'is-entering');
            if (content) {
                content.style.height = '';
                content.style.minHeight = '';
                content.style.maxHeight = '';
            }
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
            shouldSkipPageLoadingFeel(toPath) ||
            shouldSkipPageLoadingFeel(fromPath);
        const leaveMs = skipPageAnim ? 0 : belanjaFlow ? 420 : 340;
        const leaveStarted = performance.now();

        if (!skipPageAnim) {
            main.classList.add('is-leaving');
        }

        const res = await fetchPromise;
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const html = await res.text();
        if (token !== softNavToken) return;

        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextMain = doc.getElementById('evomi-main');
        const nextFooter = doc.getElementById('evomi-footer-wrap');
        const nextTitle = doc.querySelector('title')?.textContent;

        if (!nextMain) {
            if (token !== softNavToken) return;
            window.location.href = href;
            return;
        }

        if (token !== softNavToken) return;

        const nextTheme =
            chromeThemeFromDoc(doc) ||
            productChromeForPath(toPath) ||
            DEFAULT_THEME_BLUE;

        // Recolor chrome as soon as the next page theme is known — during leave fade
        if (usesProductChrome(toPath)) {
            rememberProductChrome(toPath, nextTheme);
            applyProductTheme(nextTheme);
        } else {
            restoreProductTheme();
        }

        const remain = Math.max(0, leaveMs - (performance.now() - leaveStarted));
        if (remain > 0) await wait(remain);
        if (token !== softNavToken) return;

        const apply = () => {
            if (usesProductChrome(toPath)) {
                applyProductTheme(nextTheme);
            } else {
                restoreProductTheme();
            }

            if (window.Alpine?.destroyTree) {
                Alpine.destroyTree(main);
                if (footerWrap) Alpine.destroyTree(footerWrap);
            }

            main.innerHTML = nextMain.innerHTML;
            if (footerWrap && nextFooter) {
                footerWrap.innerHTML = nextFooter.innerHTML;
                footerWrap.style.backgroundColor =
                    nextFooter.style.backgroundColor || nextTheme;
            }
            if (nextTitle) document.title = nextTitle;

            document.body?.style.setProperty('--evomi-theme', nextTheme);
            lastSoftPath = toPath;
            scheduleSiteTrafficPing();

            // URL must update BEFORE Alpine init — checkout boot() reads window.location.search
            if (push) {
                history.pushState({ soft: true }, nextTitle || '', url.pathname + url.search + url.hash);
            } else {
                history.replaceState({ soft: true }, nextTitle || '', url.pathname + url.search + url.hash);
            }

            if (window.Alpine?.initTree) {
                Alpine.initTree(main);
                if (footerWrap) Alpine.initTree(footerWrap);
            }

            bindSoftLinks(footerWrap || document);
            bindSoftLinks(main);
            seedProductChromeFromDom(main);

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

            main.classList.remove('is-leaving');
            if (!skipPageAnim) {
                main.classList.add('is-entering');
            }
        };

        if (!skipPageAnim && !belanjaFlow && document.startViewTransition) {
            await document.startViewTransition(apply).finished.catch(() => {});
        } else {
            apply();
        }

        await waitFrames(2);
        await nextFrame();
        main.classList.remove('is-entering');
        if (isBelanjaFlowPath(toPath) || isKuisPath(toPath)) playBelanjaEntrance(main);
        await wait(skipPageAnim ? 0 : belanjaFlow ? 560 : 420);
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
        bindFooterEntrance();

        if (url.hash) {
            requestAnimationFrame(() => {
                document.querySelector(url.hash)?.scrollIntoView({ behavior: 'smooth' });
            });
        }
    } catch (err) {
        if (token !== softNavToken) return;
        console.error(err);
        window.location.href = href;
    } finally {
        if (token === softNavToken) softNavBusy = false;
    }
}

window.softNavigate = softNavigate;

/**
 * Jaring pengaman saat tab kembali terlihat.
 *
 * Transisi CSS dan requestAnimationFrame berhenti total di tab yang
 * disembunyikan browser. Kalau pengguna berpindah tab tepat saat panel profil
 * sedang bertransisi masuk, panel itu tertinggal pada opacity 0 - isinya ada di
 * DOM tetapi tidak terlihat - dan kunci navigasinya ikut tertahan sehingga menu
 * berikutnya tidak lagi berpindah. Begitu tab kembali terlihat, sisa kelas
 * transisi dan kunci ukurannya dibereskan supaya keadaannya kembali waras.
 */
document.addEventListener('visibilitychange', () => {
    if (document.hidden) return;

    softNavBusy = false;

    document.querySelectorAll('.profile-content-panel, #evomi-main, #admin-page').forEach((el) => {
        el.classList.remove('is-leaving', 'is-entering');
    });

    document.querySelectorAll('.profile-content-panel').forEach((el) => {
        el.style.height = '';
        el.style.minHeight = '';
        el.style.maxHeight = '';
    });
});

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

            if (el.hasAttribute('data-open-track')) {
                const resi = el.getAttribute('data-track-resi') || '';
                window.dispatchEvent(new CustomEvent('evomi-open-track', { detail: { resi } }));
                return;
            }

            if (el.hasAttribute('data-open-faq')) {
                window.dispatchEvent(new CustomEvent('evomi-open-faq'));
                return;
            }

            if (el.hasAttribute('data-open-kontak')) {
                window.dispatchEvent(new CustomEvent('evomi-open-kontak'));
                return;
            }

            const index = el.dataset.navIndex !== undefined ? Number(el.dataset.navIndex) : null;
            let chromeHint = '';
            try {
                const dest = new URL(href, window.location.origin);
                if (isBelanjaDetailPath(dest.pathname)) {
                    chromeHint = accentFromNavSource(el);
                    if (chromeHint) rememberProductChrome(dest.pathname, chromeHint);
                }
            } catch {
                /* ignore */
            }
            softNavigate(href, { navIndex: index, chromeHint });
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

async function adminSoftNavigate(href, { push = true, force = false } = {}) {
    const url = new URL(href, window.location.origin);
    const page = document.getElementById('admin-page');

    if (url.origin !== window.location.origin || !isDashboardPath(url.pathname) || !page) {
        window.location.href = href;
        return;
    }

    const targetKey = adminMenuKeyFromPath(url.pathname);

    if (
        !force &&
        pathKey(url) === pathKey(window.location.href) &&
        url.search === window.location.search
    ) {
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
        await nextFrame();
        page.classList.remove('is-entering');
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
    lastSoftPath = window.location.pathname.replace(/\/$/, '') || '/';
    setSurfaceForPath(window.location.pathname);
    bindSoftLinks(document);
    seedProductChromeFromDom(document);
    if (isBelanjaDetailPath(lastSoftPath)) {
        rememberProductChrome(
            lastSoftPath,
            document.body?.style.getPropertyValue('--evomi-theme') || '',
        );
    }
    scheduleBelanjaEntrance(document);
    bindFooterEntrance();
    initBerandaMotion(document);
    bindAdminNav();
    initSourceGuard(getAuthUser);
    scheduleSiteTrafficPing();
    startSiteTrafficHeartbeat();
    try {
        const st = history.state;
        if (!st || (typeof st === 'object' && !st.soft && !st.admin)) {
            history.replaceState(
                { ...(st && typeof st === 'object' ? st : {}), soft: true },
                document.title,
                window.location.href,
            );
        }
    } catch {
        /* ignore */
    }
});

window.addEventListener('popstate', () => {
    if (isDashboardPath(window.location.pathname) && document.getElementById('admin-page')) {
        adminSoftNavigate(window.location.href, { push: false, force: true });
        return;
    }
    softNavigate(window.location.href, { push: false, force: true });
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

    const cleanups = [];
    bindAboutHashNav(scope, cleanups);

    const finish = () => {
        berandaMotionCleanup = () => cleanups.forEach((fn) => fn());
    };

    const hero = scope.querySelector('.hero-section');
    if (!hero && !scope.querySelector('[data-reveal], [data-parallax]')) {
        finish();
        return;
    }

    if (prefersReducedMotion()) {
        scope.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-revealed'));
        if (hero) {
            hero.classList.add('is-hero-ready', 'is-hero-float-live');
        }
        finish();
        return;
    }

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

/**
 * Tentang (#about): move white pill to Tentang while the section is in view,
 * then return to Beranda once #about leaves the viewport.
 */
function bindAboutHashNav(scope, cleanups) {
    const about = scope?.querySelector?.('#about') || document.querySelector('#about');
    if (!about) return;

    const sanitizeAboutHash = () => {
        if (window.location.hash === '#third-section') {
            window.history.replaceState(
                null,
                '',
                window.location.pathname + window.location.search + '#about',
            );
            return;
        }
        if (window.location.hash.includes('#about#about')) {
            window.history.replaceState(
                null,
                '',
                window.location.pathname + window.location.search + '#about',
            );
        }
    };

    const activateTentangIfNeeded = (animate = false) => {
        sanitizeAboutHash();
        if (window.location.hash !== '#about') return;
        if (!isBerandaPath(window.location.pathname)) return;
        window.__evomiNav?.setActive?.(1, animate);
    };

    activateTentangIfNeeded(false);
    // Navbar Alpine may finish measuring one tick later on first paint
    window.setTimeout(() => activateTentangIfNeeded(false), 80);

    const onHashChange = () => activateTentangIfNeeded(true);
    window.addEventListener('hashchange', onHashChange);
    cleanups.push(() => window.removeEventListener('hashchange', onHashChange));

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) return;
                if (window.location.hash !== '#about') return;
                if (!isBerandaPath(window.location.pathname)) return;

                window.history.replaceState(
                    null,
                    '',
                    window.location.pathname + window.location.search,
                );
                window.__evomiNav?.setActive?.(0, true);
            });
        },
        { threshold: 0 },
    );

    observer.observe(about);
    cleanups.push(() => observer.disconnect());
}
