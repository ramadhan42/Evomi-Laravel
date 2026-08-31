/**
 * Shared admin CRUD helpers + Alpine components for Evomi dashboard.
 * Registered from app.js inside alpine:init.
 */

import {
    SECTION_LABELS,
    SECTION_ORDER,
    fieldLabel,
    sortSectionFields,
    ensureFontCompanionFields,
    ensureSectionSpacingFields,
    ensureBerandaContentFields,
    ensureBelanjaDetailsShippingFields,
    ensureHeroHeadlineGapFields,
    defaultFaqTypographyFields,
    isTypographyBaseField,
    defaultMaxLines,
} from './cms-meta';
import {
    createAdminI18nApi,
    readAdminLocale,
} from './admin-i18n';

function unwrapList(payload) {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    if (Array.isArray(payload?.data?.data)) return payload.data.data;
    if (payload?.data && typeof payload.data === 'object') {
        return Object.values(payload.data);
    }
    return [];
}

function unwrapData(payload) {
    if (payload?.data !== undefined) return payload.data;
    return payload;
}

function toInputDate(value) {
    if (!value) return '';
    return String(value).slice(0, 10);
}

function formatDisplayDate(value, locale = 'id') {
    if (!value) return '—';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return toInputDate(value);
    return parsed.toLocaleDateString(locale === 'en' ? 'en-US' : 'id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function todayInputDate() {
    return new Date().toISOString().slice(0, 10);
}

export function registerAdminCrud(Alpine, deps) {
    const {
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
    } = deps;

    async function adminJson(url, options = {}) {
        const { method = 'GET', body = null, json = true } = options;
        const headers = authHeaders(json && !(body instanceof FormData));
        if (body instanceof FormData) {
            delete headers['Content-Type'];
        }
        const res = await fetch(url, {
            method,
            headers,
            credentials: 'same-origin',
            body: body instanceof FormData || typeof body === 'string' ? body : body ? JSON.stringify(body) : undefined,
        });
        if (res.status === 401 || res.status === 403) {
            clearAuthSession();
            window.location.replace('/login');
            throw new Error('Sesi admin berakhir.');
        }
        const data = await readApiJson(res);
        if (!res.ok) {
            throw new Error(apiErrorMessage(data, 'Permintaan gagal.'));
        }
        return data;
    }

    /**
     * Multipart upload that reports real byte progress, which fetch() cannot do.
     * Progress caps at 95% until the server responds so the bar never sits at
     * 100% while Laravel is still processing the images.
     */
    function uploadWithProgress(url, formData, { method = 'POST', onProgress } = {}) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open(method, url, true);
            xhr.withCredentials = true;

            const headers = authHeaders(false);
            delete headers['Content-Type'];
            Object.entries(headers).forEach(([key, value]) => {
                xhr.setRequestHeader(key, value);
            });

            if (typeof onProgress === 'function') {
                xhr.upload.onprogress = (event) => {
                    if (!event.lengthComputable) return;
                    const percent = Math.round((event.loaded / event.total) * 100);
                    onProgress(Math.min(95, percent));
                };
                xhr.upload.onload = () => onProgress(95);
            }

            xhr.onerror = () => reject(new Error('Koneksi gagal saat mengunggah.'));
            xhr.onabort = () => reject(new Error('Unggahan dibatalkan.'));

            xhr.onload = () => {
                let data = {};
                try {
                    data = xhr.responseText ? JSON.parse(xhr.responseText) : {};
                } catch {
                    data = {};
                }

                if (xhr.status === 401 || xhr.status === 403) {
                    clearAuthSession();
                    window.location.replace('/login');
                    reject(new Error('Sesi admin berakhir.'));
                    return;
                }

                if (xhr.status < 200 || xhr.status >= 300) {
                    reject(new Error(apiErrorMessage(data, 'Permintaan gagal.')));
                    return;
                }

                if (typeof onProgress === 'function') onProgress(100);
                resolve(data);
            };

            xhr.send(formData);
        });
    }

    Alpine.store('adminUi', {
        toast: { open: false, message: '', type: 'success' },
        confirm: {
            open: false,
            title: 'Konfirmasi',
            message: '',
            _resolve: null,
        },
        locale: readAdminLocale(),
        _toastTimer: null,

        notify(message, type = 'success') {
            this.toast = { open: true, message, type };
            if (this._toastTimer) clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => {
                this.toast.open = false;
            }, 3200);
        },

        i18n() {
            return createAdminI18nApi(() => this.locale || readAdminLocale());
        },

        i18nCancel() {
            return this.i18n().common().cancel;
        },

        i18nYesDelete() {
            return this.i18n().common().yes_delete;
        },

        askConfirm(message, title) {
            const i18n = this.i18n();
            return new Promise((resolve) => {
                this.confirm = {
                    open: true,
                    title: title || i18n.common().confirm_delete,
                    message,
                    _resolve: resolve,
                };
            });
        },

        cancelConfirm() {
            const r = this.confirm._resolve;
            this.confirm.open = false;
            this.confirm._resolve = null;
            if (r) r(false);
        },

        runConfirm() {
            const r = this.confirm._resolve;
            this.confirm.open = false;
            this.confirm._resolve = null;
            if (r) r(true);
        },
    });

    window.addEventListener('evomi-admin-locale', (e) => {
        Alpine.store('adminUi').locale = e.detail || readAdminLocale();
    });

    function i18nMixin() {
        return {
            locale: readAdminLocale(),

            t(section, key, id = '', en = '') {
                return createAdminI18nApi(() => this.locale).t(section, key, id, en);
            },

            common() {
                return createAdminI18nApi(() => this.locale).common();
            },

            watchLocale() {
                window.addEventListener('evomi-admin-locale', (e) => {
                    this.locale = e.detail || readAdminLocale();
                });
            },
        };
    }

    function listMixin(perPage = 8) {
        return {
            ...i18nMixin(),
            loading: true,
            error: '',
            items: [],
            search: '',
            page: 1,
            perPage,
            saving: false,
            modalOpen: false,
            modalMode: 'add',
            formatRupiah,
            storageUrl,
            mediaUrl,
            resolveAvatarUrl,

            openModal() {
                this.modalOpen = true;
                document.body.style.overflow = 'hidden';
            },

            closeModal() {
                this.modalOpen = false;
                document.body.style.overflow = '';
            },

            formatDate(value) {
                return formatDisplayDate(value, this.locale);
            },

            /** Prefer image_2 / gambar_2 (catalogue packshot) for table thumbs. */
            productThumb(product) {
                if (!product) return '';
                return mediaUrl(
                    product.image_2 ||
                        product.gambar_2 ||
                        product.image_produk_belanja ||
                        product.image_1 ||
                        product.gambar_1 ||
                        product.image ||
                        product.image_url,
                );
            },

            articleThumb(article) {
                if (!article) return '';
                return mediaUrl(article.image_url || article.image);
            },

            filteredItems() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.items;
                return this.items.filter((row) => JSON.stringify(row).toLowerCase().includes(q));
            },

            pageCount() {
                return Math.max(1, Math.ceil(this.filteredItems().length / this.perPage));
            },

            pagedItems() {
                const start = (this.page - 1) * this.perPage;
                return this.filteredItems().slice(start, start + this.perPage);
            },

            /** Windowed page list with '…' gaps, e.g. 1 … 4 5 6 … 12 */
            pageNumbers(window = 5) {
                const total = this.pageCount();
                if (total <= window) {
                    return Array.from({ length: total }, (_, i) => i + 1);
                }

                const wanted = new Set([1, total, this.page]);
                for (let step = 1; wanted.size < window; step += 1) {
                    if (this.page - step > 1) wanted.add(this.page - step);
                    if (wanted.size < window && this.page + step < total) {
                        wanted.add(this.page + step);
                    }
                    if (this.page - step <= 1 && this.page + step >= total) break;
                }

                const out = [];
                let previous = 0;
                for (const value of [...wanted].sort((a, b) => a - b)) {
                    if (previous && value - previous > 1) out.push('…');
                    out.push(value);
                    previous = value;
                }
                return out;
            },

            goToPage(value) {
                const target = Number(value);
                if (!Number.isFinite(target)) return;
                this.page = Math.min(Math.max(1, target), this.pageCount());
            },

            rangeStart() {
                if (!this.filteredItems().length) return 0;
                return (this.page - 1) * this.perPage + 1;
            },

            rangeEnd() {
                return Math.min(this.page * this.perPage, this.filteredItems().length);
            },

            notify(msg, type = 'success') {
                Alpine.store('adminUi').notify(msg, type);
            },

            async confirmDelete(message, title) {
                return Alpine.store('adminUi').askConfirm(message, title);
            },

            watchSearch() {
                this.$watch('search', () => {
                    this.page = 1;
                });
                window.addEventListener('evomi-admin-locale', (e) => {
                    this.locale = e.detail || readAdminLocale();
                });
            },
        };
    }

    /* ---------- PRODUCTS ---------- */
    Alpine.data('evomiAdminProducts', () => ({
        ...listMixin(1000),
        form: emptyProductForm(),
        files: {},
        previews: {},
        uploadProgress: 0,

        init() {
            this.watchSearch();
            this.load();
        },

        filteredItems() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.items;
            return this.items.filter((p) => {
                const title = String(p.title || '').toLowerCase();
                const personality = String(p.personality_type || '').toLowerCase();
                return title.includes(q) || personality.includes(q);
            });
        },

        stockBadgeClass(p) {
            const qty = Number(p.quantity) || 0;
            const status = String(p.stock_status || '').toLowerCase();
            if (status === 'habis' || qty <= 0) {
                return 'bg-red-50 text-red-700 border-red-200';
            }
            if (status === 'minim' || qty <= 10) {
                return 'bg-amber-50 text-amber-700 border-amber-200';
            }
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/products', { json: false });
                this.items = unwrapList(data);
            } catch (e) {
                this.error = e.message;
                this.items = [];
            } finally {
                this.loading = false;
            }
        },

        openAdd() {
            this.modalMode = 'add';
            this.form = emptyProductForm();
            this.files = {};
            this.previews = {};
            this.saving = false;
            this.uploadProgress = 0;
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        openEdit(p) {
            this.modalMode = 'edit';
            this.form = {
                id: p.id,
                title: p.title || '',
                title_en: p.title_en || '',
                description: p.description || '',
                description_en: p.description_en || '',
                price: p.price ?? '',
                quantity: p.quantity ?? 0,
                personality_type: p.personality_type === 'purpose_prestige' ? 'prestige' : p.personality_type || 'prestige',
                stock_status: p.stock_status || 'tersedia',
                perfume_type: p.perfume_type || 'Eau de Parfum',
                gender: p.gender || 'unisex',
                bottle_size: p.bottle_size || 50,
                berat_satuan: p.berat_satuan ?? 60,
                color: p.color || '#1172BA',
                top_note: p.top_note || '',
                middle_note: p.middle_note || '',
                base_note: p.base_note || '',
            };
            this.files = {};
            this.previews = {
                image_produk_belanja: mediaUrl(p.image_produk_belanja),
                image_1: mediaUrl(p.image_1),
                image_2: mediaUrl(p.image_2),
                image_3: mediaUrl(p.image_3),
            };
            this.saving = false;
            this.uploadProgress = 0;
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            if (this.saving) return;
            this.modalOpen = false;
            this.uploadProgress = 0;
            document.body.style.overflow = '';
        },

        imageFields: [
            { key: 'image_produk_belanja', label: 'image_main', requiredOnCreate: true },
            { key: 'image_1', label: 'image_slider_1', requiredOnCreate: true },
            { key: 'image_2', label: 'image_slider_2', requiredOnCreate: false },
            { key: 'image_3', label: 'image_slider_3', requiredOnCreate: false },
        ],

        /** True when the preview still points at the stored file, not a fresh pick. */
        hasStoredImage(field) {
            const preview = this.previews[field];
            return Boolean(preview) && !String(preview).startsWith('blob:');
        },

        uploadHeadline() {
            return this.uploadProgress >= 100
                ? this.t('products', 'upload_finishing')
                : this.t('products', 'upload_in_progress');
        },

        onFile(field, event) {
            const file = event.target.files?.[0];
            if (!file) return;
            if (file.size > 40 * 1024 * 1024) {
                this.notify(this.t('products', 'image_too_large'), 'error');
                event.target.value = '';
                return;
            }
            this.files[field] = file;
            this.previews[field] = URL.createObjectURL(file);
        },

        clearFile(field, inputEl) {
            delete this.files[field];
            this.previews[field] = '';
            if (inputEl) inputEl.value = '';
        },

        async save() {
            if (this.saving) return;
            if (this.modalMode === 'add' && (!this.files.image_produk_belanja || !this.files.image_1)) {
                this.notify(this.t('products', 'images_required'), 'error');
                return;
            }
            const imageKeys = ['image_produk_belanja', 'image_1', 'image_2', 'image_3'];
            const hasImages = imageKeys.some((f) => this.files[f]);

            this.saving = true;
            this.uploadProgress = hasImages ? 0 : 15;
            try {
                const fd = new FormData();
                Object.entries(this.form).forEach(([k, v]) => {
                    if (k === 'id') return;
                    if (v !== null && v !== undefined) fd.append(k, v);
                });
                imageKeys.forEach((f) => {
                    if (this.files[f]) fd.append(f, this.files[f]);
                });

                const url =
                    this.modalMode === 'add'
                        ? '/api/products'
                        : `/api/products/${this.form.id}`;

                await uploadWithProgress(url, fd, {
                    onProgress: (percent) => {
                        this.uploadProgress = hasImages ? percent : Math.max(15, percent);
                    },
                });

                this.notify(
                    this.modalMode === 'add'
                        ? this.t('products', 'created')
                        : this.t('products', 'updated'),
                );
                this.saving = false;
                this.uploadProgress = 0;
                this.closeModal();
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
                this.uploadProgress = 0;
            }
        },

        async remove(id) {
            if (!(await this.confirmDelete(this.t('products', 'confirm_delete_desc')))) return;
            try {
                await adminJson(`/api/products/${id}`, { method: 'DELETE' });
                this.notify(this.t('products', 'deleted'));
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },
    }));

    function emptyProductForm() {
        return {
            id: null,
            title: '',
            title_en: '',
            description: '',
            description_en: '',
            price: '',
            quantity: 10,
            personality_type: 'prestige',
            stock_status: 'tersedia',
            perfume_type: 'Eau de Parfum',
            gender: 'unisex',
            bottle_size: 50,
            berat_satuan: 60,
            color: '#1172BA',
            top_note: '',
            middle_note: '',
            base_note: '',
        };
    }

    /* ---------- ORDERS ---------- */
    Alpine.data('evomiAdminOrders', () => ({
        ...listMixin(5),
        editOpen: false,
        edit: {
            id: null,
            status: '',
            payment_status: '',
            isCod: false,
            imageUrl: '',
            productTitle: '',
            customerName: '',
            totalLabel: '',
        },
        originalEdit: { status: '', payment_status: '' },
        statusOptions: [
            'menunggu_konfirmasi',
            'pengemasan',
            'dalam_perjalanan',
            'diterima',
            'selesai',
            'dibatalkan',
        ],

        init() {
            this.watchSearch();
            this.load();
        },

        get statusCards() {
            const icon = {
                dibatalkan:
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
                menunggu_konfirmasi:
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
                pengemasan:
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
                dalam_perjalanan:
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>',
                diterima:
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
                selesai:
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
            };

            return [
                {
                    id: 'dibatalkan',
                    label: this.t('orders', 'status_dibatalkan', 'Order Dibatalkan', 'Order Cancelled'),
                    desc: this.t(
                        'orders',
                        'status_dibatalkan_desc',
                        'Pesanan telah dibatalkan',
                        'The order has been cancelled',
                    ),
                    activeClass: 'ring-2 ring-red-500 bg-red-50 border-red-500',
                    iconClass: 'text-red-600',
                    icon: icon.dibatalkan,
                },
                {
                    id: 'menunggu_konfirmasi',
                    label: this.t(
                        'orders',
                        'status_menunggu_konfirmasi',
                        'Menunggu Konfirmasi',
                        'Awaiting Confirmation',
                    ),
                    desc: this.t(
                        'orders',
                        'status_menunggu_konfirmasi_desc',
                        'Pesanan baru masuk dan perlu divalidasi',
                        'New order received and needs to be validated',
                    ),
                    activeClass: 'ring-2 ring-amber-500 bg-amber-50 border-amber-500',
                    iconClass: 'text-amber-600',
                    icon: icon.menunggu_konfirmasi,
                },
                {
                    id: 'pengemasan',
                    label: this.t('orders', 'status_pengemasan', 'Pengemasan', 'Packaging'),
                    desc: this.t(
                        'orders',
                        'status_pengemasan_desc',
                        'Produk sedang disiapkan dan dibungkus',
                        'The product is being prepared and packed',
                    ),
                    activeClass: 'ring-2 ring-blue-500 bg-blue-50 border-blue-500',
                    iconClass: 'text-blue-600',
                    icon: icon.pengemasan,
                },
                {
                    id: 'dalam_perjalanan',
                    label: this.t(
                        'orders',
                        'status_dalam_perjalanan',
                        'Dalam Perjalanan',
                        'In Transit',
                    ),
                    desc: this.t(
                        'orders',
                        'status_dalam_perjalanan_desc',
                        'Pesanan telah diserahkan ke kurir logistik',
                        'The order has been handed over to the courier',
                    ),
                    activeClass: 'ring-2 ring-violet-500 bg-violet-50 border-violet-500',
                    iconClass: 'text-violet-600',
                    icon: icon.dalam_perjalanan,
                },
                {
                    id: 'diterima',
                    label: this.t(
                        'orders',
                        'status_diterima',
                        'Diterima Pelanggan',
                        'Received by Customer',
                    ),
                    desc: this.t(
                        'orders',
                        'status_diterima_desc',
                        'Pesanan telah diterima oleh pelanggan dengan baik',
                        'The order has been successfully received by the customer',
                    ),
                    activeClass: 'ring-2 ring-emerald-500 bg-emerald-50 border-emerald-500',
                    iconClass: 'text-emerald-600',
                    icon: icon.diterima,
                },
                {
                    id: 'selesai',
                    label: this.t('orders', 'status_selesai', 'Selesai', 'Completed'),
                    desc: this.t(
                        'orders',
                        'status_selesai_desc',
                        'Pesanan selesai dan ditutup',
                        'The order is completed and closed',
                    ),
                    activeClass: 'ring-2 ring-emerald-500 bg-emerald-50 border-emerald-500',
                    iconClass: 'text-emerald-600',
                    icon: icon.selesai,
                },
            ];
        },

        get paymentCards() {
            return [
                {
                    id: 'success',
                    label: this.t('orders', 'payment_success', 'Pembayaran berhasil', 'Payment successful'),
                    desc: this.t(
                        'orders',
                        'payment_success_desc',
                        'Masuk ke total pendapatan',
                        'Counts toward total revenue',
                    ),
                    badge: 'success',
                },
                {
                    id: 'pending',
                    label: this.t('orders', 'payment_pending', 'Pembayaran pending', 'Payment pending'),
                    desc: this.edit.isCod
                        ? this.t(
                              'orders',
                              'payment_pending_cod_desc',
                              'COD belum dibayar sampai barang tiba, atau sampai dikonfirmasi admin',
                              'COD stays unpaid until delivery, or until admin confirms',
                          )
                        : this.t(
                              'orders',
                              'payment_pending_desc',
                              'Belum masuk total pendapatan',
                              'Not counted in revenue yet',
                          ),
                    badge: 'pending',
                },
                {
                    id: 'cancelled',
                    label: this.t(
                        'orders',
                        'payment_cancelled',
                        'Pembayaran dibatalkan',
                        'Payment cancelled',
                    ),
                    desc: this.t(
                        'orders',
                        'payment_cancelled_desc',
                        'Tidak masuk total pendapatan',
                        'Excluded from revenue',
                    ),
                    badge: 'cancelled',
                },
            ];
        },

        get canSaveEdit() {
            return (
                !!this.edit.id &&
                (this.edit.status !== this.originalEdit.status ||
                    this.edit.payment_status !== this.originalEdit.payment_status)
            );
        },

        statusLabel(s) {
            return fulfillmentStatusConfig(s).label;
        },
        statusClass(s) {
            return fulfillmentStatusConfig(s).class;
        },
        statusBadgeClass(s) {
            const key = String(s || '').toLowerCase();
            switch (key) {
                case 'dibatalkan':
                    return 'bg-red-50 text-red-700 border-red-200';
                case 'menunggu_konfirmasi':
                    return 'bg-yellow-50 text-yellow-700 border-yellow-200';
                case 'pengemasan':
                    return 'bg-blue-50 text-blue-700 border-blue-200';
                case 'dalam_perjalanan':
                    return 'bg-purple-50 text-purple-700 border-purple-200';
                case 'diterima':
                case 'selesai':
                    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                default:
                    return 'bg-gray-50 text-gray-600 border-gray-200';
            }
        },
        statusIcon(s) {
            const key = String(s || '').toLowerCase();
            const icons = {
                dibatalkan:
                    '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
                menunggu_konfirmasi:
                    '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
                pengemasan:
                    '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>',
                dalam_perjalanan:
                    '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>',
                diterima:
                    '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
                selesai:
                    '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
            };
            return (
                icons[key] ||
                '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/></svg>'
            );
        },
        statusDisplay(s) {
            return String(s || '')
                .replace(/_/g, ' ')
                .trim();
        },
        payLabel(s) {
            return paymentStatusLabel(normalizePaymentStatus(s));
        },
        payClass(s) {
            return paymentStatusBadgeClass(normalizePaymentStatus(s));
        },
        isPaid(o) {
            return normalizePaymentStatus(o?.payment_status) === 'success';
        },
        total(o) {
            return formatRupiah(orderGrandTotal(o));
        },
        revenue() {
            return this.filteredItems().reduce((sum, order) => {
                return normalizePaymentStatus(order.payment_status) === 'success'
                    ? sum + orderGrandTotal(order)
                    : sum;
            }, 0);
        },
        customerName(o) {
            return o.user?.name || o.guest_name || this.t('orders', 'guest');
        },
        customerEmail(o) {
            return o.user?.email || o.guest_email || this.t('orders', 'no_email');
        },
        productImage(o) {
            return this.productThumb(o.product);
        },
        /** Next.js orders table uses product.image_1 */
        orderProductImage(o) {
            if (!o?.product) return '';
            return mediaUrl(
                o.product.image_1 ||
                    o.product.gambar_1 ||
                    o.product.image_2 ||
                    o.product.gambar_2 ||
                    o.product.image_produk_belanja ||
                    o.product.image,
            );
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/orders');
                this.items = unwrapList(data);
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        selectStatus(statusId) {
            this.edit.status = statusId;
            if (statusId === 'dibatalkan') {
                this.edit.payment_status = 'cancelled';
                return;
            }
            if (this.edit.isCod) {
                return;
            }
            if (
                this.edit.payment_status === 'pending' &&
                ['pengemasan', 'dalam_perjalanan', 'diterima', 'selesai'].includes(statusId)
            ) {
                this.edit.payment_status = 'success';
            }
        },

        openEdit(o) {
            const status = o.status || 'menunggu_konfirmasi';
            const payment = normalizePaymentStatus(o.payment_status);
            this.edit = {
                id: o.id,
                status,
                payment_status: payment,
                isCod: typeof isCodPayment === 'function' ? isCodPayment(o) : false,
                imageUrl: this.orderProductImage(o) || this.productThumb(o.product) || '',
                productTitle:
                    o.product?.title || this.t('orders', 'no_name', 'Tanpa Nama', 'No Name'),
                customerName: this.customerName(o),
                totalLabel: formatRupiah(orderGrandTotal(o)),
                note: o.note || '',
            };
            this.originalEdit = { status, payment_status: payment };
            this.editOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeEdit() {
            this.editOpen = false;
            document.body.style.overflow = '';
        },

        async saveStatus() {
            if (this.saving || !this.canSaveEdit) return;
            this.saving = true;
            try {
                const fd = new FormData();
                fd.append('status', this.edit.status);
                fd.append('payment_status', this.edit.payment_status);
                fd.append('_method', 'PATCH');
                await adminJson(`/api/orders/${this.edit.id}/status`, {
                    method: 'POST',
                    body: fd,
                    json: false,
                });
                this.notify(
                    this.t(
                        'orders',
                        'status_updated_success',
                        'Status pesanan berhasil diperbarui.',
                        'Order status updated successfully.',
                    ),
                );
                this.closeEdit();
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async remove(id) {
            if (!(await this.confirmDelete('Hapus pesanan ini?'))) return;
            try {
                await adminJson(`/api/orders/${id}`, { method: 'DELETE' });
                this.notify('Pesanan dihapus');
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },
    }));

    /* ---------- USERS ---------- */
    Alpine.data('evomiAdminUsers', () => ({
        ...listMixin(5),
        form: emptyUserForm(),
        avatarFile: null,
        avatarPreview: null,
        viewOpen: false,
        viewUser: null,

        init() {
            this.watchSearch();
            this.load();
        },

        filteredItems() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.items;
            return this.items.filter((u) =>
                `${u.name || ''} ${u.email || ''}`.toLowerCase().includes(q),
            );
        },

        presence(value) {
            if (!value) return this.t('users', 'never');
            return formatDisplayDate(value, this.locale);
        },

        canDelete(u) {
            const me = getAuthUser();
            if (!u) return false;
            if (u.is_admin) return false;
            return String(u.id) !== String(me?.id ?? '');
        },

        deleteTitle(u) {
            const me = getAuthUser();
            if (u?.is_admin) return this.t('users', 'admin_no_delete');
            if (String(u?.id) === String(me?.id ?? '')) return this.t('users', 'self_no_delete');
            return this.t('users', 'delete_user');
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/users');
                this.items = unwrapList(data);
            } catch (e) {
                this.error = e.message || this.t('users', 'load_error');
            } finally {
                this.loading = false;
            }
        },

        openView(u) {
            this.viewUser = u;
            this.viewOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeView() {
            this.viewOpen = false;
            this.viewUser = null;
            document.body.style.overflow = '';
        },

        openEdit(u) {
            this.modalMode = 'edit';
            this.form = {
                id: u.id,
                name: u.name || '',
                email: u.email || '',
                nama_lengkap: u.nama_lengkap || u.name || '',
                alamat_lengkap: u.alamat_lengkap || '',
                phone: u.phone || '',
                is_admin: u.is_admin ? '1' : '0',
                password: '',
            };
            this.avatarFile = null;
            this.avatarPreview = resolveAvatarUrl(u.avatar_profile || u.avatar);
            this.openModal();
        },

        onAvatar(e) {
            const f = e.target.files?.[0];
            if (!f) return;
            this.avatarFile = f;
            this.avatarPreview = URL.createObjectURL(f);
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const fd = new FormData();
                Object.entries(this.form).forEach(([k, v]) => {
                    if (k === 'id') return;
                    if (k === 'password' && !v) return;
                    fd.append(k, v);
                });
                if (this.avatarFile) fd.append('avatar_profile', this.avatarFile);
                await adminJson(`/api/admin/users/${this.form.id}`, {
                    method: 'POST',
                    body: fd,
                    json: false,
                });
                this.notify(this.t('users', 'edit_success'));
                this.closeModal();
                await this.load();
            } catch (e) {
                this.notify(e.message || this.t('users', 'edit_error'), 'error');
            } finally {
                this.saving = false;
            }
        },

        async remove(user) {
            const target = typeof user === 'object' ? user : this.items.find((u) => u.id === user);
            if (!this.canDelete(target)) {
                this.notify(this.deleteTitle(target), 'error');
                return;
            }
            const message = `${this.t('users', 'confirm_delete_desc_1')} "${target.name || target.email}" ${this.t('users', 'confirm_delete_desc_2')}`;
            if (!(await this.confirmDelete(message, this.t('users', 'confirm_delete_title')))) return;
            try {
                await adminJson(`/api/admin/users/${target.id}`, { method: 'DELETE' });
                this.notify(this.t('common', 'delete') + ' — ' + (target.name || target.email));
                await this.load();
            } catch (e) {
                this.notify(e.message || this.t('users', 'delete_error'), 'error');
            }
        },
    }));

    function emptyUserForm() {
        return {
            id: null,
            name: '',
            email: '',
            nama_lengkap: '',
            alamat_lengkap: '',
            phone: '',
            is_admin: '0',
            password: '',
        };
    }

    /* ---------- Shared CMS / article font options (Next.js cmsFonts parity) ---------- */
    const FONT_FAMILY_OPTIONS = [
        { value: 'nohemi', label: 'Nohemi (project)', group: 'project' },
        { value: 'parkinsans', label: 'Parkinsans (project)', group: 'project' },
        { value: 'syne', label: 'Syne (project)', group: 'project' },
        { value: 'heavy', label: '8-Heavy (project)', group: 'project' },
        { value: 'arial', label: 'Arial', group: 'system' },
        { value: 'helvetica', label: 'Helvetica', group: 'system' },
        { value: 'georgia', label: 'Georgia', group: 'system' },
        { value: 'times', label: 'Times New Roman', group: 'system' },
        { value: 'verdana', label: 'Verdana', group: 'system' },
        { value: 'tahoma', label: 'Tahoma', group: 'system' },
        { value: 'courier', label: 'Courier New', group: 'system' },
        { value: 'system', label: 'System UI', group: 'system' },
    ];
    const FONT_WEIGHT_OPTIONS = [
        { value: '300', label: '300 — Light' },
        { value: '400', label: '400 — Regular' },
        { value: '500', label: '500 — Medium' },
        { value: '600', label: '600 — SemiBold' },
        { value: '700', label: '700 — Bold' },
        { value: '800', label: '800 — ExtraBold' },
        { value: '900', label: '900 — Black' },
    ];
    const FONT_STYLE_OPTIONS = [
        { value: 'normal', label: 'Normal' },
        { value: 'italic', label: 'Italic' },
    ];
    const FONT_SIZE_OPTIONS = [
        { value: '14', label: '14px' },
        { value: '16', label: '16px' },
        { value: '17', label: '17px' },
        { value: '18', label: '18px' },
        { value: '20', label: '20px' },
        { value: '22', label: '22px' },
        { value: '24', label: '24px' },
        { value: '28', label: '28px' },
        { value: '32', label: '32px' },
        { value: '36', label: '36px' },
        { value: '40', label: '40px' },
        { value: '48', label: '48px' },
    ];
    const HEADING_LEVEL_OPTIONS = [
        { value: 'h1', label: 'H1' },
        { value: 'h2', label: 'H2' },
        { value: 'h3', label: 'H3' },
        { value: 'h4', label: 'H4' },
        { value: 'h5', label: 'H5' },
        { value: 'h6', label: 'H6' },
    ];
    /* Excerpt & content may also be rendered as plain text ("normal"). */
    const BLOCK_LEVEL_OPTIONS = [{ value: 'normal', label: 'Normal' }, ...HEADING_LEVEL_OPTIONS];
    const BLOCK_LEVELS = BLOCK_LEVEL_OPTIONS.map((o) => o.value);

    /** Level of a body field, falling back to plain text. */
    function blockLevel(raw) {
        return BLOCK_LEVELS.includes(raw) ? raw : 'normal';
    }

    /* Per-level article heading defaults — mirrors App\Support\ArticleContent::defaults(). */
    const HEADING_FONT_DEFAULTS = {
        h1: { font_family: 'nohemi', font_weight: '700', font_style: 'normal', font_size: '32' },
        h2: { font_family: 'nohemi', font_weight: '700', font_style: 'normal', font_size: '28' },
        h3: { font_family: 'nohemi', font_weight: '600', font_style: 'normal', font_size: '22' },
        h4: { font_family: 'parkinsans', font_weight: '600', font_style: 'normal', font_size: '20' },
        h5: { font_family: 'parkinsans', font_weight: '600', font_style: 'normal', font_size: '18' },
        h6: { font_family: 'parkinsans', font_weight: '600', font_style: 'normal', font_size: '16' },
    };
    const HEADING_LEVELS = Object.keys(HEADING_FONT_DEFAULTS);
    const HEADING_FONT_KEYS = ['font_family', 'font_weight', 'font_style', 'font_size'];

    /**
     * Heading typography lives in the articles.heading_fonts JSON column but is
     * edited as flat form keys (h2_font_weight, …) so it can reuse the shared
     * admin-article-typography partial.
     */
    function headingFontsToForm(raw) {
        const source = raw && typeof raw === 'object' ? raw : {};
        const out = {};
        for (const level of HEADING_LEVELS) {
            const given =
                source[level] && typeof source[level] === 'object' ? source[level] : {};
            for (const key of HEADING_FONT_KEYS) {
                const value = String(given[key] ?? '').trim();
                out[level + '_' + key] = value || HEADING_FONT_DEFAULTS[level][key];
            }
        }
        return out;
    }

    function isHeadingFormKey(key) {
        return /^h[1-6]_font_(family|weight|style|size)$/.test(key || '');
    }

    const FONT_FAMILY_CSS = {
        nohemi: "var(--font-nohemi), 'Nohemi', sans-serif",
        parkinsans: "var(--font-parkinsans), 'Parkinsans', sans-serif",
        syne: "var(--font-syne), 'Syne', sans-serif",
        heavy: "var(--font-heavy), '8-Heavy', sans-serif",
        arial: 'Arial, Helvetica, sans-serif',
        helvetica: 'Helvetica, Arial, sans-serif',
        georgia: "Georgia, 'Times New Roman', serif",
        times: "'Times New Roman', Times, serif",
        verdana: 'Verdana, Geneva, sans-serif',
        tahoma: 'Tahoma, Geneva, sans-serif',
        courier: "'Courier New', Courier, monospace",
        system: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
    };

    function resolveFontFamilyCss(raw) {
        const key = String(raw || '')
            .trim()
            .toLowerCase();
        if (FONT_FAMILY_CSS[key]) return FONT_FAMILY_CSS[key];
        if (key.includes(',') || key.startsWith('var(')) return String(raw).trim();
        return FONT_FAMILY_CSS.nohemi;
    }

    /* ---------- ARTICLES ---------- */
    Alpine.data('evomiAdminArticles', () => ({
        ...listMixin(6),
        form: emptyArticleForm(),
        imageFile: null,
        imagePreview: null,
        existingImage: null,
        uploadProgress: 0,
        fontSelectOpen: null,
        fontWeightOptions: FONT_WEIGHT_OPTIONS,
        fontStyleOptions: FONT_STYLE_OPTIONS,
        fontSizeOptions: FONT_SIZE_OPTIONS,
        headingLevelOptions: HEADING_LEVEL_OPTIONS,
        blockLevelOptions: BLOCK_LEVEL_OPTIONS,
        headingLevels: HEADING_LEVELS,
        fontFamilyGroups: [
            {
                key: 'project',
                label: 'Font Project (Next.js)',
                options: FONT_FAMILY_OPTIONS.filter((o) => o.group === 'project'),
            },
            {
                key: 'system',
                label: 'Font Sistem',
                options: FONT_FAMILY_OPTIONS.filter((o) => o.group === 'system'),
            },
        ],

        init() {
            this.watchSearch();
            this.load();
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/articles');
                this.items = unwrapList(data);
            } catch (e) {
                this.error = e.message || this.t('articles', 'load_error');
            } finally {
                this.loading = false;
            }
        },

        openAdd() {
            this.modalMode = 'add';
            this.form = emptyArticleForm();
            this.form.published_at = new Date().toISOString().slice(0, 10);
            this.imageFile = null;
            this.existingImage = null;
            this.imagePreview = null;
            this.saving = false;
            this.uploadProgress = 0;
            this.fontSelectOpen = null;
            this.openModal();
        },

        openEdit(a) {
            this.modalMode = 'edit';
            this.form = {
                ...emptyArticleForm(),
                id: a.id,
                title: a.title || '',
                title_en: a.title_en || '',
                slug: a.slug || '',
                excerpt: a.excerpt || '',
                excerpt_en: a.excerpt_en || '',
                content: a.content || '',
                content_en: a.content_en || '',
                category: a.category || '',
                author: a.author || 'Evomi',
                is_published: a.is_published ? '1' : '0',
                published_at: (a.published_at || '').slice(0, 10),
                title_font_family: a.title_font_family || 'nohemi',
                title_font_weight: a.title_font_weight || '700',
                title_font_style: a.title_font_style || 'normal',
                title_font_size: a.title_font_size || '40',
                excerpt_font_family: a.excerpt_font_family || 'parkinsans',
                excerpt_font_weight: a.excerpt_font_weight || '400',
                excerpt_font_style: a.excerpt_font_style || 'normal',
                excerpt_font_size: a.excerpt_font_size || '18',
                content_font_family: a.content_font_family || 'parkinsans',
                content_font_weight: a.content_font_weight || '400',
                content_font_style: a.content_font_style || 'normal',
                content_font_size: a.content_font_size || '17',
                title_heading_level: HEADING_LEVELS.includes(a.title_heading_level)
                    ? a.title_heading_level
                    : 'h1',
                excerpt_heading_level: blockLevel(a.excerpt_heading_level),
                content_heading_level: blockLevel(a.content_heading_level),
                ...headingFontsToForm(a.heading_fonts),
            };
            this.imageFile = null;
            this.existingImage = a.image_url || a.image || null;
            this.imagePreview = mediaUrl(this.existingImage);
            this.saving = false;
            this.uploadProgress = 0;
            this.fontSelectOpen = null;
            this.openModal();
        },

        onImage(e) {
            const f = e.target.files?.[0];
            if (!f) return;
            if (f.size > 10 * 1024 * 1024) {
                this.notify(this.t('articles', 'image_too_large'), 'error');
                e.target.value = '';
                return;
            }
            this.imageFile = f;
            this.imagePreview = URL.createObjectURL(f);
        },

        resetImage() {
            this.imageFile = null;
            this.imagePreview = mediaUrl(this.existingImage);
        },

        closeArticleModal() {
            if (this.saving) return;
            this.fontSelectOpen = null;
            this.closeModal();
            this.uploadProgress = 0;
        },

        toggleFontSelect(key) {
            this.fontSelectOpen = this.fontSelectOpen === key ? null : key;
        },

        closeFontSelect(key) {
            if (this.fontSelectOpen === key) this.fontSelectOpen = null;
        },

        pickFontSelect(key, value) {
            this.form[key] = String(value);
            this.fontSelectOpen = null;
        },

        fontSelectLabel(key, kind) {
            const value = String(this.form[key] ?? '');
            let opts = [];
            if (kind === 'family') opts = FONT_FAMILY_OPTIONS;
            else if (kind === 'weight') opts = FONT_WEIGHT_OPTIONS;
            else if (kind === 'style') opts = FONT_STYLE_OPTIONS;
            else if (kind === 'size') opts = FONT_SIZE_OPTIONS;
            else if (kind === 'level') opts = HEADING_LEVEL_OPTIONS;
            else if (kind === 'block_level') opts = BLOCK_LEVEL_OPTIONS;
            const hit = opts.find((o) => String(o.value) === value);
            return hit?.label || value || 'Pilih…';
        },

        fontOptionStyle(value, kind) {
            const v = String(value ?? '');
            if (kind === 'family') return { fontFamily: resolveFontFamilyCss(v || 'nohemi') };
            if (kind === 'weight') return { fontWeight: Number(v) || 400 };
            if (kind === 'style') return { fontStyle: v === 'italic' ? 'italic' : 'normal' };
            return {};
        },

        isHeadingPrefix(prefix) {
            return HEADING_LEVELS.includes(prefix);
        },

        typographyLabel(prefix) {
            if (this.isHeadingPrefix(prefix)) {
                return this.t('articles', 'heading_in_content') + ' ' + prefix.toUpperCase();
            }
            const labels = {
                title: this.t('articles', 'typography_title'),
                excerpt: this.t('articles', 'typography_excerpt'),
                content: this.t('articles', 'typography_content'),
            };
            return labels[prefix] || prefix;
        },

        /** First heading of this level inside the content, else a sample line. */
        headingSample(prefix) {
            const level = Number(prefix.slice(1));
            const re = new RegExp('^\\s*#{' + level + '}(?!#)\\s+(\\S.*)$', 'm');
            const hit = re.exec(String(this.form.content || this.form.content_en || ''));
            if (hit) return hit[1].trim();
            return this.t('articles', 'heading_sample') + ' ' + level;
        },

        typographyPreview(prefix) {
            if (this.isHeadingPrefix(prefix)) return this.headingSample(prefix);
            if (prefix === 'title') return this.form.title || this.form.title_en || '—';
            if (prefix === 'excerpt') return this.form.excerpt || this.form.excerpt_en || '—';
            return String(this.form.content || this.form.content_en || '—').slice(0, 160);
        },

        typographyPreviewStyle(prefix) {
            const family = this.form[`${prefix}_font_family`];
            const size = Math.min(Number(this.form[`${prefix}_font_size`]) || 16, 48);

            return {
                fontFamily: resolveFontFamilyCss(family || 'nohemi'),
                fontWeight: this.form[`${prefix}_font_weight`] || '400',
                fontStyle: this.form[`${prefix}_font_style`] || 'normal',
                fontSize: `${size}px`,
                lineHeight: prefix === 'title' || this.isHeadingPrefix(prefix) ? '1.28' : '1.55',
            };
        },

        uploadHeadline() {
            return this.uploadProgress >= 100
                ? this.t('articles', 'upload_finishing')
                : this.t('articles', 'upload_in_progress');
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            this.uploadProgress = this.imageFile ? 0 : 15;
            try {
                const fd = new FormData();
                Object.entries(this.form).forEach(([k, v]) => {
                    if (k === 'id') return;
                    if (isHeadingFormKey(k)) {
                        const [level, ...rest] = k.split('_');
                        fd.append('heading_fonts[' + level + '][' + rest.join('_') + ']', v ?? '');
                        return;
                    }
                    fd.append(k, v ?? '');
                });
                if (this.imageFile) fd.append('image', this.imageFile);
                const url =
                    this.modalMode === 'add'
                        ? '/api/admin/articles'
                        : `/api/admin/articles/${this.form.id}`;
                await uploadWithProgress(url, fd, {
                    onProgress: (percent) => {
                        this.uploadProgress = this.imageFile
                            ? percent
                            : Math.max(15, percent);
                    },
                });
                this.notify(
                    this.modalMode === 'add'
                        ? this.t('articles', 'created')
                        : this.t('articles', 'updated'),
                );
                this.saving = false;
                this.uploadProgress = 0;
                this.closeArticleModal();
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
                this.uploadProgress = 0;
            }
        },

        async remove(id) {
            if (
                !(await this.confirmDelete(
                    this.t('articles', 'delete_message'),
                    this.t('articles', 'delete_title'),
                ))
            ) {
                return;
            }
            try {
                await adminJson(`/api/admin/articles/${id}`, { method: 'DELETE' });
                this.notify(this.t('articles', 'deleted'));
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },
    }));

    function emptyArticleForm() {
        return {
            id: null,
            title: '',
            title_en: '',
            slug: '',
            excerpt: '',
            excerpt_en: '',
            content: '',
            content_en: '',
            category: 'Jurnal',
            author: 'Evomi',
            is_published: '1',
            published_at: '',
            title_font_family: 'nohemi',
            title_font_weight: '700',
            title_font_style: 'normal',
            title_font_size: '40',
            excerpt_font_family: 'parkinsans',
            excerpt_font_weight: '400',
            excerpt_font_style: 'normal',
            excerpt_font_size: '18',
            content_font_family: 'parkinsans',
            content_font_weight: '400',
            content_font_style: 'normal',
            content_font_size: '17',
            title_heading_level: 'h1',
            excerpt_heading_level: 'normal',
            content_heading_level: 'normal',
            ...headingFontsToForm(null),
        };
    }

    /* ---------- PROMOS ---------- */
    Alpine.data('evomiAdminPromos', () => ({
        ...listMixin(5),
        form: { id: null, harga_promo: '', persentase_promo: '', tanggal_berlaku_promo: '', tanggal_berakhir_promo: '' },

        init() {
            this.watchSearch();
            this.load();
        },

        promoValueLabel(promo) {
            const percent = Number(promo?.persentase_promo) || 0;
            if (percent > 0) return `${percent}%`;
            return this.formatRupiah(promo?.harga_promo);
        },

        isActive(promo) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const startRaw = (promo.tanggal_berlaku_promo || '').slice(0, 10);
            const endRaw = (promo.tanggal_berakhir_promo || '').slice(0, 10);
            const start = startRaw ? new Date(startRaw) : null;
            const end = endRaw ? new Date(endRaw) : null;
            if (start) start.setHours(0, 0, 0, 0);
            if (end) end.setHours(0, 0, 0, 0);
            if (start && today < start) return false;
            if (end && today > end) return false;
            return Boolean(start);
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/promos');
                this.items = unwrapList(data);
            } catch (e) {
                this.error = e.message || this.t('promos', 'load_error');
            } finally {
                this.loading = false;
            }
        },

        openAdd() {
            this.modalMode = 'add';
            this.form = { id: null, harga_promo: '', persentase_promo: '', tanggal_berlaku_promo: '', tanggal_berakhir_promo: '' };
            this.openModal();
        },

        openEdit(p) {
            this.modalMode = 'edit';
            this.form = {
                id: p.id,
                harga_promo: p.harga_promo ?? '',
                persentase_promo: p.persentase_promo ?? '',
                tanggal_berlaku_promo: (p.tanggal_berlaku_promo || '').slice(0, 10),
                tanggal_berakhir_promo: (p.tanggal_berakhir_promo || '').slice(0, 10),
            };
            this.openModal();
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const body = {
                    harga_promo: Number(this.form.harga_promo) || 0,
                    persentase_promo:
                        this.form.persentase_promo === '' || this.form.persentase_promo == null
                            ? null
                            : Number(this.form.persentase_promo),
                    tanggal_berlaku_promo: this.form.tanggal_berlaku_promo,
                    tanggal_berakhir_promo: this.form.tanggal_berakhir_promo,
                };
                if (this.modalMode === 'add') {
                    await adminJson('/api/admin/promos', { method: 'POST', body });
                    this.notify(this.t('promos', 'created'));
                } else {
                    await adminJson(`/api/admin/promos/${this.form.id}`, { method: 'PUT', body });
                    this.notify(this.t('promos', 'updated'));
                }
                this.closeModal();
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async remove(id) {
            if (
                !(await this.confirmDelete(
                    this.t('promos', 'delete_desc'),
                    this.t('promos', 'delete_title'),
                ))
            ) {
                return;
            }
            try {
                await adminJson(`/api/admin/promos/${id}`, { method: 'DELETE' });
                this.notify(this.t('promos', 'deleted'));
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },
    }));

    /* ---------- FREE SHIPPING TOGGLE ---------- */
    Alpine.data('evomiAdminFreeShipping', () => ({
        enabled: false,
        loading: true,
        saving: false,

        init() {
            this.load();
        },

        async load() {
            this.loading = true;
            try {
                const data = await adminJson('/api/admin/shipping-settings');
                const settings = data?.data || data || {};
                this.enabled = Boolean(settings.free_shipping);
            } catch {
                /* ignore */
            } finally {
                this.loading = false;
            }
        },

        async toggle() {
            this.saving = true;
            try {
                await adminJson('/api/admin/shipping-settings', {
                    method: 'PUT',
                    body: { free_shipping: this.enabled },
                });
            } catch (e) {
                this.enabled = !this.enabled;
                if (typeof this.notify === 'function') this.notify(e.message || 'Gagal menyimpan', 'error');
            } finally {
                this.saving = false;
            }
        },
    }));

    /* ---------- KURIRS ---------- */
    Alpine.data('evomiAdminKurirs', () => ({
        ...listMixin(5),
        form: emptyKurirForm(),

        init() {
            this.watchSearch();
            this.load();
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/kurirs?all=1');
                this.items = unwrapList(data);
            } catch (e) {
                this.error = e.message || this.t('kurirs', 'load_error');
            } finally {
                this.loading = false;
            }
        },

        openAdd() {
            this.modalMode = 'add';
            this.form = emptyKurirForm();
            this.openModal();
        },

        openEdit(k) {
            this.modalMode = 'edit';
            this.form = {
                id: k.id,
                nama: k.nama || '',
                jenis: k.jenis || '',
                harga: k.harga ?? '',
                destinasi: k.destinasi || '',
                estimasi_hari: k.estimasi_hari ?? 3,
                is_active: k.is_active ? true : false,
            };
            this.openModal();
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const body = {
                    nama: this.form.nama,
                    jenis: this.form.jenis,
                    harga: Number(this.form.harga),
                    destinasi: this.form.destinasi,
                    estimasi_hari: Number(this.form.estimasi_hari),
                    is_active: !!this.form.is_active,
                };
                if (this.modalMode === 'add') {
                    await adminJson('/api/admin/kurirs', { method: 'POST', body });
                    this.notify(this.t('kurirs', 'created'));
                } else {
                    await adminJson(`/api/admin/kurirs/${this.form.id}`, { method: 'PUT', body });
                    this.notify(this.t('kurirs', 'updated'));
                }
                this.closeModal();
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async remove(id) {
            if (
                !(await this.confirmDelete(
                    this.t('kurirs', 'delete_message'),
                    this.t('kurirs', 'delete_title'),
                ))
            ) {
                return;
            }
            try {
                await adminJson(`/api/admin/kurirs/${id}`, { method: 'DELETE' });
                this.notify(this.t('kurirs', 'deleted'));
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },
    }));

    Alpine.data('evomiAdminKurirTarifs', () => ({
        ...listMixin(8),
        kurirOptions: [],
        form: emptyKurirTarifForm(),

        init() {
            this.watchSearch();
            this.load();
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const [tarifData, kurirData] = await Promise.all([
                    adminJson('/api/admin/kurir-tarifs'),
                    adminJson('/api/admin/kurirs?all=1'),
                ]);
                this.items = unwrapList(tarifData);
                this.kurirOptions = unwrapList(kurirData);
            } catch (e) {
                this.error = e.message || this.t('kurirs', 'load_error');
            } finally {
                this.loading = false;
            }
        },

        openAdd() {
            this.modalMode = 'add';
            this.form = emptyKurirTarifForm();
            this.openModal();
        },

        openEdit(t) {
            this.modalMode = 'edit';
            this.form = {
                id: t.id,
                kurir_id: t.kurir_id ?? t.kurirId ?? '',
                kota_asal: t.kota_asal ?? 'Cisauk',
                kota_tujuan: t.kota_tujuan ?? '',
                berat_min_gram: t.berat_min_gram ?? 0,
                berat_max_gram: t.berat_max_gram ?? 0,
                harga: t.harga ?? '',
                estimasi_hari: t.estimasi_hari ?? 3,
                is_active: t.is_active ? true : false,
            };
            this.openModal();
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const body = {
                    kurir_id: Number(this.form.kurir_id),
                    kota_asal: this.form.kota_asal,
                    kota_tujuan: this.form.kota_tujuan,
                    berat_min_gram: Number(this.form.berat_min_gram),
                    berat_max_gram: Number(this.form.berat_max_gram),
                    harga: Number(this.form.harga),
                    estimasi_hari: Number(this.form.estimasi_hari),
                    is_active: !!this.form.is_active,
                };

                if (this.modalMode === 'add') {
                    await adminJson('/api/admin/kurir-tarifs', { method: 'POST', body });
                    this.notify('Tarif berhasil ditambahkan', 'success');
                } else {
                    await adminJson(`/api/admin/kurir-tarifs/${this.form.id}`, { method: 'PUT', body });
                    this.notify('Tarif berhasil diperbarui', 'success');
                }

                this.closeModal();
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async remove(id) {
            if (
                !(await this.confirmDelete(
                    'Tarif ongkir ini akan dihapus permanen.',
                    'Hapus Tarif Ongkir?',
                ))
            ) {
                return;
            }

            try {
                await adminJson(`/api/admin/kurir-tarifs/${id}`, { method: 'DELETE' });
                this.notify('Tarif berhasil dihapus', 'success');
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },
    }));

    function emptyKurirForm() {
        return {
            id: null,
            nama: '',
            jenis: 'reguler',
            harga: '',
            destinasi: 'Seluruh Indonesia',
            estimasi_hari: 3,
            is_active: true,
        };
    }

    function emptyKurirTarifForm() {
        return {
            id: null,
            kurir_id: '',
            kota_asal: 'Cisauk',
            kota_tujuan: '',
            berat_min_gram: 0,
            berat_max_gram: 250,
            harga: '',
            estimasi_hari: 3,
            is_active: true,
        };
    }

    /* ---------- PAYMENT ---------- */
    Alpine.data('evomiAdminPayment', () => ({
        ...i18nMixin(),
        loading: true,
        saving: false,
        error: '',
        notice: null,
        noticeTimer: null,
        show: { midtransServer: false, xenditSecret: false },
        configured: { midtrans: false, xendit: false },
        providers: [
            { id: 'manual', title: 'Manual', hint: 'COD' },
            { id: 'midtrans', title: 'Midtrans', hint: 'QRIS · Transfer Bank' },
            { id: 'xendit', title: 'Xendit', hint: 'QRIS · Transfer Bank' },
        ],
        form: {
            provider: 'manual',
            midtrans: { is_production: false, merchant_id: '', client_key: '', server_key: '' },
            xendit: { is_production: false, merchant_id: '', callback_token: '', secret_key: '' },
        },

        async init() {
            this.watchLocale();
            await this.load();
        },

        showNotice(type, text) {
            this.notice = { type, text };
            if (this.noticeTimer) clearTimeout(this.noticeTimer);
            this.noticeTimer = setTimeout(() => {
                this.notice = null;
            }, 3500);
        },

        providerHint(opt) {
            if (opt.id === 'manual') return opt.hint;
            const saved = opt.id === 'midtrans' ? this.configured.midtrans : this.configured.xendit;
            const status = saved
                ? this.t('payment', 'saved_badge', 'tersimpan', 'saved')
                : this.t('payment', 'empty_badge', 'belum diisi', 'not set');
            return `${opt.hint} · ${status}`;
        },

        applySettings(d) {
            this.form = {
                provider: d.provider || 'manual',
                midtrans: {
                    is_production: !!(d.midtrans?.is_production),
                    merchant_id: d.midtrans?.merchant_id || '',
                    client_key: d.midtrans?.client_key || '',
                    server_key: d.midtrans?.server_key || '',
                },
                xendit: {
                    is_production: !!(d.xendit?.is_production),
                    merchant_id: d.xendit?.merchant_id || '',
                    callback_token: d.xendit?.callback_token || '',
                    secret_key: d.xendit?.secret_key || '',
                },
            };
            this.configured = {
                midtrans: !!(d.midtrans?.configured ?? (d.midtrans?.server_key && d.midtrans?.client_key)),
                xendit: !!(d.xendit?.configured ?? (d.xendit?.secret_key && d.xendit?.callback_token)),
            };
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/payment-settings');
                this.applySettings(unwrapData(data) || {});
            } catch (e) {
                this.error = e.message || this.t('payment', 'load_error');
                this.showNotice('error', this.error);
            } finally {
                this.loading = false;
            }
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const trimOrNull = (v) => {
                    const s = String(v ?? '').trim();
                    return s === '' ? null : s;
                };
                const payload = {
                    provider: this.form.provider,
                    midtrans: {
                        is_production: !!this.form.midtrans.is_production,
                        merchant_id: trimOrNull(this.form.midtrans.merchant_id),
                        client_key: trimOrNull(this.form.midtrans.client_key),
                        server_key: trimOrNull(this.form.midtrans.server_key),
                    },
                    xendit: {
                        is_production: !!this.form.xendit.is_production,
                        merchant_id: trimOrNull(this.form.xendit.merchant_id),
                        callback_token: trimOrNull(this.form.xendit.callback_token),
                        secret_key: trimOrNull(this.form.xendit.secret_key),
                    },
                };
                const data = await adminJson('/api/admin/payment-settings', {
                    method: 'PUT',
                    body: payload,
                });
                this.applySettings(unwrapData(data) || this.form);
                this.showNotice('success', this.t('payment', 'saved'));
                Alpine.store('adminUi').notify(this.t('payment', 'saved'));
            } catch (e) {
                this.showNotice('error', e.message);
                Alpine.store('adminUi').notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },
    }));

    /* ---------- QUIZ ---------- */
    const QUIZ_PERSONALITIES = ['prestige', 'peaceful_calm', 'rebel_brave', 'sweet_shy'];
    const QUIZ_PERSONALITY_LABELS = {
        prestige: 'Purpose Prestige',
        purpose_prestige: 'Purpose Prestige',
        peaceful_calm: 'Peaceful Calm',
        rebel_brave: 'Rebel Brave',
        sweet_shy: 'Sweet Shy',
    };

    Alpine.data('evomiAdminQuiz', () => ({
        ...listMixin(1000),
        tab: 'questions',
        scores: [],
        scoreSearch: '',
        form: emptyQuizForm(),
        personalities: QUIZ_PERSONALITIES,
        personalityFields: [
            { key: 'total_prestige', label: 'Purpose Prestige' },
            { key: 'total_peaceful_calm', label: 'Peaceful Calm' },
            { key: 'total_rebel_brave', label: 'Rebel Brave' },
            { key: 'total_sweet_shy', label: 'Sweet Shy' },
        ],
        scoreDetailOpen: false,
        scoreDetail: null,
        scoreEditOpen: false,
        scoreForm: {
            id: null,
            total_prestige: 0,
            total_peaceful_calm: 0,
            total_rebel_brave: 0,
            total_sweet_shy: 0,
            dominant_personality: 'prestige',
        },

        init() {
            this.watchSearch();
            this.load();
        },

        setTab(tab) {
            this.tab = tab;
            this.page = 1;
        },

        personalityLabel(key) {
            if (!key) return '-';
            return QUIZ_PERSONALITY_LABELS[key] || String(key);
        },

        localizedQuestionText(item) {
            if (!item) return '';
            if (this.locale === 'en' && String(item.question_text_en || '').trim()) {
                return item.question_text_en;
            }
            return item.question_text || '';
        },

        localizedOptionText(item) {
            if (!item) return '';
            if (this.locale === 'en' && String(item.option_text_en || '').trim()) {
                return item.option_text_en;
            }
            return item.option_text || '';
        },

        filteredItems() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.items;
            return this.items.filter((row) => {
                const idText = (row.question_text || '').toLowerCase();
                const enText = (row.question_text_en || '').toLowerCase();
                return idText.includes(q) || enText.includes(q);
            });
        },

        filteredScores() {
            const q = this.scoreSearch.trim().toLowerCase();
            if (!q) return this.scores;
            return this.scores.filter((s) =>
                `${s.user?.name || ''} ${s.user?.email || ''} ${s.dominant_personality || ''} ${this.personalityLabel(s.dominant_personality)}`
                    .toLowerCase()
                    .includes(q),
            );
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const [q, s] = await Promise.all([
                    adminJson('/api/admin/quiz/questions'),
                    adminJson('/api/admin/quiz/scores'),
                ]);
                this.items = unwrapList(q);
                this.scores = unwrapList(s);
            } catch (e) {
                this.error = e.message || this.t('quiz', 'load_error');
            } finally {
                this.loading = false;
            }
        },

        async openScoreDetail(score) {
            this.scoreDetail = score;
            this.scoreDetailOpen = true;
            document.body.style.overflow = 'hidden';
            try {
                const data = await adminJson(`/api/admin/quiz/scores/${score.id}`);
                this.scoreDetail = unwrapData(data) || score;
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },

        closeScoreDetail() {
            this.scoreDetailOpen = false;
            this.scoreDetail = null;
            document.body.style.overflow = '';
        },

        openScoreEdit(score) {
            this.scoreForm = {
                id: score.id,
                total_prestige: score.total_prestige ?? 0,
                total_peaceful_calm: score.total_peaceful_calm ?? 0,
                total_rebel_brave: score.total_rebel_brave ?? 0,
                total_sweet_shy: score.total_sweet_shy ?? 0,
                dominant_personality: score.dominant_personality || 'prestige',
            };
            this.scoreEditOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeScoreEdit() {
            this.scoreEditOpen = false;
            document.body.style.overflow = '';
        },

        async saveScore() {
            if (this.saving) return;
            this.saving = true;
            try {
                await adminJson(`/api/admin/quiz/scores/${this.scoreForm.id}`, {
                    method: 'PUT',
                    body: {
                        total_prestige: Number(this.scoreForm.total_prestige) || 0,
                        total_peaceful_calm: Number(this.scoreForm.total_peaceful_calm) || 0,
                        total_rebel_brave: Number(this.scoreForm.total_rebel_brave) || 0,
                        total_sweet_shy: Number(this.scoreForm.total_sweet_shy) || 0,
                        dominant_personality: this.scoreForm.dominant_personality,
                    },
                });
                this.notify(this.t('quiz', 'score_updated_success'));
                this.closeScoreEdit();
                await this.load();
            } catch (e) {
                this.notify(e.message || this.t('quiz', 'score_update_error'), 'error');
            } finally {
                this.saving = false;
            }
        },

        openAdd() {
            this.modalMode = 'add';
            this.form = emptyQuizForm();
            this.openModal();
        },

        openEdit(q) {
            this.modalMode = 'edit';
            this.form = {
                id: q.id,
                question_text: q.question_text || '',
                question_text_en: q.question_text_en || '',
                options: (q.options || []).map((o) => ({
                    id: o.id,
                    option_text: o.option_text || '',
                    option_text_en: o.option_text_en || '',
                    prestige_score: o.prestige_score ?? 0,
                    peaceful_calm_score: o.peaceful_calm_score ?? 0,
                    rebel_brave_score: o.rebel_brave_score ?? 0,
                    sweet_shy_score: o.sweet_shy_score ?? 0,
                })),
            };
            while (this.form.options.length < 2) {
                this.form.options.push(emptyOption());
            }
            this.openModal();
        },

        addOption() {
            this.form.options.push(emptyOption());
        },

        removeOption(i) {
            if (this.form.options.length <= 2) return;
            this.form.options.splice(i, 1);
        },

        async save() {
            if (this.saving) return;
            if (!this.form.question_text.trim()) {
                this.notify(this.t('quiz', 'validation_question_required'), 'error');
                return;
            }
            if (this.form.options.length < 2) {
                this.notify(this.t('quiz', 'validation_min_options'), 'error');
                return;
            }
            if (this.form.options.some((o) => !String(o.option_text || '').trim())) {
                this.notify(this.t('quiz', 'validation_options_required'), 'error');
                return;
            }
            this.saving = true;
            try {
                const body = {
                    question_text: this.form.question_text,
                    question_text_en: this.form.question_text_en,
                    options: this.form.options,
                };
                if (this.modalMode === 'add') {
                    await adminJson('/api/admin/quiz/questions', { method: 'POST', body });
                    this.notify(this.t('quiz', 'added_success'));
                } else {
                    await adminJson(`/api/admin/quiz/questions/${this.form.id}`, {
                        method: 'PUT',
                        body,
                    });
                    this.notify(this.t('quiz', 'updated_success'));
                }
                this.closeModal();
                await this.load();
            } catch (e) {
                this.notify(e.message || this.t('quiz', 'save_error'), 'error');
            } finally {
                this.saving = false;
            }
        },

        async remove(id) {
            if (!(await this.confirmDelete(this.t('quiz', 'confirm_delete_desc')))) return;
            try {
                await adminJson(`/api/admin/quiz/questions/${id}`, { method: 'DELETE' });
                this.notify(this.t('quiz', 'deleted_success'));
                await this.load();
            } catch (e) {
                this.notify(e.message || this.t('quiz', 'delete_error'), 'error');
            }
        },

        async removeScore(id) {
            if (!(await this.confirmDelete(this.t('quiz', 'confirm_delete_desc')))) return;
            try {
                await adminJson(`/api/admin/quiz/scores/${id}`, { method: 'DELETE' });
                this.notify(this.t('quiz', 'deleted_success'));
                await this.load();
            } catch (e) {
                this.notify(e.message || this.t('quiz', 'delete_error'), 'error');
            }
        },
    }));

    function emptyOption() {
        return {
            option_text: '',
            option_text_en: '',
            prestige_score: 0,
            peaceful_calm_score: 0,
            rebel_brave_score: 0,
            sweet_shy_score: 0,
        };
    }

    function emptyQuizForm() {
        return {
            id: null,
            question_text: '',
            question_text_en: '',
            options: [emptyOption(), emptyOption(), emptyOption(), emptyOption()],
        };
    }

    /* ---------- TRACKINGS ---------- */
    Alpine.data('evomiAdminTrackings', () => ({
        ...listMixin(5),
        kurirs: [],
        form: emptyTrackingForm(),

        init() {
            this.watchSearch();
            this.load();
        },

        async load() {
            this.loading = true;
            try {
                const [t, k] = await Promise.all([
                    adminJson('/api/admin/trackings'),
                    adminJson('/api/kurirs'),
                ]);
                this.items = unwrapList(t);
                this.kurirs = unwrapList(k);
            } catch (e) {
                this.error = e.message || this.t('trackings', 'load_error');
            } finally {
                this.loading = false;
            }
        },

        openEdit(row) {
            this.modalMode = 'edit';
            const timeline = Array.isArray(row.timeline) ? row.timeline : [];
            this.form = {
                order_id: row.order_id,
                tracking_number: row.tracking_number || '',
                status: row.status || '',
                estimated_delivery: (row.estimated_delivery || '').slice(0, 10),
                courier: row.courier || '',
                recipient_name: row.recipient_name || '',
                recipient_phone: row.recipient_phone || '',
                recipient_address: row.recipient_address || '',
                timeline: timeline.length
                    ? timeline.map((x) => ({
                          status: x.status || '',
                          time: x.time || x.date || '',
                          description: x.description || '',
                      }))
                    : [{ status: '', time: '', description: '' }],
            };
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        addTimeline() {
            this.form.timeline.push({ status: '', time: '', description: '' });
        },

        removeTimeline(index) {
            this.form.timeline.splice(index, 1);
        },

        closeModal() {
            this.modalOpen = false;
            document.body.style.overflow = '';
        },

        timelineInput(value) {
            if (!value) return '';
            return String(value).replace(' ', 'T').slice(0, 16);
        },

        setTimelineTime(row, value) {
            row.time = value ? `${value.replace('T', ' ')}:00` : '';
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                await adminJson(`/api/admin/trackings/${this.form.order_id}`, {
                    method: 'PUT',
                    body: this.form,
                });
                this.notify(this.t('trackings', 'updated'));
                this.closeModal();
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async remove(orderId) {
            if (!(await this.confirmDelete('Hapus data pelacakan ini?'))) return;
            try {
                await adminJson(`/api/admin/trackings/${orderId}`, { method: 'DELETE' });
                this.notify(this.t('trackings', 'deleted'));
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },
    }));

    function emptyTrackingForm() {
        return {
            order_id: '',
            tracking_number: '',
            status: '',
            estimated_delivery: '',
            courier: '',
            recipient_name: '',
            recipient_phone: '',
            recipient_address: '',
            timeline: [{ status: '', time: '', description: '' }],
        };
    }

    /* ---------- MESSAGES ---------- */
    Alpine.data('evomiAdminMessages', () => ({
        ...listMixin(1000),
        loading: true,
        error: '',
        conversations: [],
        selectedEmail: null,
        selectedMeta: null,
        thread: [],
        threadLoading: false,
        reply: '',
        sending: false,
        search: '',

        get filtered() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.conversations;
            return this.conversations.filter((c) =>
                `${c.email} ${c.name || ''} ${c.last_message || ''}`.toLowerCase().includes(q),
            );
        },

        async init() {
            this.watchSearch();
            await this.load();
        },

        async load() {
            this.loading = true;
            try {
                const data = await adminJson('/api/admin/contact/conversations');
                this.conversations = unwrapList(data);
            } catch (e) {
                this.error = e.message || this.t('messages', 'load_error');
            } finally {
                this.loading = false;
            }
        },

        avatar(c) {
            return resolveAvatarUrl(c?.avatar);
        },

        formatTime(value) {
            if (!value) return '';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '';
            return date.toLocaleString(this.locale === 'en' ? 'en-US' : 'id-ID', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        threadText(row) {
            if (!row || typeof row !== 'object') return '';
            const value =
                row.body ||
                row.text ||
                row.message ||
                row.reply_message ||
                row.reply ||
                '';
            return String(value).trim();
        },

        normalizeThread(raw) {
            const rows = Array.isArray(raw) ? raw : [];
            const bubbles = [];

            for (const row of rows) {
                if (row?.type && (row.text || row.message || row.body || row.reply_message)) {
                    bubbles.push({
                        id: String(row.id || `${row.type}-${row.created_at}`),
                        type: row.type === 'admin' ? 'admin' : 'user',
                        text: this.threadText(row),
                        subject: row.subject || '',
                        created_at: row.created_at || row.createdAt || null,
                    });
                    continue;
                }

                const userText = String(row?.message || '').trim();
                if (userText && userText !== '[Percakapan dimulai oleh admin]') {
                    bubbles.push({
                        id: `msg-${row.id}`,
                        type: 'user',
                        text: userText,
                        subject: row.subject || '',
                        created_at: row.created_at || null,
                    });
                }

                for (const reply of row?.replies || row?.contact_replies || []) {
                    bubbles.push({
                        id: `reply-${reply.id}`,
                        type: 'admin',
                        text: String(reply.reply_message || reply.message || reply.text || '').trim(),
                        subject: '',
                        created_at: reply.created_at || null,
                    });
                }
            }

            return bubbles.filter((b) => b.text);
        },

        async openThread(conv) {
            const email = typeof conv === 'string' ? conv : conv.email;
            this.selectedEmail = email;
            this.selectedMeta =
                typeof conv === 'string'
                    ? this.conversations.find((c) => c.email === email) || null
                    : conv;
            this.reply = '';
            this.threadLoading = true;
            try {
                const data = await adminJson(
                    `/api/admin/contact/thread?email=${encodeURIComponent(email)}`,
                );
                const payload = unwrapData(data) || {};
                const rawMessages = Array.isArray(payload.messages)
                    ? payload.messages
                    : Array.isArray(payload)
                      ? payload
                      : [];
                this.thread = this.normalizeThread(rawMessages);
                this.selectedMeta = {
                    ...(this.selectedMeta || {}),
                    email,
                    name: payload.name || this.selectedMeta?.name || email,
                    avatar: payload.avatar ?? this.selectedMeta?.avatar,
                };
                this.conversations = this.conversations.map((c) =>
                    c.email === email ? { ...c, unread_count: 0 } : c,
                );
                this.$nextTick(() => {
                    const pane = this.$refs.threadPane;
                    if (pane) pane.scrollTop = pane.scrollHeight;
                });
            } catch (e) {
                Alpine.store('adminUi').notify(e.message, 'error');
            } finally {
                this.threadLoading = false;
            }
        },

        async send() {
            if (!this.reply.trim() || !this.selectedEmail || this.sending) return;
            this.sending = true;
            try {
                const conv = this.conversations.find((c) => c.email === this.selectedEmail);
                await adminJson('/api/admin/contact/thread/send', {
                    method: 'POST',
                    body: {
                        email: this.selectedEmail,
                        name: conv?.name || 'User',
                        message: this.reply.trim(),
                    },
                });
                this.reply = '';
                await this.openThread(this.selectedMeta || this.selectedEmail);
                await this.load();
            } catch (e) {
                Alpine.store('adminUi').notify(e.message, 'error');
            } finally {
                this.sending = false;
            }
        },

        async removeThread(email) {
            const ok = await Alpine.store('adminUi').askConfirm(
                this.t('messages', 'delete_message'),
                this.t('messages', 'delete_title'),
            );
            if (!ok) return;
            try {
                await adminJson(
                    `/api/admin/contact/thread?email=${encodeURIComponent(email)}`,
                    { method: 'DELETE' },
                );
                if (this.selectedEmail === email) {
                    this.selectedEmail = null;
                    this.selectedMeta = null;
                    this.thread = [];
                }
                Alpine.store('adminUi').notify(this.t('messages', 'deleted'));
                await this.load();
            } catch (e) {
                Alpine.store('adminUi').notify(e.message, 'error');
            }
        },
    }));

    /* ---------- CART / WISHLIST ---------- */
    Alpine.data('evomiAdminCart', () => ({
        ...listMixin(5),
        async init() {
            this.watchSearch();
            await this.load();
        },
        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/carts');
                this.items = unwrapList(data);
            } catch (e) {
                this.error = e.message || this.t('cart', 'load_error');
            } finally {
                this.loading = false;
            }
        },
        async remove(id) {
            if (
                !(await this.confirmDelete(
                    this.t('cart', 'delete_desc'),
                    this.t('common', 'confirm_delete'),
                ))
            ) {
                return;
            }
            try {
                await adminJson(`/api/carts/${id}`, { method: 'DELETE' });
                this.notify(this.t('cart', 'deleted_success'));
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },
    }));

    Alpine.data('evomiAdminWishlist', () => ({
        ...listMixin(5),
        async init() {
            this.watchSearch();
            await this.load();
        },
        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/wishlists');
                this.items = unwrapList(data);
            } catch (e) {
                this.error = e.message || this.t('wishlist', 'load_error');
            } finally {
                this.loading = false;
            }
        },
        async remove(id) {
            if (
                !(await this.confirmDelete(
                    this.t('wishlist', 'confirm_delete_desc'),
                    this.t('wishlist', 'confirm_delete_title'),
                ))
            ) {
                return;
            }
            try {
                await adminJson(`/api/wishlists/${id}`, { method: 'DELETE' });
                this.notify(this.t('wishlist', 'deleted_success'));
                await this.load();
            } catch (e) {
                this.notify(e.message, 'error');
            }
        },
    }));

    /* ---------- SUBSCRIBERS (read-only, matches Next.js) ---------- */
    Alpine.data('evomiAdminSubscribers', () => ({
        ...listMixin(10),
        async init() {
            this.watchSearch();
            await this.load();
        },
        async load() {
            this.loading = true;
            this.error = '';
            try {
                const data = await adminJson('/api/admin/subscribers');
                this.items = unwrapList(data);
                this.page = 1;
            } catch (e) {
                this.error = e.message || this.t('subscribers', 'load_error');
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
    }));

    /* ---------- TRAFFIC / VISITORS ---------- */
    Alpine.data('evomiAdminTraffic', () => ({
        ...listMixin(5),
        filterType: 'all',
        autoRefresh: true,
        lastUpdatedAt: null,
        _pollTimer: null,
        viewOpen: false,
        viewVisit: null,
        stats: {
            online_now: 0,
            online_guest: 0,
            online_user: 0,
            today_guest: 0,
            today_user: 0,
            today_views: 0,
            online_window_seconds: 120,
            generated_at: null,
        },

        get typeOptions() {
            return [
                { id: 'all', label: this.t('traffic', 'filter_all') },
                { id: 'user', label: this.t('traffic', 'filter_user') },
                { id: 'guest', label: this.t('traffic', 'filter_guest') },
            ];
        },

        async init() {
            this.watchSearch();
            this.$watch('filterType', () => {
                this.page = 1;
            });
            this.$watch('autoRefresh', (on) => {
                if (on) this.startPolling();
                else this.stopPolling();
            });
            await this.load(true);
            this.startPolling();
            window.addEventListener('beforeunload', () => this.stopPolling(), { once: true });
        },

        startPolling() {
            this.stopPolling();
            if (!this.autoRefresh) return;
            window.__evomiTrafficPoll = setInterval(() => {
                if (document.visibilityState === 'hidden') return;
                this.load(false);
            }, 10000);
            this._pollTimer = window.__evomiTrafficPoll;
        },

        stopPolling() {
            if (window.__evomiTrafficPoll) {
                clearInterval(window.__evomiTrafficPoll);
                window.__evomiTrafficPoll = null;
            }
            this._pollTimer = null;
        },

        async load(showSpinner = true) {
            if (showSpinner) this.loading = true;
            this.error = '';
            try {
                const qs = new URLSearchParams({ limit: '120' });
                if (this.filterType === 'user' || this.filterType === 'guest') {
                    qs.set('type', this.filterType);
                }
                if (this.search.trim()) qs.set('q', this.search.trim());
                const data = await adminJson(`/api/admin/traffic?${qs.toString()}`);
                const payload = data?.data || data || {};
                this.stats = {
                    ...this.stats,
                    ...(payload.stats || {}),
                };
                this.items = Array.isArray(payload.items) ? payload.items : [];
                this.lastUpdatedAt = new Date();
            } catch (e) {
                this.error = e.message || this.t('traffic', 'load_error');
                if (showSpinner) this.items = [];
            } finally {
                this.loading = false;
            }
        },

        openView(v) {
            this.viewVisit = v || null;
            this.viewOpen = true;
        },

        closeView() {
            this.viewOpen = false;
            this.viewVisit = null;
        },

        filteredItems() {
            const q = this.search.trim().toLowerCase();
            let rows = this.items;
            if (this.filterType === 'user' || this.filterType === 'guest') {
                rows = rows.filter((r) => r.visitor_type === this.filterType);
            }
            if (!q) return rows;
            return rows.filter((row) => JSON.stringify(row).toLowerCase().includes(q));
        },

        visitorName(v) {
            if (v?.user?.name) return v.user.name;
            if (v?.visitor_type === 'user') return this.t('traffic', 'user_fallback');
            return this.t('traffic', 'guest_fallback');
        },

        visitorSub(v) {
            if (v?.user?.email) return v.user.email;
            return String(v?.visitor_key || '').slice(0, 8) + '…';
        },

        locationLabel(v) {
            const parts = [v?.city, v?.region, v?.country].filter(Boolean);
            if (!parts.length) return this.t('traffic', 'unknown_location');
            return parts.join(', ');
        },

        countryFlagUrl(v) {
            const code = String(v?.country_code || '').toLowerCase();
            if (!code || code === 'lo' || !/^[a-z]{2}$/.test(code)) return '';
            return `https://flagcdn.com/w40/${code}.png`;
        },

        countryCodeLabel(v) {
            const code = String(v?.country_code || '').toUpperCase();
            if (!code || code === 'LO') return '';
            return code;
        },

        deviceLabel(v) {
            const parts = [v?.device, v?.browser, v?.platform].filter(Boolean);
            return parts.length ? parts.join(' · ') : '-';
        },

        shortUrl(url) {
            try {
                const u = new URL(url);
                return u.host + u.pathname;
            } catch {
                return String(url || '').slice(0, 60);
            }
        },

        formatWhen(iso) {
            if (!iso) return '-';
            try {
                return new Date(iso).toLocaleString(this.locale === 'en' ? 'en-GB' : 'id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                });
            } catch {
                return String(iso);
            }
        },

        relativeWhen(iso) {
            if (!iso) return '';
            const ts = new Date(iso).getTime();
            if (!Number.isFinite(ts)) return '';
            const diff = Math.max(0, Math.floor((Date.now() - ts) / 1000));
            if (diff < 15) return this.t('traffic', 'just_now');
            if (diff < 60) return `${diff}s`;
            if (diff < 3600) return `${Math.floor(diff / 60)}m`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
            return `${Math.floor(diff / 86400)}d`;
        },

        lastUpdatedLabel() {
            if (!this.lastUpdatedAt) return this.t('traffic', 'not_updated');
            return `${this.t('traffic', 'updated')} ${this.relativeWhen(this.lastUpdatedAt.toISOString())}`;
        },
    }));

    /* ---------- PROFILE ---------- */
    Alpine.data('evomiAdminProfile', () => ({
        ...i18nMixin(),
        loading: true,
        saving: false,
        modalOpen: false,
        isAdmin: false,
        meta: { id: null, created_at: null, updated_at: null, last_login_at: null, last_seen_at: null },
        form: {
            name: '',
            nama_lengkap: '',
            email: '',
            phone: '',
            alamat_lengkap: '',
            password: '',
        },
        avatarFile: null,
        avatarPreview: null,

        async init() {
            this.watchLocale();
            await this.load();
        },

        formatDate(value) {
            return value ? formatDisplayDate(value, this.locale) : '';
        },

        presence(value) {
            return value ? formatDisplayDate(value, this.locale) : this.t('profile', 'not_set');
        },

        openEdit() {
            this.form.password = '';
            this.avatarFile = null;
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            if (this.saving) return;
            this.modalOpen = false;
            document.body.style.overflow = '';
        },

        async load() {
            this.loading = true;
            try {
                const data = await adminJson('/api/user/profile');
                const u = unwrapData(data) || getAuthUser() || {};
                this.form = {
                    name: u.name || '',
                    nama_lengkap: u.nama_lengkap || u.name || '',
                    email: u.email || '',
                    phone: u.phone || '',
                    alamat_lengkap: u.alamat_lengkap || '',
                    password: '',
                };
                this.isAdmin = u.is_admin === true || u.is_admin === 1 || u.is_admin === '1' || u.id === 1;
                this.meta = {
                    id: u.id || null,
                    created_at: u.created_at || null,
                    updated_at: u.updated_at || null,
                    last_login_at: u.last_login_at || u.last_login || null,
                    last_seen_at: u.last_seen_at || u.last_seen || null,
                };
                this.avatarPreview = resolveAvatarUrl(u.avatar_profile || u.avatar);
                this.avatarFile = null;
            } catch (e) {
                Alpine.store('adminUi').notify(e.message || this.t('profile', 'load_error'), 'error');
            } finally {
                this.loading = false;
            }
        },

        onAvatar(e) {
            const f = e.target.files?.[0];
            if (!f) return;
            if (f.size > 2 * 1024 * 1024) {
                Alpine.store('adminUi').notify(this.t('profile', 'avatar_too_large'), 'error');
                return;
            }
            this.avatarFile = f;
            this.avatarPreview = URL.createObjectURL(f);
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const fd = new FormData();
                Object.entries(this.form).forEach(([k, v]) => {
                    if (k === 'password' && !v) return;
                    fd.append(k, v ?? '');
                });
                if (this.avatarFile) fd.append('avatar_profile', this.avatarFile);
                const data = await adminJson('/api/user/profile', {
                    method: 'POST',
                    body: fd,
                    json: false,
                });
                const u = unwrapData(data);
                if (u) {
                    const token = localStorage.getItem('auth_token');
                    if (token) {
                        localStorage.setItem('auth_user', JSON.stringify(u));
                        localStorage.setItem('user', JSON.stringify(u));
                    }
                }
                Alpine.store('adminUi').notify(this.t('profile', 'update_success'));
                this.closeModal();
                await this.load();
            } catch (e) {
                Alpine.store('adminUi').notify(e.message || this.t('profile', 'update_error'), 'error');
            } finally {
                this.saving = false;
            }
        },
    }));

    /* ---------- CMS ---------- */
    const CMS_TABS = [
        { key: 'beranda', page: 'beranda', label: 'Beranda' },
        { key: 'belanja', page: 'belanja', label: 'Belanja' },
        { key: 'belanja_details', page: 'belanja_details', label: 'Belanja Details' },
        { key: 'checkout', page: 'checkout', label: 'Checkout' },
        { key: 'faq', page: null, label: 'FAQ' },
        { key: 'kuis_hasil', page: null, label: 'Kuis Hasil' },
        { key: 'kontak', page: 'kontak', label: 'Kontak' },
        { key: 'navfooter', page: null, label: 'Navbar / Footer' },
        { key: 'ui', page: 'ui', label: 'UI Website' },
        { key: 'admin', page: 'admin', label: 'UI Admin' },
    ];

    const NUMERIC_STYLE_KEY_RE =
        /(_fs_|_pos_|_left_|_top_|_right_|_bottom_|_size_|_gap_|_rotate_|_icon_size_|_max_lines$|^wave_|^gap_(horizontal|vertical)_)/;

    function resolveCmsImage(path) {
        if (!path) return '';
        const p = String(path).trim();
        if (!p) return '';
        if (/^(https?:|blob:|data:)/i.test(p)) return p;
        if (p.startsWith('/')) {
            return encodeURI(p);
        }
        if (/^(src|images|img|assets|build|fonts|favicon)\//i.test(p)) {
            return encodeURI(`/${p}`);
        }
        return `/storage/${encodeURI(p.replace(/^\/+/, ''))}`;
    }

    function looksLikeCssNumber(value) {
        const v = String(value ?? '').trim();
        if (!v) return false;
        return /^-?\d+(\.\d+)?\s*(px|%|deg|em|rem)?$/i.test(v);
    }

    function isCmsFontFamilyField(key) {
        return /_font_family$/.test(key || '');
    }
    function isCmsFontWeightField(key) {
        return /_font_weight$/.test(key || '');
    }
    function isCmsFontStyleField(key) {
        return /_font_style$/.test(key || '');
    }
    function isCmsFontField(key) {
        return isCmsFontFamilyField(key) || isCmsFontWeightField(key) || isCmsFontStyleField(key);
    }

    function isNumericStyleField(key, value) {
        if ((key || '').endsWith('_color')) return false;
        if (isCmsFontField(key)) return false;
        if (
            (key || '').includes('badge_label') ||
            (key || '').endsWith('_icon') ||
            (key || '').endsWith('_text') ||
            (key || '').endsWith('_title')
        ) {
            return false;
        }
        if (NUMERIC_STYLE_KEY_RE.test(key || '')) return true;
        return looksLikeCssNumber(value);
    }

    function parseNumericCmsValue(raw) {
        const value = String(raw ?? '').trim();
        if (!value) return { num: '', unit: '' };
        const spaced = value.match(/^(-?\d+(?:\.\d+)?)\s*(px|%|deg|em|rem)$/i);
        if (spaced) return { num: spaced[1], unit: spaced[2].toLowerCase() };
        const unitMatch = value.match(/(px|%|deg|em|rem)$/i);
        const unit = unitMatch ? unitMatch[1].toLowerCase() : '';
        const numPart = unit ? value.slice(0, -unit.length).trim() : value;
        if (numPart === '' || /^-?\d*\.?\d*$/.test(numPart)) {
            return { num: numPart, unit };
        }
        const match = value.match(/^(-?\d+(?:\.\d+)?)/);
        if (match) return { num: match[1], unit: unit || '' };
        return { num: value, unit: '' };
    }

    function inferNumericUnit(key) {
        if ((key || '').endsWith('_max_lines')) return '';
        if (/_rotate_/.test(key) || /product\d+_size_/.test(key)) return '';
        if (/headline_\d+_gap_horizontal_/.test(key || '')) return 'em';
        if (
            /_fs_/.test(key) ||
            /_gap_/.test(key) ||
            /_icon_size_/.test(key) ||
            /card_icon_size_/.test(key) ||
            /divider_icon_\d+_size_/.test(key) ||
            /divider_bottom_/.test(key) ||
            /headline_pos_/.test(key)
        ) {
            return 'px';
        }
        if (/_left_|_top_|_right_|_bottom_|^wave_/.test(key)) return '%';
        return '';
    }

    function displayUnitForField(key, storageUnit) {
        if (storageUnit) return storageUnit;
        if (/_rotate_/.test(key)) return 'deg';
        if (/product\d+_size_/.test(key)) return '%';
        return '';
    }

    function resolveNumericUnit(key, raw) {
        const parsed = parseNumericCmsValue(raw);
        if (parsed.unit) return parsed.unit;
        return inferNumericUnit(key);
    }

    function composeNumericCmsValue(num, unit) {
        const trimmed = String(num ?? '').trim();
        if (!trimmed) return '';
        return `${trimmed}${unit || ''}`;
    }

    function numericStepForField(key, unit) {
        if (unit === 'em' || unit === 'rem') return 0.01;
        if (unit === '%' || /_left_|_top_|_right_|_bottom_|^wave_/.test(key)) return 0.1;
        return 1;
    }

    function formatStepped(n, step) {
        const decimals = String(step).includes('.') ? (String(step).split('.')[1]?.length ?? 0) : 0;
        const rounded =
            decimals > 0 ? Math.round(n * 10 ** decimals) / 10 ** decimals : Math.round(n);
        if (decimals === 0) return String(rounded);
        return rounded.toFixed(decimals).replace(/\.?0+$/, '');
    }

    function fieldKind(field) {
        const key = field.key || '';
        if (field.type === 'image' || isCmsImageKey(key, field.type)) return 'image';
        if (key.endsWith('_color')) return 'color';
        if (isCmsFontFamilyField(key)) return 'font_family';
        if (isCmsFontWeightField(key)) return 'font_weight';
        if (isCmsFontStyleField(key)) return 'font_style';
        if (isNumericStyleField(key, field.value)) return 'number';
        if (isTypographyBaseField(field)) return 'copy';
        if (field.type === 'text' || /desc|content|subtitle|excerpt|body|answer/i.test(key)) {
            return 'text';
        }
        return 'string';
    }

    function isCmsImageKey(key, type) {
        if (type === 'image') return true;
        if (NUMERIC_STYLE_KEY_RE.test(key || '')) return false;
        if ((key || '').endsWith('_color')) return false;
        return (
            /(_image|_img)$/.test(key || '') ||
            (key || '').endsWith('_icon') ||
            key === 'image' ||
            key === 'favicon' ||
            /photo|avatar/i.test(key || '')
        );
    }

    function sortSectionEntries(pageKey, entries) {
        const order = SECTION_ORDER[pageKey] || SECTION_ORDER.beranda;
        return [...entries].sort((a, b) => {
            const ai = order.indexOf(a.name);
            const bi = order.indexOf(b.name);
            return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
        });
    }

    Alpine.data('evomiAdminCms', () => ({
        tabs: CMS_TABS,
        tab: 'beranda',
        locale: 'id',
        loading: false,
        saving: false,
        savingSection: null,
        fields: [],
        faqs: [],
        results: [],
        kuisTypographyFields: [],
        faqModal: false,
        faqForm: emptyFaqForm(),
        faqMode: 'add',
        navFields: [],
        footerFields: [],
        fontFamilyOptions: FONT_FAMILY_OPTIONS,
        fontWeightOptions: FONT_WEIGHT_OPTIONS,
        fontStyleOptions: FONT_STYLE_OPTIONS,
        fontOpenKey: null,

        fontFieldKey(field) {
            return `${field.section || ''}::${field.key || ''}`;
        },

        isFontOpen(field) {
            return this.fontOpenKey === this.fontFieldKey(field);
        },

        toggleFontOpen(field) {
            const key = this.fontFieldKey(field);
            this.fontOpenKey = this.fontOpenKey === key ? null : key;
        },

        closeFontOpen() {
            this.fontOpenKey = null;
        },

        fontOptionsFor(field) {
            const k = this.kind(field);
            if (k === 'font_family') return this.fontFamilyOptions;
            if (k === 'font_weight') return this.fontWeightOptions;
            return this.fontStyleOptions;
        },

        fontGroupsFor(field) {
            const opts = this.fontOptionsFor(field);
            if (this.kind(field) !== 'font_family') {
                return [{ key: null, label: null, options: opts }];
            }
            return [
                {
                    key: 'project',
                    label: 'Font Project',
                    options: opts.filter((o) => o.group === 'project'),
                },
                {
                    key: 'system',
                    label: 'Font Sistem',
                    options: opts.filter((o) => o.group === 'system'),
                },
            ];
        },

        selectedFontLabel(field) {
            const opts = this.fontOptionsFor(field);
            const hit = opts.find((o) => o.value === String(field.value || ''));
            return hit?.label || field.value || 'Pilih opsi…';
        },

        fontTriggerStyle(field) {
            const k = this.kind(field);
            const v = field.value || '';
            if (k === 'font_family') return { fontFamily: resolveFontFamilyCss(v || 'nohemi') };
            if (k === 'font_weight') return { fontWeight: Number(v) || 400 };
            if (k === 'font_style') return { fontStyle: v === 'italic' ? 'italic' : 'normal' };
            return {};
        },

        fontOptionStyle(field, opt) {
            const k = this.kind(field);
            if (k === 'font_family') return { fontFamily: resolveFontFamilyCss(opt.value) };
            if (k === 'font_weight') return { fontWeight: Number(opt.value) || 400 };
            if (k === 'font_style') {
                return { fontStyle: opt.value === 'italic' ? 'italic' : 'normal' };
            }
            return {};
        },

        pickFont(field, value) {
            field.value = value;
            this.fontOpenKey = null;
        },

        sectionsList() {
            const map = {};
            for (const f of this.fields) {
                const s = f.section || 'general';
                if (!map[s]) map[s] = [];
                map[s].push(f);
            }
            const entries = Object.entries(map).map(([name, items]) => ({
                name,
                label: SECTION_LABELS[name] || name,
                items: sortSectionFields(name, items),
            }));
            const pageKey =
                this.tab === 'faq'
                    ? 'faq'
                    : CMS_TABS.find((t) => t.key === this.tab)?.page || this.tab;
            return sortSectionEntries(pageKey, entries);
        },

        sectionsForActiveTab() {
            if (this.tab === 'navfooter') return this.navFooterSections();
            return this.sectionsList();
        },

        resultTypographyFields(personalityKey) {
            const key = String(personalityKey || '');
            return sortSectionFields(
                key,
                this.kuisTypographyFields.filter((f) => (f.section || '') === key),
            );
        },

        navFooterSections() {
            const all = [...this.navFields, ...this.footerFields];
            const map = {};
            for (const f of all) {
                const s = f.section || 'general';
                if (!map[s]) map[s] = [];
                map[s].push(f);
            }
            const entries = Object.entries(map).map(([name, items]) => ({
                name,
                label: SECTION_LABELS[name] || name,
                items: sortSectionFields(name, items),
            }));
            return sortSectionEntries('navfooter', entries);
        },

        fieldLabel,

        kind(field) {
            return fieldKind(field);
        },

        isWaveIcon(key) {
            return key === 'wave_left_icon' || key === 'wave_right_icon';
        },

        imgUrl(path) {
            return resolveCmsImage(path);
        },

        numDisplay(field) {
            return parseNumericCmsValue(field.value).num;
        },

        unitLabel(field) {
            if ((field.key || '').endsWith('_max_lines')) return 'baris';
            const unit = resolveNumericUnit(field.key, field.value);
            const display = displayUnitForField(field.key, unit);
            return display === 'deg' ? '°' : display;
        },

        setNumeric(field, rawNum) {
            const cleaned = String(rawNum ?? '')
                .trim()
                .replace(/[^\d.\-]/g, '');
            if (cleaned !== '' && !/^-?\d*\.?\d*$/.test(cleaned)) return;
            if ((field.key || '').endsWith('_max_lines')) {
                if (cleaned === '') {
                    field.value = defaultMaxLines(String(field.key || '').replace(/_max_lines$/, ''));
                    return;
                }
                const n = Math.min(3, Math.max(1, Math.round(Number.parseFloat(cleaned) || 1)));
                field.value = String(n);
                return;
            }
            const unit = resolveNumericUnit(field.key, field.value);
            field.value = composeNumericCmsValue(cleaned, unit);
        },

        bumpNumeric(field, dir) {
            if ((field.key || '').endsWith('_max_lines')) {
                const current = Number.parseInt(String(field.value ?? '').trim(), 10);
                const base = Number.isFinite(current) ? current : 1;
                field.value = String(Math.min(3, Math.max(1, base + dir)));
                return;
            }
            const unit = resolveNumericUnit(field.key, field.value);
            const step = numericStepForField(field.key, unit);
            const current = Number.parseFloat(parseNumericCmsValue(field.value).num);
            const base = Number.isFinite(current) ? current : 0;
            this.setNumeric(field, formatStepped(base + dir * step, step));
        },

        findCompanion(field, suffix) {
            const section = field.section || 'general';
            const key = `${field.key || ''}${suffix}`;
            const pools = [this.fields, this.navFields, this.footerFields, this.kuisTypographyFields];
            for (const pool of pools) {
                const hit = (pool || []).find((f) => (f.section || 'general') === section && f.key === key);
                if (hit) return hit;
            }
            return null;
        },

        maxLinesFor(field) {
            const companion = this.findCompanion(field, '_max_lines');
            const raw = companion?.value ?? defaultMaxLines(field.key || '');
            const n = Number.parseInt(String(raw).trim(), 10);
            return Math.min(3, Math.max(1, Number.isFinite(n) ? n : 2));
        },

        clampFieldLines(value, max) {
            const lines = String(value ?? '').split(/\r\n|\n|\r/);
            if (lines.length <= max) return lines.join('\n');
            return lines.slice(0, max).join('\n');
        },

        onCopyInput(field, event) {
            const max = this.maxLinesFor(field);
            const clamped = this.clampFieldLines(event.target.value, max);
            field.value = clamped;
            if (event.target.value !== clamped) {
                event.target.value = clamped;
            }
        },

        onCopyKeydown(field, event) {
            if (event.key !== 'Enter' || event.shiftKey) return;
            const max = this.maxLinesFor(field);
            const el = event.target;
            const start = el.selectionStart ?? 0;
            const end = el.selectionEnd ?? 0;
            const next = `${el.value.slice(0, start)}\n${el.value.slice(end)}`;
            if (next.split(/\r\n|\n|\r/).length > max) {
                event.preventDefault();
            }
        },

        clearImage(field) {
            field.value = '';
        },

        async init() {
            await this.loadTab();
        },

        async setTab(key) {
            this.tab = key;
            await this.loadTab();
        },

        async loadTab() {
            this.loading = true;
            try {
                if (this.tab === 'faq') {
                    const [faqData, uiData] = await Promise.all([
                        adminJson('/api/admin/cms/faqs'),
                        adminJson(`/api/admin/cms/ui?locale=${this.locale}`),
                    ]);
                    this.faqs = unwrapList(faqData);
                    const uiFields = unwrapList(uiData).map(normalizeField);
                    const faqOnly = uiFields.filter((f) => f.section === 'faq');
                    this.fields = ensureFontCompanionFields(
                        faqOnly.length ? faqOnly : defaultFaqTypographyFields(),
                    );
                    this.kuisTypographyFields = [];
                    this.navFields = [];
                    this.footerFields = [];
                } else if (this.tab === 'kuis_hasil') {
                    const [resData, cmsData] = await Promise.all([
                        adminJson('/api/admin/quiz/results'),
                        adminJson(`/api/admin/cms/kuis_hasil?locale=${this.locale}`),
                    ]);
                    this.results = unwrapList(resData);
                    const cms = unwrapList(cmsData).map(normalizeField);
                    const base = this.results.flatMap((r) => {
                        const sec = r.personality_key || r.key || 'result';
                        return [
                            {
                                section: sec,
                                key: 'title',
                                type: 'string',
                                value: r.title || '',
                            },
                            {
                                section: sec,
                                key: 'description',
                                type: 'text',
                                value: r.description || '',
                            },
                        ];
                    });
                    const merged = [...cms];
                    for (const b of base) {
                        if (
                            !merged.some(
                                (f) => f.section === b.section && f.key === b.key,
                            )
                        ) {
                            merged.push(b);
                        }
                    }
                    this.kuisTypographyFields = ensureFontCompanionFields(merged).filter((f) =>
                        /_font_(family|weight|style)$|_fs_(mobile|desktop)$|_max_lines$/.test(f.key || ''),
                    );
                    this.fields = [];
                    this.faqs = [];
                    this.navFields = [];
                    this.footerFields = [];
                } else if (this.tab === 'navfooter') {
                    const [nav, foot] = await Promise.all([
                        adminJson(`/api/admin/cms/navbar?locale=${this.locale}`),
                        adminJson(`/api/admin/cms/footer?locale=${this.locale}`),
                    ]);
                    this.navFields = ensureFontCompanionFields(
                        unwrapList(nav).map(normalizeField),
                    );
                    this.footerFields = ensureFontCompanionFields(
                        unwrapList(foot).map(normalizeField),
                    );
                    this.fields = [];
                    this.kuisTypographyFields = [];
                } else {
                    const page = CMS_TABS.find((t) => t.key === this.tab)?.page;
                    const data = await adminJson(
                        `/api/admin/cms/${page}?locale=${this.locale}`,
                    );
                    const baseFields = unwrapList(data).map(normalizeField);
                    // Beranda defaults (copy + typography + gaps) come from the
                    // backend catalog so they match the live storefront UI.
                    // Also inject hero headline word-gap fields client-side so
                    // they always appear even if API cache is stale.
                    this.fields =
                        page === 'beranda'
                            ? ensureHeroHeadlineGapFields(baseFields, page)
                            : ensureSectionSpacingFields(
                                  ensureFontCompanionFields(
                                      page === 'belanja_details'
                                          ? ensureBelanjaDetailsShippingFields(
                                                ensureBerandaContentFields(baseFields, page),
                                                page,
                                            )
                                          : ensureBerandaContentFields(baseFields, page),
                                  ),
                                  page,
                              );
                    this.kuisTypographyFields = [];
                    this.navFields = [];
                    this.footerFields = [];
                }
            } catch (e) {
                Alpine.store('adminUi').notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        async uploadImage(field, event) {
            const file = event?.target?.files?.[0];
            if (!file) return;
            try {
                const fd = new FormData();
                fd.append('image', file);
                const data = await adminJson('/api/admin/cms/upload', {
                    method: 'POST',
                    body: fd,
                    json: false,
                });
                const path = data?.data?.path || data?.path || data?.data?.url || '';
                field.value = path;
                Alpine.store('adminUi').notify('Gambar diunggah');
            } catch (e) {
                Alpine.store('adminUi').notify(e.message, 'error');
            } finally {
                if (event?.target) event.target.value = '';
            }
        },

        async savePage() {
            if (this.saving) return;
            this.saving = true;
            try {
                if (this.tab === 'kuis_hasil') {
                    if (!this.results.length && !this.kuisTypographyFields.length) {
                        Alpine.store('adminUi').notify('Tidak ada data hasil kuis untuk disimpan', 'error');
                        return;
                    }
                    for (const result of this.results) {
                        const key = result.personality_key || result.key || result.personality;
                        if (!key) continue;
                        await adminJson(`/api/admin/quiz/results/${key}`, {
                            method: 'PUT',
                            body: {
                                product_image: result.product_image,
                                bg_image: result.bg_image,
                                bg_image_width_mobile: result.bg_image_width_mobile,
                                bg_image_width_desktop: result.bg_image_width_desktop,
                                product_image_width_mobile: result.product_image_width_mobile,
                                product_image_width_desktop: result.product_image_width_desktop,
                                color: result.color,
                                title: result.title,
                                description: result.description,
                            },
                        });
                    }
                    if (this.kuisTypographyFields.length) {
                        await adminJson('/api/admin/cms/kuis_hasil', {
                            method: 'PUT',
                            body: {
                                locale: this.locale,
                                fields: this.kuisTypographyFields.map(toSaveField),
                            },
                        });
                    }
                } else if (this.tab === 'navfooter') {
                    await adminJson('/api/admin/cms/navbar', {
                        method: 'PUT',
                        body: {
                            locale: this.locale,
                            fields: this.navFields.map(toSaveField),
                        },
                    });
                    await adminJson('/api/admin/cms/footer', {
                        method: 'PUT',
                        body: {
                            locale: this.locale,
                            fields: this.footerFields.map(toSaveField),
                        },
                    });
                } else if (this.tab === 'faq') {
                    if (!this.fields.length) {
                        Alpine.store('adminUi').notify('Tidak ada konten tipografi FAQ untuk disimpan', 'error');
                        return;
                    }
                    await adminJson('/api/admin/cms/ui', {
                        method: 'PUT',
                        body: {
                            locale: this.locale,
                            fields: this.fields.map(toSaveField),
                        },
                    });
                } else {
                    const page = CMS_TABS.find((t) => t.key === this.tab)?.page;
                    if (!page) {
                        Alpine.store('adminUi').notify('Halaman CMS tidak valid', 'error');
                        return;
                    }
                    if (!this.fields.length) {
                        Alpine.store('adminUi').notify('Tidak ada field untuk disimpan', 'error');
                        return;
                    }
                    await adminJson(`/api/admin/cms/${page}`, {
                        method: 'PUT',
                        body: {
                            locale: this.locale,
                            fields: this.fields.map(toSaveField),
                        },
                    });
                }
                Alpine.store('adminUi').notify('CMS berhasil disimpan');
            } catch (e) {
                Alpine.store('adminUi').notify(e.message || 'Gagal menyimpan CMS', 'error');
            } finally {
                this.saving = false;
            }
        },

        async saveSection(sectionName) {
            if (this.savingSection || this.saving) return;
            this.savingSection = sectionName;
            try {
                const navSections = new Set(['site', 'menu']);
                if (this.tab === 'navfooter') {
                    const page = navSections.has(sectionName) ? 'navbar' : 'footer';
                    const source = page === 'navbar' ? this.navFields : this.footerFields;
                    const fields = source
                        .filter((f) => (f.section || 'general') === sectionName)
                        .map(toSaveField);
                    if (!fields.length) {
                        Alpine.store('adminUi').notify('Tidak ada field di section ini', 'error');
                        return;
                    }
                    await adminJson(`/api/admin/cms/${page}`, {
                        method: 'PUT',
                        body: { locale: this.locale, fields },
                    });
                } else if (this.tab === 'faq') {
                    const fields = this.fields
                        .filter((f) => (f.section || 'general') === sectionName)
                        .map(toSaveField);
                    if (!fields.length) {
                        Alpine.store('adminUi').notify('Tidak ada field di section ini', 'error');
                        return;
                    }
                    await adminJson('/api/admin/cms/ui', {
                        method: 'PUT',
                        body: { locale: this.locale, fields },
                    });
                } else {
                    const page = CMS_TABS.find((t) => t.key === this.tab)?.page;
                    const fields = this.fields
                        .filter((f) => (f.section || 'general') === sectionName)
                        .map(toSaveField);
                    if (!fields.length) {
                        Alpine.store('adminUi').notify('Tidak ada field di section ini', 'error');
                        return;
                    }
                    await adminJson(`/api/admin/cms/${page}`, {
                        method: 'PUT',
                        body: { locale: this.locale, fields },
                    });
                }
                Alpine.store('adminUi').notify(`Section "${sectionName}" disimpan`);
            } catch (e) {
                Alpine.store('adminUi').notify(e.message, 'error');
            } finally {
                this.savingSection = null;
            }
        },

        openFaqAdd() {
            this.faqMode = 'add';
            this.faqForm = emptyFaqForm();
            this.faqModal = true;
        },

        openFaqEdit(f) {
            this.faqMode = 'edit';
            this.faqForm = {
                id: f.id,
                category: f.category || '',
                question: f.question || '',
                question_en: f.question_en || '',
                answer: f.answer || '',
                answer_en: f.answer_en || '',
                sort_order: f.sort_order ?? 0,
                is_active: !!f.is_active,
            };
            this.faqModal = true;
        },

        async saveFaq() {
            try {
                const body = { ...this.faqForm };
                delete body.id;
                if (this.faqMode === 'add') {
                    await adminJson('/api/admin/cms/faqs', { method: 'POST', body });
                } else {
                    await adminJson(`/api/admin/cms/faqs/${this.faqForm.id}`, {
                        method: 'PUT',
                        body,
                    });
                }
                this.faqModal = false;
                Alpine.store('adminUi').notify('FAQ disimpan');
                await this.loadTab();
            } catch (e) {
                Alpine.store('adminUi').notify(e.message, 'error');
            }
        },

        async removeFaq(id) {
            const ok = await Alpine.store('adminUi').askConfirm('Hapus FAQ ini?');
            if (!ok) return;
            try {
                await adminJson(`/api/admin/cms/faqs/${id}`, { method: 'DELETE' });
                Alpine.store('adminUi').notify('FAQ dihapus');
                await this.loadTab();
            } catch (e) {
                Alpine.store('adminUi').notify(e.message, 'error');
            }
        },

        async uploadResultImage(result, field, event) {
            const file = event?.target?.files?.[0];
            if (!file) return;
            try {
                const fd = new FormData();
                fd.append('image', file);
                const data = await adminJson('/api/admin/cms/upload', {
                    method: 'POST',
                    body: fd,
                    json: false,
                });
                const path = data?.data?.path || data?.path || '';
                result[field] = path;
                Alpine.store('adminUi').notify('Gambar diunggah');
            } catch (e) {
                Alpine.store('adminUi').notify(e.message, 'error');
            } finally {
                if (event?.target) event.target.value = '';
            }
        },
    }));

    function normalizeField(f) {
        return {
            section: f.section,
            key: f.key,
            type: f.type || 'string',
            value: f.value ?? '',
        };
    }

    function toSaveField(f) {
        let value = f.value ?? '';
        if (isTypographyBaseField(f)) {
            // clamp by companion max_lines if present in the same payload isn't available here —
            // value already clamped in UI; still hard-cap at 3 lines
            const lines = String(value).split(/\r\n|\n|\r/);
            if (lines.length > 3) value = lines.slice(0, 3).join('\n');
        }
        if (String(f.key || '').endsWith('_max_lines')) {
            const n = Math.min(3, Math.max(1, Math.round(Number.parseInt(String(value).trim(), 10) || 1)));
            value = String(n);
        }
        return {
            section: f.section,
            key: f.key,
            type: f.type || 'string',
            value,
        };
    }

    function emptyFaqForm() {
        return {
            id: null,
            category: 'Umum',
            question: '',
            question_en: '',
            answer: '',
            answer_en: '',
            sort_order: 0,
            is_active: true,
        };
    }
}
