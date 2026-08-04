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

/** Paint Evomi blue under soft-nav so fade never flashes white into beranda */
function setSurfaceForPath(pathname) {
    document.body.classList.toggle('evomi-surface-blue', isBerandaPath(pathname));
}

function wait(ms) {
    return new Promise((r) => setTimeout(r, ms));
}

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
        activeIndex,
        indicator: { left: 0, width: 0, opacity: 0 },

        get indicatorStyle() {
            return {
                transform: `translate3d(${this.indicator.left}px, 0, 0)`,
                width: `${Math.max(this.indicator.width, 0)}px`,
                opacity: this.indicator.opacity,
            };
        },

        init() {
            window.__evomiNav = this;
            this.$nextTick(() => {
                this.moveIndicator(this.activeIndex, false);
                requestAnimationFrame(() => {
                    this.moveIndicator(this.activeIndex, true);
                });
            });

            window.addEventListener('resize', () => {
                this.moveIndicator(this.activeIndex, false);
            });
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

        const [res] = await Promise.all([fetchPromise, wait(220)]);

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

            main.innerHTML = nextMain.innerHTML;
            if (footerWrap && nextFooter) {
                footerWrap.innerHTML = nextFooter.innerHTML;
            }
            if (nextTitle) document.title = nextTitle;

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

            main.classList.remove('is-leaving');
            main.classList.add('is-entering');
        };

        if (document.startViewTransition) {
            await document.startViewTransition(apply).finished.catch(() => {});
        } else {
            apply();
        }

        await waitFrames(2);
        main.classList.remove('is-entering');
        // Re-measure pill after layout settles
        if (nav) {
            nav.moveIndicator(nav.activeIndex, true);
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
        if (match === '/' && (p === '/' || p === '')) best = i;
        if (match !== '/' && match !== '#about' && p.startsWith(match)) best = i;
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
