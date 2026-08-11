<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestCartReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{product_id:int,title:string,quantity:int,price:float|int,line_total:float|int,image:?string}>  $items
     */
    public function __construct(
        public string $guestEmail,
        public array $items,
        public float|int $total,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Salinan Keranjang Evomi — simpan sebelum hilang',
        );
    }

    public function content(): Content
    {
        $frontend = rtrim((string) (env('FRONTEND_URL') ?: env('APP_URL') ?: 'https://evomi.shop'), '/');

        return new Content(
            html: 'emails.guest-cart-reminder',
            with: [
                'guestEmail' => $this->guestEmail,
                'items' => $this->items,
                'total' => $this->total,
                'registerUrl' => $frontend.'/register',
                'loginUrl' => $frontend.'/login',
                'checkoutUrl' => $frontend.'/checkout?type=cart',
                'brandColor' => '#1172BA',
            ],
        );
    }
}
