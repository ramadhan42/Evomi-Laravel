<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Faq;
use App\Models\Kurir;
use App\Models\KurirTarif;
use App\Models\OrderTracking;
use App\Models\Product;
use App\Models\QuizPersonalityResult;
use App\Models\QuizQuestion;
use App\Models\SiteContent;
use App\Support\ArticleContent;
use App\Support\ArticleSeo;
use App\Support\BelanjaCatalog;
use App\Support\CmsStorefront;
use App\Support\ShippingConfig;
use App\Support\LocaleResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function beranda(): View
    {
        return view('pages.beranda', [
            'cms' => CmsStorefront::forPage('beranda'),
        ]);
    }

    public function belanja(): View
    {
        return view('pages.belanja', [
            'products' => array_values(BelanjaCatalog::all()),
        ]);
    }

    public function belanjaShow(int $id): View|RedirectResponse
    {
        $bundle = $this->productDetailBundle($id);

        if (! $bundle) {
            return redirect()->route('belanja');
        }

        return view('pages.belanja-show', array_merge($bundle, [
            'themeAccent' => $bundle['product']['accent'] ?? '#1172BA',
        ]));
    }

    public function checkout(): View
    {
        return view('pages.checkout', [
            'themeAccent' => '#1172BA',
            'shippingOriginCity' => ShippingConfig::DEFAULT_ORIGIN_CITY,
            'shippingCities' => ShippingConfig::destinationCities(),
            'freeShipping' => ShippingConfig::isFreeShipping(),
        ]);
    }

    public function pembayaran(string $invoiceId): View
    {
        return view('pages.pembayaran', [
            'invoiceId' => $invoiceId,
            'themeAccent' => '#1172BA',
        ]);
    }

    public function artikel(): View
    {
        $articles = Article::query()
            ->published()
            ->latest('published_at')
            ->get()
            ->map(fn (Article $a) => $this->mapArticle($a))
            ->values()
            ->all();

        if ($articles === []) {
            $articles = [];
        }

        return view('pages.artikel', [
            'articles' => $articles,
        ]);
    }

    public function artikelShow(string $slug): View|RedirectResponse
    {
        $model = Article::query()->published()->where('slug', $slug)->first();

        if (! $model) {
            return redirect()->route('artikel');
        }

        $article = $this->mapArticle($model);

        // Related: prefer same category (Next API filters category=parfum then takes others)
        $all = Article::query()
            ->published()
            ->latest('published_at')
            ->get()
            ->map(fn (Article $a) => $this->mapArticle($a))
            ->all();

        $sameCategory = array_values(array_filter(
            $all,
            fn (array $item) => $item['slug'] !== $slug
                && strcasecmp((string) ($item['category'] ?? ''), (string) ($article['category'] ?? '')) === 0
        ));
        $related = array_slice($sameCategory, 0, 3);

        if (count($related) < 3) {
            foreach ($all as $item) {
                if ($item['slug'] === $slug) {
                    continue;
                }
                if (collect($related)->contains(fn ($r) => $r['slug'] === $item['slug'])) {
                    continue;
                }
                $related[] = $item;
                if (count($related) >= 3) {
                    break;
                }
            }
        }

        return view('pages.artikel-show', [
            'article' => $article,
            'related' => $related,
            'seo' => ArticleSeo::forArticle(
                $article,
                route('artikel.show', $model->slug),
                CmsStorefront::resolveLocale(),
            ),
        ]);
    }

    public function kuis(): View
    {
        $locale = CmsStorefront::resolveLocale();

        $questions = QuizQuestion::query()
            ->with(['options' => fn ($q) => $q->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(function (QuizQuestion $q) use ($locale) {
                $text = $q->question_text;
                if ($locale === 'en' && filled($q->question_text_en)) {
                    $text = $q->question_text_en;
                }

                return [
                    'id' => $q->id,
                    'text' => $text,
                    'options' => $q->options->map(function ($o) use ($locale) {
                        $optText = $o->option_text;
                        if ($locale === 'en' && filled($o->option_text_en)) {
                            $optText = $o->option_text_en;
                        }

                        return [
                            'id' => $o->id,
                            'text' => $optText,
                            'peaceful_calm' => (int) ($o->peaceful_calm_score ?? 0),
                            'purpose_prestige' => (int) ($o->prestige_score ?? 0),
                            'sweet_shy' => (int) ($o->sweet_shy_score ?? 0),
                            'rebel_brave' => (int) ($o->rebel_brave_score ?? 0),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        $results = [];
        foreach (QuizPersonalityResult::query()->orderBy('id')->get() as $row) {
            $key = (string) ($row->personality_key ?? '');
            if ($key === '') {
                continue;
            }
            $frontendKey = match ($key) {
                'prestige', 'purpose_prestige' => 'purpose_prestige',
                default => $key,
            };

            $productId = $row->forced_product_id;
            if (! $productId) {
                $productId = match ($frontendKey) {
                    'peaceful_calm' => Product::query()->where('personality_type', 'peaceful_calm')->value('id'),
                    'rebel_brave' => Product::query()->where('personality_type', 'rebel_brave')->value('id'),
                    'sweet_shy' => Product::query()->where('personality_type', 'sweet_shy')->value('id'),
                    default => Product::query()->where('personality_type', 'prestige')->value('id'),
                };
            }

            $fallbackImages = match ($frontendKey) {
                'peaceful_calm' => [
                    'bg' => '/src/images/kuis/peaceful-kanan.webp',
                    'product' => '/src/images/kuis/peaceful-produk.webp',
                ],
                'rebel_brave' => [
                    'bg' => '/src/images/kuis/rebel-kanan.webp',
                    'product' => '/src/images/kuis/rebel-produk.webp',
                ],
                'sweet_shy' => [
                    'bg' => '/src/images/kuis/sweet-kanan.webp',
                    'product' => '/src/images/kuis/sweet-produk.webp',
                ],
                default => [
                    'bg' => '/src/images/kuis/purpose-kanan.webp',
                    'product' => '/src/images/kuis/purpose-produk.webp',
                ],
            };

            $title = $row->title;
            $description = $row->description;
            if ($locale === 'en') {
                if (filled($row->title_en)) {
                    $title = $row->title_en;
                }
                if (filled($row->description_en)) {
                    $description = $row->description_en;
                }
            }

            $results[$frontendKey] = [
                'personality_key' => $frontendKey,
                'title' => $title,
                'description' => $description,
                'color' => $row->color ?: '#1172BA',
                'bg_image' => BelanjaCatalog::articleImageUrl($row->bg_image ?: $fallbackImages['bg']),
                'product_image' => BelanjaCatalog::articleImageUrl($row->product_image ?: $fallbackImages['product']),
                'bg_image_width_mobile' => $row->bg_image_width_mobile,
                'bg_image_width_desktop' => $row->bg_image_width_desktop,
                'product_image_width_mobile' => $row->product_image_width_mobile,
                'product_image_width_desktop' => $row->product_image_width_desktop,
                'product_id' => (string) ($productId ?: ''),
                'forced_product_id' => (string) ($productId ?: ''),
            ];
        }

        if ($questions === []) {
            $questions = [];
        }
        if ($results === []) {
            $results = [];
        }

        $resultProducts = [];
        foreach ($results as $result) {
            $pid = (int) ($result['forced_product_id'] ?? $result['product_id'] ?? 0);
            if ($pid > 0 && ! isset($resultProducts[$pid])) {
                $bundle = $this->productDetailBundle($pid);
                if ($bundle) {
                    $resultProducts[$pid] = $bundle;
                }
            }
        }

        return view('pages.kuis', [
            'questions' => $questions,
            'results' => $results,
            'resultProducts' => $resultProducts,
        ]);
    }

    public function faq(): View
    {
        $rows = Faq::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $faqGroups = [];
        if ($rows->isNotEmpty()) {
            foreach ($rows->groupBy('category') as $category => $items) {
                $faqGroups[$category ?: 'Umum'] = $items->map(fn (Faq $f) => [
                    'q' => $f->question,
                    'a' => $f->answer,
                ])->values()->all();
            }
        } else {
            $faqGroups = [];
        }

        return view('pages.faq', [
            'faqGroups' => $faqGroups,
        ]);
    }

    public function kontak(): View
    {
        $cms = $this->cmsMap('kontak');

        return view('pages.kontak', [
            'cms' => [
                'title' => $cms['header.title'] ?? 'Hubungi Kami',
                // Subjudul disunting dengan editor teks, jadi format inline-nya
                // ikut dicetak setelah disaring.
                'subtitle' => ArticleContent::sanitizeInlineHtml(
                    $cms['header.subtitle'] ?? 'Punya pertanyaan atau ingin berkolaborasi? Tim Evomi siap mendengarkan Anda.'
                ),
                'email_label' => $cms['info.email_label'] ?? 'Email',
                'email_value' => $cms['info.email_value'] ?? 'hello@evomi.id',
                'phone_label' => $cms['info.phone_label'] ?? 'WhatsApp',
                'phone_value' => $cms['info.phone_value'] ?? '+62 812-3456-7890',
                'address_label' => $cms['info.address_label'] ?? 'Kantor Pusat',
                'address_value' => $cms['info.address_value'] ?? 'Jakarta, Indonesia',
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function cmsMap(string $page, string $locale = 'id'): array
    {
        $out = [];
        foreach (
            SiteContent::query()
                ->where('page', $page)
                ->where('locale', $locale)
                ->get(['section', 'key', 'value']) as $row
        ) {
            $out[$row->section.'.'.$row->key] = (string) ($row->value ?? '');
        }

        return $out;
    }

    public function pengiriman(): View
    {
        return view('pages.pengiriman');
    }

    public function pengirimanShow(string $resi): View
    {
        $resi = trim(urldecode($resi));
        $row = $resi !== '' ? OrderTracking::findByResiOrOrder($resi) : null;

        if (! $row) {
            return view('pages.pengiriman-show', [
                'tracking' => null,
                'query' => $resi,
            ]);
        }

        $timelineRaw = is_array($row->timeline) ? $row->timeline : [];
        $timeline = [];
        foreach ($timelineRaw as $item) {
            $item = is_array($item) ? $item : (array) $item;
            $stamp = $item['time'] ?? $item['date'] ?? null;
            $parsed = is_string($stamp) && $stamp !== '' ? date_create($stamp) : null;
            $timeline[] = [
                'status' => $item['status'] ?? 'Update',
                'date' => $parsed ? $parsed->format('d M Y') : (string) ($item['date'] ?? ''),
                'time' => $parsed ? $parsed->format('H:i') : (string) ($item['time'] ?? ''),
                'description' => $item['description'] ?? ($item['status'] ?? ''),
            ];
        }

        $displayCode = $row->tracking_number ?: $row->order_id;

        $tracking = [
            'resi' => $displayCode,
            'orderId' => $row->order_id,
            'trackingNumber' => $row->tracking_number ?: null,
            'courier' => $row->courier ?: 'Kurir Evomi',
            'estimatedDelivery' => $row->estimated_delivery
                ? $row->estimated_delivery->translatedFormat('d M Y')
                : 'Belum ada estimasi',
            'currentStatus' => $row->status ?: 'Menunggu Konfirmasi',
            'recipient' => [
                'name' => $row->recipient_name ?: 'Pelanggan Evomi',
                'phone' => $row->recipient_phone ?: '-',
                'address' => $row->recipient_address ?: '-',
            ],
            'timeline' => $timeline ?: [[
                'status' => $row->status ?: 'Pesanan dibuat',
                'date' => optional($row->created_at)->translatedFormat('d M Y') ?: now()->translatedFormat('d M Y'),
                'time' => optional($row->created_at)->format('H:i') ?: now()->format('H:i'),
                'description' => 'Pesanan tercatat di sistem Evomi.',
            ]],
        ];

        return view('pages.pengiriman-show', [
            'tracking' => $tracking,
            'query' => $resi,
        ]);
    }

    public function login(): View
    {
        return view('pages.login');
    }

    public function register(): View
    {
        return view('pages.register');
    }

    public function lupaPassword(): View
    {
        return view('pages.lupa-password');
    }

    public function resetPassword(Request $request, string $token): View
    {
        return view('pages.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapArticle(Article $a): array
    {
        $locale = CmsStorefront::resolveLocale();
        $localized = LocaleResolver::resolveFields([
            'title' => $a->title,
            'title_en' => $a->title_en,
            'excerpt' => $a->excerpt,
            'excerpt_en' => $a->excerpt_en,
            'content' => $a->content,
            'content_en' => $a->content_en,
            'meta_title' => $a->meta_title,
            'meta_title_en' => $a->meta_title_en,
            'meta_description' => $a->meta_description,
            'meta_description_en' => $a->meta_description_en,
        ], ['title', 'excerpt', 'content', 'meta_title', 'meta_description'], $locale);

        return [
            'id' => $a->id,
            'slug' => $a->slug,
            'title' => $localized['title'],
            'excerpt' => $localized['excerpt'],
            'content' => $localized['content'],
            'category' => $a->category ?: 'parfum',
            'author' => $a->author ?: 'Evomi',
            'image' => BelanjaCatalog::articleImageUrl($a->image),
            'published_at' => optional($a->published_at)->toDateString() ?: optional($a->created_at)->toDateString(),
            'title_font_family' => $a->title_font_family,
            'title_font_weight' => $a->title_font_weight,
            'title_font_style' => $a->title_font_style,
            'title_font_size' => $a->title_font_size,
            'excerpt_font_family' => $a->excerpt_font_family,
            'excerpt_font_weight' => $a->excerpt_font_weight,
            'excerpt_font_style' => $a->excerpt_font_style,
            'excerpt_font_size' => $a->excerpt_font_size,
            'content_font_family' => $a->content_font_family,
            'content_font_weight' => $a->content_font_weight,
            'content_font_style' => $a->content_font_style,
            'content_font_size' => $a->content_font_size,
            'font_title' => $this->articleFontInline($a, 'title'),
            'font_excerpt' => $this->articleFontInline($a, 'excerpt'),
            'font_content' => $this->articleFontInline($a, 'content'),
            'title_heading_tag' => ArticleContent::headingLevel($a->title_heading_level),
            'excerpt_heading_tag' => ArticleContent::blockLevel($a->excerpt_heading_level),
            'excerpt_html' => ArticleContent::sanitizeInlineHtml($localized['excerpt']),
            'excerpt_text' => ArticleContent::plainText($localized['excerpt']),
            'content_heading_tag' => ArticleContent::blockLevel($a->content_heading_level),
            'heading_fonts' => ArticleContent::normalizeFonts($a->heading_fonts),
            'heading_font_styles' => ArticleContent::headingFontStyles($a->heading_fonts),
            'content_blocks' => ArticleContent::blocks($localized['content']),
            'content_is_html' => ArticleContent::looksLikeHtml($localized['content']),
            'content_html' => ArticleContent::sanitizeHtml($localized['content']),
            'content_text' => ArticleContent::plainText($localized['content']),
            'heading_css' => ArticleContent::headingCss($a->heading_fonts, '.artikel-detail-body'),
            'meta_title' => $localized['meta_title'],
            'meta_description' => $localized['meta_description'],
            'meta_keywords' => $a->meta_keywords,
            'canonical_url' => $a->canonical_url,
            'noindex' => (bool) $a->noindex,
            'schema_type' => ArticleSeo::schemaType($a->schema_type),
            'schema_json' => $a->schema_json,
            'faqs' => ArticleSeo::localizedFaqs($a->faqs, $locale),
            'updated_at' => optional($a->updated_at)->toIso8601String(),
            'published_at_iso' => optional($a->published_at ?: $a->created_at)->toIso8601String(),
        ];
    }

    /**
     * Inline CSS for article typography fields (parity with Next.js articleFontStyle).
     */
    private function articleFontInline(Article $a, string $prefix): string
    {
        $defaults = match ($prefix) {
            'title' => ['family' => 'nohemi', 'weight' => '700', 'style' => 'normal', 'size' => '40'],
            'excerpt' => ['family' => 'parkinsans', 'weight' => '400', 'style' => 'normal', 'size' => '18'],
            default => ['family' => 'parkinsans', 'weight' => '400', 'style' => 'normal', 'size' => '17'],
        };

        $family = CmsStorefront::fontFamilyCss(
            $a->{"{$prefix}_font_family"} ?? null,
            $defaults['family'],
        );
        $weight = CmsStorefront::fontWeight(
            $a->{"{$prefix}_font_weight"} ?? null,
            $defaults['weight'],
        );
        $style = CmsStorefront::fontStyle(
            $a->{"{$prefix}_font_style"} ?? null,
            $defaults['style'],
        );

        $rawSize = trim((string) ($a->{"{$prefix}_font_size"} ?? ''));
        if ($rawSize === '') {
            $size = $defaults['size'].'px';
        } elseif (preg_match('/^\d+(\.\d+)?(px|rem|em)$/i', $rawSize)) {
            $size = $rawSize;
        } elseif (preg_match('/^\d+(\.\d+)?$/', $rawSize)) {
            $size = $rawSize.'px';
        } else {
            $size = $defaults['size'].'px';
        }

        return "font-family: {$family}; font-weight: {$weight}; font-style: {$style}; font-size: {$size};";
    }

    /**
     * Shared product detail payload for belanja show + kuis result.
     *
     * @return array<string, mixed>|null
     */
    private function productDetailBundle(int $id): ?array
    {
        $product = BelanjaCatalog::find($id);

        if (! $product) {
            return null;
        }

        $gallery = $product['gallery'] ?? null;
        if (! is_array($gallery) || $gallery === []) {
            $imgSrc = ! empty($product['img_url'])
                ? $product['img']
                : asset('src/images/'.$product['img']);
            $gallery = [$imgSrc, $imgSrc, $imgSrc];
        }

        $kurirs = [];
        if (! ShippingConfig::isFreeShipping()) {
            try {
                if (class_exists(KurirTarif::class)) {
                    $kurirIdsWithTarif = KurirTarif::query()
                        ->where('is_active', true)
                        ->where('kota_asal', ShippingConfig::DEFAULT_ORIGIN_CITY)
                        ->distinct()
                        ->pluck('kurir_id');

                    $query = Kurir::query()->active()->orderBy('nama')->orderBy('jenis');

                    if ($kurirIdsWithTarif->isNotEmpty()) {
                        $query->whereIn('id', $kurirIdsWithTarif);
                    }

                    $kurirs = $query->get()
                        ->map(fn (Kurir $k) => [
                            'id' => $k->id,
                            'nama' => $k->nama,
                            'jenis' => $k->jenis,
                            'harga' => (float) $k->harga,
                            'estimasi_hari' => (int) ($k->estimasi_hari ?: 3),
                        ])
                        ->values()
                        ->all();
                }
            } catch (\Throwable) {
                $kurirs = [];
            }

            if ($kurirs === []) {
                $kurirs = BelanjaCatalog::kurirs();
            }
        }

        return [
            'product' => $product,
            'gallery' => $gallery,
            'characterUrl' => asset('src/images/'.$product['character']),
            'kurirs' => $kurirs,
            'disclaimers' => BelanjaCatalog::disclaimers(),
            'promo' => BelanjaCatalog::activePromoAmount(),
            'checkoutPromo' => BelanjaCatalog::activeCheckoutPromo(),
            'showDivider' => false,
            'freeShipping' => ShippingConfig::isFreeShipping(),
        ];
    }
}
