<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Faq;
use App\Models\Kurir;
use App\Models\OrderTracking;
use App\Models\Product;
use App\Models\QuizPersonalityResult;
use App\Models\QuizQuestion;
use App\Support\BelanjaCatalog;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function beranda(): View
    {
        return view('pages.beranda');
    }

    public function belanja(): View
    {
        return view('pages.belanja', [
            'products' => array_values(BelanjaCatalog::all()),
        ]);
    }

    public function belanjaShow(int $id): View|RedirectResponse
    {
        $product = BelanjaCatalog::find($id);

        if (! $product) {
            return redirect()->route('belanja');
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

        return view('pages.belanja-show', [
            'product' => $product,
            'gallery' => $gallery,
            'characterUrl' => asset('src/images/'.$product['character']),
            'kurirs' => $kurirs,
            'disclaimers' => BelanjaCatalog::disclaimers(),
            'promo' => 25000,
            'themeAccent' => $product['accent'] ?? '#1172BA',
        ]);
    }

    public function checkout(): View
    {
        return view('pages.checkout', [
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
            $articles = SiteContent::articles();
        }

        return view('pages.artikel', [
            'articles' => $articles,
        ]);
    }

    public function artikelShow(string $slug): View|RedirectResponse
    {
        $model = Article::query()->published()->where('slug', $slug)->first();
        $article = $model ? $this->mapArticle($model) : SiteContent::findArticle($slug);

        if (! $article) {
            return redirect()->route('artikel');
        }

        $all = Article::query()->published()->latest('published_at')->get()->map(fn (Article $a) => $this->mapArticle($a))->all();
        if ($all === []) {
            $all = SiteContent::articles();
        }

        $related = array_values(array_filter(
            $all,
            fn (array $item) => $item['slug'] !== $slug && ($item['category'] ?? '') === ($article['category'] ?? '')
        ));
        $related = array_slice($related, 0, 3);

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
        $questions = QuizQuestion::query()
            ->with(['options' => fn ($q) => $q->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(function (QuizQuestion $q) {
                return [
                    'id' => $q->id,
                    'text' => $q->question_text,
                    'options' => $q->options->map(fn ($o) => [
                        'id' => $o->id,
                        'text' => $o->option_text,
                        'peaceful_calm' => (int) ($o->peaceful_calm_score ?? 0),
                        'purpose_prestige' => (int) ($o->prestige_score ?? 0),
                        'sweet_shy' => (int) ($o->sweet_shy_score ?? 0),
                        'rebel_brave' => (int) ($o->rebel_brave_score ?? 0),
                    ])->values()->all(),
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

            $results[$frontendKey] = [
                'title' => $row->title,
                'description' => $row->description,
                'color' => $row->color ?: '#1172BA',
                'product_id' => (string) ($productId ?: ''),
            ];
        }

        if ($questions === []) {
            $questions = SiteContent::quizQuestions();
        }
        if ($results === []) {
            $results = SiteContent::quizResults();
        }

        return view('pages.kuis', [
            'questions' => $questions,
            'results' => $results,
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
            $faqGroups = SiteContent::faqs();
        }

        return view('pages.faq', [
            'faqGroups' => $faqGroups,
        ]);
    }

    public function kontak(): View
    {
        return view('pages.kontak');
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
        $image = (string) ($a->image ?: '');
        if ($image !== '' && ! str_starts_with($image, 'http') && ! str_starts_with($image, '/')) {
            $image = '/'.$image;
        }

        return [
            'id' => $a->id,
            'slug' => $a->slug,
            'title' => $a->title,
            'excerpt' => $a->excerpt,
            'content' => $a->content,
            'category' => $a->category ?: 'Tips',
            'author' => $a->author ?: 'Evomi',
            'image' => $image ?: '/src/images/articles/article-1.jpg',
            'published_at' => optional($a->published_at)->toDateString() ?: optional($a->created_at)->toDateString(),
        ];
    }
}
