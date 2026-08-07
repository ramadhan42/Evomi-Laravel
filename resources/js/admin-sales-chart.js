/**
 * Alpine-native sales area chart (Next.js Recharts look-alike).
 * Renders a full SVG string — HTML parsers break <template> inside <svg>.
 */

function chartIsDark() {
    return document.documentElement.getAttribute('data-admin-theme') === 'dark';
}

function esc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/** Round max up to a clean Recharts-like tick ceiling. */
function niceMax(raw) {
    const n = Math.max(Number(raw) || 0, 1);
    const exp = Math.floor(Math.log10(n));
    const base = 10 ** exp;
    const frac = n / base;
    let niceFrac;
    if (frac <= 1) niceFrac = 1;
    else if (frac <= 2) niceFrac = 2;
    else if (frac <= 5) niceFrac = 5;
    else niceFrac = 10;
    return niceFrac * base;
}

function formatAxisTick(value) {
    const v = Number(value) || 0;
    if (v >= 1_000_000) {
        const m = v / 1_000_000;
        return `Rp${Number.isInteger(m) ? m : m.toFixed(1)}jt`;
    }
    return `Rp${Math.round(v / 1000)}k`;
}

/**
 * Recharts/d3 curveMonotoneX — cubic Hermite yang tidak overshoot
 * (mencegah lengkung turun di bawah titik / baseline).
 */
function monotoneAreaPaths(points, width, height, pad, maxY) {
    if (!points.length) {
        return { line: '', area: '', coords: [] };
    }

    const innerW = width - pad.left - pad.right;
    const innerH = height - pad.top - pad.bottom;
    const n = points.length;
    const baseY = height - pad.bottom;
    const minY = pad.top;

    const coords = points.map((p, i) => {
        const x = pad.left + (n === 1 ? innerW / 2 : (i / (n - 1)) * innerW);
        const y = pad.top + innerH - ((Number(p.total) || 0) / maxY) * innerH;
        return { x, y, name: p.name, total: Number(p.total) || 0 };
    });

    const clampY = (y) => Math.min(baseY, Math.max(minY, y));

    if (coords.length === 1) {
        const c = coords[0];
        const span = Math.min(innerW * 0.22, 56);
        const left = Math.max(pad.left, c.x - span);
        const right = Math.min(width - pad.right, c.x + span);
        // Puncak sederhana ke nilai hari itu — tidak melengkung di bawah baseline
        return {
            line: `M ${left} ${baseY} L ${c.x} ${c.y} L ${right} ${baseY}`,
            area: `M ${left} ${baseY} L ${c.x} ${c.y} L ${right} ${baseY} Z`,
            coords: [c],
        };
    }

    const deltaX = [];
    const slopes = [];
    for (let i = 0; i < n - 1; i += 1) {
        const dx = coords[i + 1].x - coords[i].x;
        const dy = coords[i + 1].y - coords[i].y;
        deltaX[i] = dx;
        slopes[i] = dx ? dy / dx : 0;
    }

    const tangents = new Array(n);
    tangents[0] = slopes[0];
    let prevSlope = slopes[0];
    for (let i = 1; i < n - 1; i += 1) {
        const slope = slopes[i];
        if (prevSlope * slope <= 0 || prevSlope === 0 || slope === 0) {
            tangents[i] = 0;
        } else {
            const dx0 = deltaX[i - 1];
            const dx1 = deltaX[i];
            const common = dx0 + dx1;
            tangents[i] =
                (3 * common) / ((common + dx1) / prevSlope + (common + dx0) / slope);
        }
        prevSlope = slope;
    }
    tangents[n - 1] = slopes[n - 2];

    const segments = [];
    for (let i = 0; i < n - 1; i += 1) {
        const p0 = coords[i];
        const p1 = coords[i + 1];
        const dx = (p1.x - p0.x) / 3;
        const cp1x = p0.x + dx;
        const cp1y = clampY(p0.y + dx * tangents[i]);
        const cp2x = p1.x - dx;
        const cp2y = clampY(p1.y - dx * tangents[i + 1]);
        segments.push(`C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p1.x} ${p1.y}`);
    }

    const first = coords[0];
    const last = coords[coords.length - 1];
    const line = `M ${first.x} ${first.y} ${segments.join(' ')}`;
    const area = `M ${first.x} ${baseY} L ${first.x} ${first.y} ${segments.join(' ')} L ${last.x} ${baseY} Z`;

    return { line, area, coords };
}

function xLabelStep(count) {
    if (count <= 10) return 1;
    if (count <= 16) return 2;
    if (count <= 31) return 3;
    return Math.ceil(count / 10);
}

export function buildSalesChartModel(data, formatRupiah, revenueLabel = 'Pendapatan') {
    const width = 720;
    const height = 300;
    const pad = { top: 18, right: 18, left: 58, bottom: 38 };
    const dark = chartIsDark();
    const points = Array.isArray(data) ? data : [];
    const rawMax = Math.max(...points.map((p) => Number(p.total) || 0), 0);
    const maxY = niceMax(rawMax || 1);
    const { line, area, coords } = monotoneAreaPaths(points, width, height, pad, maxY);

    const gridStroke = dark ? '#2a3344' : '#f3f4f6';
    const tickFill = dark ? '#9aa3b2' : '#9ca3af';
    const gradOpacity = dark ? 0.32 : 0.14;
    const cursorStroke = dark ? '#3b465c' : '#e5e7eb';
    const dotStroke = dark ? '#111827' : '#ffffff';
    const gridCount = 4;
    const gradId = `evomiSalesGrad-${Math.random().toString(36).slice(2, 9)}`;
    const labelStep = xLabelStep(coords.length);

    let gridsXml = '';
    for (let i = 0; i <= gridCount; i += 1) {
        const t = i / gridCount;
        const y = pad.top + (height - pad.top - pad.bottom) * t;
        const value = maxY * (1 - t);
        gridsXml += `<line class="admin-chart-grid" x1="${pad.left}" x2="${width - pad.right}" y1="${y}" y2="${y}" stroke="${gridStroke}" stroke-dasharray="3 3" stroke-width="1"></line>`;
        gridsXml += `<text class="admin-chart-tick" x="${pad.left - 10}" y="${y + 4}" text-anchor="end" font-size="11" fill="${tickFill}">${esc(formatAxisTick(value))}</text>`;
    }

    let xLabelsXml = '';
    coords.forEach((c, i) => {
        const show =
            i === 0 || i === coords.length - 1 || i % labelStep === 0 || coords.length <= 7;
        if (!show) return;
        xLabelsXml += `<text class="admin-chart-tick" x="${c.x}" y="${height - 12}" text-anchor="middle" font-size="11" fill="${tickFill}">${esc(c.name)}</text>`;
    });

    let dotsXml = '';
    for (const c of coords) {
        if (!(c.total > 0)) continue;
        dotsXml += `<circle class="admin-chart-dot" cx="${c.x}" cy="${c.y}" r="3.5" fill="#10B981" stroke="${dotStroke}" stroke-width="2"></circle>`;
    }

    const svg = [
        `<svg class="admin-sales-chart-svg" viewBox="0 0 ${width} ${height}" width="100%" height="100%" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Grafik penjualan">`,
        `<defs>`,
        `<linearGradient id="${gradId}" x1="0" y1="0" x2="0" y2="1">`,
        `<stop offset="5%" stop-color="#10B981" stop-opacity="${gradOpacity}"></stop>`,
        `<stop offset="95%" stop-color="#10B981" stop-opacity="0"></stop>`,
        `</linearGradient>`,
        `<filter id="${gradId}-glow" x="-40%" y="-40%" width="180%" height="180%">`,
        `<feGaussianBlur stdDeviation="2.2" result="blur"/>`,
        `<feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>`,
        `</filter>`,
        `</defs>`,
        gridsXml,
        area
            ? `<path class="admin-chart-area" d="${area}" fill="url(#${gradId})"></path>`
            : '',
        line
            ? `<path class="admin-chart-line" d="${line}" fill="none" stroke="#10B981" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" pathLength="1"></path>`
            : '',
        dotsXml,
        xLabelsXml,
        `<g data-chart-hover></g>`,
        `</svg>`,
    ].join('');

    return {
        width,
        height,
        pad,
        coords,
        cursorStroke,
        dotStroke,
        glowFilter: `${gradId}-glow`,
        formatRupiah,
        revenueLabel,
        svg,
        empty: coords.length === 0,
    };
}

export function salesChartHoverAt(model, clientX, containerEl) {
    if (!model?.coords?.length || !containerEl) return null;
    const svg = containerEl.querySelector('svg');
    if (!svg) return null;
    const rect = svg.getBoundingClientRect();
    if (!rect.width) return null;
    const x = ((clientX - rect.left) / rect.width) * model.width;

    let best = model.coords[0];
    let bestDist = Infinity;
    for (const c of model.coords) {
        const d = Math.abs(c.x - x);
        if (d < bestDist) {
            bestDist = d;
            best = c;
        }
    }

    const hoverGroup = svg.querySelector('[data-chart-hover]');
    if (hoverGroup) {
        hoverGroup.innerHTML = [
            `<line x1="${best.x}" x2="${best.x}" y1="${model.pad.top}" y2="${model.height - model.pad.bottom}" stroke="${model.cursorStroke}" stroke-width="1.25" stroke-dasharray="4 4"></line>`,
            `<circle cx="${best.x}" cy="${best.y}" r="7" fill="#10B981" fill-opacity="0.18"></circle>`,
            `<circle cx="${best.x}" cy="${best.y}" r="5" fill="#10B981" stroke="${model.dotStroke || '#ffffff'}" stroke-width="2.5" filter="url(#${model.glowFilter})"></circle>`,
        ].join('');
    }

    // Keep tooltip inside the chart box
    const leftPct = Math.min(88, Math.max(12, (best.x / model.width) * 100));
    const topPct = Math.min(72, Math.max(18, (best.y / model.height) * 100));

    return {
        x: best.x,
        y: best.y,
        name: best.name,
        total: best.total,
        label: model.formatRupiah(best.total),
        leftPct,
        topPct,
    };
}

export function salesChartClearHover(containerEl) {
    const hoverGroup = containerEl?.querySelector?.('[data-chart-hover]');
    if (hoverGroup) hoverGroup.innerHTML = '';
}
