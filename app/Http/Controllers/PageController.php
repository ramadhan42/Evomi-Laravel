<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Faq;
use App\Models\Kurir;
use App\Models\OrderTracking;
use App\Models\Product;
use App\Models\QuizPersonalityResult;
use App\Models\QuizQuestion;
use App\Models\SiteContent;
use App\Support\BelanjaCatalog;
use App\Support\CmsStorefront;
use App\Support\LocaleResolver;
use Illuminate\Http\RedirectResponse;
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
                    'bg' => '/src/images/kuis/peaceful-kanan.png',
                    'product' => '/src/images/kuis/peaceful-produk.png',
                ],
                'rebel_brave' => [
                    'bg' => '/src/images/kuis/rebel-kanan.png',
                    'product' => '/src/images/kuis/rebel-produk.png',
                ],
                'sweet_shy' => [
                    'bg' => '/src/images/kuis/sweet-kanan.png',
                    'product' => '/src/images/kuis/sweet-produk.png',
                ],
                default => [
                    'bg' => '/src/images/kuis/purpose-kanan.png',
                    'product' => '/src/images/kuis/purpose-produk.png',
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
                'subtitle' => $cms['header.subtitle'] ?? 'Punya pertanyaan atau ingin berkolaborasi? Tim Evomi siap mendengarkan Anda.',
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
        $resi = trim($resi);
        $row = OrderTracking::query()->where('order_id', $resi)->first();

        if ($row) {
            $timelineRaw = is_array($row->timeline) ? $row->timeline : [];
            $timeline = [];
            foreach ($timelineRaw as $item) {
                $date = $item['date'] ?? null;
                $parsed = $date ? date_create($date) : null;
                $timeline[] = [
                    'status' => $item['status'] ?? 'Update',
                    'date' => $parsed ? $parsed->format('d M Y') : ($item['date'] ?? ''),
                    'time' => $parsed ? $parsed->format('H:i') : ($item['time'] ?? ''),
                    'description' => $item['description'] ?? ($item['status'] ?? ''),
                ];
            }

            $tracking = [
                'resi' => strtoupper($resi),
                'courier' => $row->courier ?: 'Kurir Evomi',
                'estimatedDelivery' => now()->addDays(3)->translatedFormat('d M Y'),
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
        } else {
            $tracking = [
                'resi' => strtoupper($resi),
                'courier' => '-',
                'estimatedDelivery' => '-',
                'currentStatus' => 'Tidak ditemukan',
                'recipient' => [
                    'name' => '-',
                    'phone' => '-',
                    'address' => '-',
                ],
                'timeline' => [[
                    'status' => 'Resi tidak ditemukan',
                    'date' => now()->translatedFormat('d M Y'),
                    'time' => now()->format('H:i'),
                    'description' => 'Pastikan nomor invoice/resi sudah benar.',
                ]],
            ];
        }

        return view('pages.pengiriman-show', [
            'tracking' => $tracking,
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
        ], ['title', 'excerpt', 'content'], $locale);

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

        $kurirs = Kurir::query()->active()->orderBy('nama')->orderBy('jenis')->get()
            ->map(fn (Kurir $k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'jenis' => $k->jenis,
                'harga' => (float) $k->harga,
                'estimasi_hari' => (int) ($k->estimasi_hari ?: 3),
            ])
            ->values()
            ->all();

        if ($kurirs === []) {
            $kurirs = BelanjaCatalog::kurirs();
        }

        return [
            'product' => $product,
            'gallery' => $gallery,
            'characterUrl' => asset('src/images/'.$product['character']),
            'kurirs' => $kurirs,
            'disclaimers' => BelanjaCatalog::disclaimers(),
            'promo' => BelanjaCatalog::activePromoAmount(),
            'showDivider' => false,
        ];
    }
}
