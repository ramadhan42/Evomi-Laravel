const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
const CHALLENGE_TIMEOUT_MS = 20000;
const SESSION_KEY = 'evomi-turnstile-session';

let scriptPromise = null;

export function turnstileEnabled() {
    const cfg = window.EVOMI_TURNSTILE || {};

    return Boolean(cfg.enabled && cfg.siteKey);
}

export function turnstileRequiredMessage() {
    return window.EVOMI_TURNSTILE?.requiredMessage || 'Selesaikan verifikasi keamanan terlebih dahulu.';
}

/**
 * Cermin dari cache "turnstile-session" di server (lihat VerifyTurnstile mode
 * session). Dipakai supaya widget captcha ikut hilang di UI selama verifikasi
 * masih berlaku; server tetap jadi penentu akhir lewat flag captcha_required.
 */
export function isTurnstileSessionVerified() {
    try {
        const until = Number(window.sessionStorage.getItem(SESSION_KEY) || 0);

        return until > Date.now();
    } catch {
        return false;
    }
}

export function markTurnstileSessionVerified() {
    const minutes = Number(window.EVOMI_TURNSTILE?.sessionMinutes) || 30;

    try {
        window.sessionStorage.setItem(SESSION_KEY, String(Date.now() + minutes * 60000));
    } catch {
        /* private mode: cukup andalkan server */
    }
}

export function clearTurnstileSessionVerified() {
    try {
        window.sessionStorage.removeItem(SESSION_KEY);
    } catch {
        /* ignore */
    }
}

function loadTurnstileScript() {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    if (!scriptPromise) {
        scriptPromise = new Promise((resolve, reject) => {
            const existing = document.querySelector(`script[src^="${SCRIPT_SRC}"]`);
            if (existing) {
                existing.addEventListener('load', () => resolve(window.turnstile));
                existing.addEventListener('error', reject);
                return;
            }

            const script = document.createElement('script');
            script.src = SCRIPT_SRC;
            script.async = true;
            script.defer = true;
            script.onload = () => resolve(window.turnstile);
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    return scriptPromise;
}

/**
 * Reactive fields consumed by the shared `partials.turnstile-field` markup.
 */
export function turnstileState(enabled = true) {
    return {
        hasTurnstile: turnstileEnabled() && enabled,
        turnstileWidgetId: null,
        turnstileToken: '',
        turnstileStatus: 'idle',
        turnstileTimer: 0,
    };
}

/**
 * The widget is rendered in `execute` mode so the challenge never starts on its
 * own — it waits for the visitor to tick the Evomi checkbox.
 */
export async function setupTurnstile(host, container, { theme = 'light' } = {}) {
    if (!host.hasTurnstile || !container) {
        return;
    }

    destroyTurnstile(host);

    let turnstile;
    try {
        turnstile = await loadTurnstileScript();
    } catch {
        host.hasTurnstile = false;
        return;
    }

    container.innerHTML = '';

    host.turnstileWidgetId = turnstile.render(container, {
        sitekey: window.EVOMI_TURNSTILE.siteKey,
        theme,
        execution: 'execute',
        appearance: 'interaction-only',
        callback: (token) => {
            window.clearTimeout(host.turnstileTimer);
            host.turnstileToken = token;
            host.turnstileStatus = 'verified';
        },
        'expired-callback': () => resetTurnstile(host),
        'error-callback': (code) => {
            window.clearTimeout(host.turnstileTimer);
            host.turnstileToken = '';
            host.turnstileStatus = 'error';

            // 110200 = hostname belum terdaftar di Hostname Management widget.
            console.warn('[Turnstile] challenge error', code);
        },
    });
}

export function destroyTurnstile(host) {
    window.clearTimeout(host.turnstileTimer);
    host.turnstileTimer = 0;
    host.turnstileToken = '';
    host.turnstileStatus = 'idle';

    if (host.turnstileWidgetId && window.turnstile) {
        try {
            window.turnstile.remove(host.turnstileWidgetId);
        } catch {
            try {
                window.turnstile.reset(host.turnstileWidgetId);
            } catch {
                /* widget sudah tidak ada */
            }
        }
    }

    host.turnstileWidgetId = null;
}

/**
 * Untuk widget yang dipasang belakangan (panel chat baru dibuka, atau captcha
 * muncul lagi karena server menolak) — aman dipanggil berkali-kali.
 */
export async function ensureTurnstileMounted(host, container, options = {}) {
    if (!host.hasTurnstile || !container || host.turnstileWidgetId) {
        return;
    }

    await setupTurnstile(host, container, options);
}

export function runTurnstile(host) {
    if (!host.hasTurnstile || !host.turnstileWidgetId || !window.turnstile) {
        return;
    }

    if (host.turnstileStatus === 'pending' || host.turnstileStatus === 'verified') {
        return;
    }

    const previousStatus = host.turnstileStatus;
    host.turnstileToken = '';
    host.turnstileStatus = 'pending';

    window.clearTimeout(host.turnstileTimer);
    host.turnstileTimer = window.setTimeout(() => {
        if (host.turnstileStatus === 'pending') {
            host.turnstileStatus = 'error';
        }
    }, CHALLENGE_TIMEOUT_MS);

    try {
        if (previousStatus === 'error') {
            window.turnstile.reset(host.turnstileWidgetId);
        }
        window.turnstile.execute(host.turnstileWidgetId);
    } catch {
        window.clearTimeout(host.turnstileTimer);
        host.turnstileStatus = 'error';
    }
}

export function resetTurnstile(host) {
    window.clearTimeout(host.turnstileTimer);
    host.turnstileToken = '';
    host.turnstileStatus = 'idle';

    if (host.turnstileWidgetId && window.turnstile) {
        window.turnstile.reset(host.turnstileWidgetId);
    }
}

export function turnstileToken(host) {
    if (!host.hasTurnstile) {
        return '';
    }

    if (host.turnstileToken) {
        return host.turnstileToken;
    }

    if (host.turnstileWidgetId && window.turnstile) {
        return window.turnstile.getResponse(host.turnstileWidgetId) || '';
    }

    return '';
}
