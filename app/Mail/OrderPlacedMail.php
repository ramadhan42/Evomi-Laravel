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

    /**
     * @param  array{name: string, phone: string, address: string, courier?: string|null}  $recipient
     * @param  list<array{title: string, quantity: int|float, price: int|float, image_url?: string|null, image_path?: string|null}>  $items
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

        return new Content(
            html: 'emails.order-placed',
            with: [
                'orderId' => $this->order->id,
                'items' => $this->items,
                'paymentMethod' => $this->paymentMethod,
                'total' => $this->total,
                'recipient' => $this->recipient,
                'trackingUrl' => $frontend . '/pengiriman/' . $this->order->id,
                'social' => [
                    'instagram' => 'https://instagram.com/evomi.id',
                    'twitter' => 'https://twitter.com/evomi',
                    'facebook' => 'https://facebook.com/evomi',
                ],
            ],
        );
    }
}
