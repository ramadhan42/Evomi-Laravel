<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestOrdersReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{id:string,invoice:string,title:string,status:string,payment:string,total:float|int,tracking_url:string,payment_url:?string}>  $orders
     */
    public function __construct(
        public string $guestEmail,
        public array $orders,
        public string $kind = 'orders',
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->kind === 'tracking'
            ? 'Ringkasan pelacakan pesanan Evomi (guest)'
            : 'Ringkasan pesanan Evomi (guest)';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $frontend = (string) config('evomi.frontend_url');

        return new Content(
            html: 'emails.guest-orders-reminder',
            with: [
                'guestEmail' => $this->guestEmail,
                'orders' => $this->orders,
                'kind' => $this->kind,
                'registerUrl' => $frontend.'/register',
                'loginUrl' => $frontend.'/login',
                'brandColor' => '#1172BA',
            ],
        );
    }
}
