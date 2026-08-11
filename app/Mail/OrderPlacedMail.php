<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    private const DEFAULT_BRAND = '#1172BA';

    /**
     * @param  array{name: string, phone: string, address: string, courier?: string|null}  $recipient
     * @param  list<array{title: string, quantity: int|float, price: int|float, image_url?: string|null, image_path?: string|null, color?: string|null}>  $items
     */
    public function __construct(
        public Order $order,
        public array $recipient,
        public array $items,
        public string $paymentMethod,
        public float|int $total,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Evomi #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        $frontend = rtrim(
            (string) (env('FRONTEND_URL') ?: env('APP_FRONTEND_URL') ?: 'http://localhost:3000'),
            '/'
        );

        $brand = $this->resolveBrandColor();
        $palette = $this->buildPalette($brand);

        return new Content(
            html: 'emails.order-placed',
            with: [
                'orderId' => $this->order->id,
                'items' => $this->items,
                'paymentMethod' => $this->paymentMethod,
                'total' => $this->total,
                'recipient' => $this->recipient,
                'trackingUrl' => $frontend . '/pengiriman/' . $this->order->id,
                'isGuest' => blank($this->order->user_id),
                'registerUrl' => $frontend . '/register',
                'loginUrl' => $frontend . '/login',
                'brandColor' => $palette['brand'],
                'brandSoft' => $palette['soft'],
                'brandDark' => $palette['dark'],
                'brandLight' => $palette['light'],
                'social' => [
                    'instagram' => 'https://instagram.com/evomi.id',
                    'twitter' => 'https://twitter.com/evomi',
                    'facebook' => 'https://facebook.com/evomi',
                ],
            ],
        );
    }

    /**
     * Prefer the first line-item product color (checkout brand for buy-now).
     */
    private function resolveBrandColor(): string
    {
        foreach ($this->items as $item) {
            $raw = is_array($item) ? ($item['color'] ?? null) : null;
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }

            $normalized = $this->normalizeHex($raw);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return self::DEFAULT_BRAND;
    }

    /**
     * @return array{brand: string, soft: string, dark: string, light: string}
     */
    private function buildPalette(string $brand): array
    {
        return [
            'brand' => $brand,
            'soft' => $this->mixWithWhite($brand, 0.90),
            'dark' => $this->mixWithBlack($brand, 0.55),
            'light' => $this->mixWithWhite($brand, 0.42),
        ];
    }

    private function normalizeHex(string $color): ?string
    {
        $c = trim($color);
        if (preg_match('/^#([0-9A-Fa-f]{3})$/', $c, $m) === 1) {
            $h = strtoupper($m[1]);

            return '#'.$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
        }

        if (preg_match('/^#([0-9A-Fa-f]{6})$/', $c, $m) === 1) {
            return '#'.strtoupper($m[1]);
        }

        if (preg_match('/^([0-9A-Fa-f]{6})$/', $c, $m) === 1) {
            return '#'.strtoupper($m[1]);
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexToRgb(string $hex): array
    {
        $h = ltrim($hex, '#');

        return [
            hexdec(substr($h, 0, 2)),
            hexdec(substr($h, 2, 2)),
            hexdec(substr($h, 4, 2)),
        ];
    }

    private function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02X%02X%02X', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }

    private function mixWithWhite(string $hex, float $whiteRatio): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $w = max(0.0, min(1.0, $whiteRatio));

        return $this->rgbToHex(
            (int) round($r + (255 - $r) * $w),
            (int) round($g + (255 - $g) * $w),
            (int) round($b + (255 - $b) * $w),
        );
    }

    private function mixWithBlack(string $hex, float $blackRatio): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $k = max(0.0, min(1.0, $blackRatio));

        return $this->rgbToHex(
            (int) round($r * (1 - $k)),
            (int) round($g * (1 - $k)),
            (int) round($b * (1 - $k)),
        );
    }
}
