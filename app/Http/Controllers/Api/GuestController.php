<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\GuestCartReminderMail;
use App\Mail\GuestOrdersReminderMail;
use App\Models\Order;
use App\Models\OrderTracking;
use App\Support\LocaleResolver;
use App\Support\OrderNumber;
use App\Support\ProductLocalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class GuestController extends Controller
{
    /**
     * Email a snapshot of the guest cart so items are not lost on device clear.
     */
    public function emailCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'items' => 'required|array|min:1|max:40',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.title' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1|max:99',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.image' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $email = strtolower(trim($data['email']));
        $items = collect($data['items'])->map(function (array $row) {
            $qty = (int) $row['quantity'];
            $price = (float) ($row['price'] ?? 0);

            return [
                'product_id' => (int) $row['product_id'],
                'title' => (string) $row['title'],
                'quantity' => $qty,
                'price' => $price,
                'line_total' => $price * $qty,
                'image' => $row['image'] ?? null,
            ];
        })->values()->all();

        $total = collect($items)->sum('line_total');

        try {
            Mail::to($email)->send(new GuestCartReminderMail($email, $items, $total));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email keranjang. Coba lagi nanti.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Salinan keranjang sudah dikirim ke email Anda.',
        ]);
    }

    /**
     * Lookup guest orders by email (optional email summary).
     */
    public function orders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'notify' => 'sometimes|boolean',
            'locale' => 'sometimes|string|in:id,en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $email = strtolower(trim($validator->validated()['email']));
        $locale = LocaleResolver::normalize($request->input('locale', 'id'));
        $notify = (bool) $request->boolean('notify');

        $history = Order::with('product')
            ->whereNull('user_id')
            ->whereRaw('LOWER(guest_email) = ?', [$email])
            ->orderByDesc('created_at')
            ->get();

        $payload = ProductLocalizer::mapWithProduct($history, $locale);

        if ($notify && $history->isNotEmpty()) {
            try {
                Mail::to($email)->send(new GuestOrdersReminderMail(
                    $email,
                    $this->buildOrderSummaries($history, $locale),
                    'orders',
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    /**
     * Lookup guest trackings by email (optional email summary).
     */
    public function trackings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'notify' => 'sometimes|boolean',
            'locale' => 'sometimes|string|in:id,en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $email = strtolower(trim($validator->validated()['email']));
        $locale = LocaleResolver::normalize($request->input('locale', 'id'));
        $notify = (bool) $request->boolean('notify');

        $orders = Order::with('product')
            ->whereNull('user_id')
            ->whereRaw('LOWER(guest_email) = ?', [$email])
            ->where(function ($q) {
                $q->where('payment_status', Order::PAYMENT_SUCCESS)
                    ->orWhereNotIn('status', ['dibatalkan']);
            })
            ->orderByDesc('created_at')
            ->get();

        $groups = [];
        foreach ($orders as $order) {
            $invoiceRoot = Order::invoiceRoot((string) $order->id);
            $groups[$invoiceRoot][] = $order;
        }

        $data = [];
        foreach ($groups as $invoiceRoot => $lines) {
            /** @var Order $first */
            $first = $lines[0];
            $tracking = OrderTracking::where('order_id', $invoiceRoot)->first()
                ?: OrderTracking::where('order_id', (string) $first->id)->first();

            $product = $first->product;
            $localized = $product ? ProductLocalizer::localize($product, $locale) : null;
            $statusKey = strtolower((string) ($first->status ?: ''));
            $trackStatus = (string) ($tracking?->status ?: '');

            $timeline = collect($tracking?->timeline ?? [])->map(function ($item) {
                $item = is_array($item) ? $item : (array) $item;

                return [
                    'status' => $item['status'] ?? '',
                    'time' => $item['time'] ?? $item['date'] ?? null,
                    'description' => $item['description'] ?? null,
                ];
            })->values()->all();

            $invoiceNumber = OrderNumber::display($invoiceRoot);

            $data[] = [
                'id' => $invoiceRoot,
                'order_id' => (string) $first->id,
                'invoice' => $invoiceNumber,
                'code' => $invoiceNumber,
                'order_number' => $invoiceNumber,
                'title' => $localized['title'] ?? ($product?->title ?? 'Produk Evomi'),
                'image' => $product?->image_2
                    ?: $product?->image_1
                    ?: $product?->image_produk_belanja,
                'accent' => $product?->color ?: '#1172BA',
                'personality_type' => $product?->personality_type,
                'status' => $statusKey,
                'status_label' => $this->storefrontStatusLabel($statusKey, $trackStatus),
                'status_tone' => $this->storefrontStatusTone($statusKey),
                'progress_step' => $this->storefrontProgressStep($statusKey),
                'courier' => $tracking?->courier ?: null,
                'tracking_number' => $tracking?->tracking_number ?: null,
                'estimated_delivery' => $tracking?->estimated_delivery
                    ? $tracking->estimated_delivery->translatedFormat('d F Y')
                    : null,
                'destination' => null,
                'recipient' => [
                    'name' => $tracking?->recipient_name,
                    'phone' => $tracking?->recipient_phone,
                    'address' => $tracking?->recipient_address,
                ],
                'timeline' => $timeline,
                'created_at' => optional($first->created_at)?->toIso8601String(),
            ];
        }

        if ($notify && $orders->isNotEmpty()) {
            try {
                Mail::to($email)->send(new GuestOrdersReminderMail(
                    $email,
                    $this->buildOrderSummaries($orders, $locale),
                    'tracking',
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return list<array{id: string, title: string, status: string, payment: string, total: float|int, tracking_url: string, payment_url: string|null}>
     */
    private function buildOrderSummaries($orders, string $locale): array
    {
        $frontend = rtrim((string) (env('FRONTEND_URL') ?: env('APP_URL') ?: 'https://evomi.shop'), '/');
        $summaries = [];

        foreach ($orders as $order) {
            $invoiceRoot = Order::invoiceRoot((string) $order->id);
            $invoiceNumber = OrderNumber::display($invoiceRoot);
            $product = $order->product;
            $localized = $product ? ProductLocalizer::localize($product, $locale) : null;
            $awaiting = $order->isAwaitingAnyPayment();

            $summaries[] = [
                'id' => (string) $order->id,
                'invoice' => $invoiceNumber,
                'code' => $invoiceNumber,
                'order_number' => $invoiceNumber,
                'title' => $localized['title'] ?? ($product?->title ?? 'Produk Evomi'),
                'status' => (string) $order->status,
                'payment' => (string) $order->payment_status,
                'total' => (float) ($order->total_price ?? 0) + (float) ($order->shipping_cost ?? 0) - (float) ($order->promo_discount ?? 0),
                'tracking_url' => $frontend.'/pengiriman/'.$invoiceRoot,
                'payment_url' => $awaiting ? $frontend.'/pembayaran/'.rawurlencode($invoiceRoot) : null,
            ];
        }

        return $summaries;
    }

    private function storefrontStatusLabel(string $statusKey, string $trackStatus): string
    {
        return match ($statusKey) {
            'diterima', 'selesai' => 'Terkirim',
            'dalam_perjalanan' => 'Dalam Perjalanan',
            'pengemasan' => 'Dikemas',
            'menunggu_konfirmasi' => 'Diproses',
            'dibatalkan' => 'Dibatalkan',
            default => $trackStatus !== '' ? $trackStatus : 'Diproses',
        };
    }

    private function storefrontStatusTone(string $statusKey): string
    {
        return match ($statusKey) {
            'diterima', 'selesai' => 'success',
            'dalam_perjalanan' => 'info',
            'pengemasan', 'menunggu_konfirmasi' => 'warning',
            'dibatalkan' => 'danger',
            default => 'muted',
        };
    }

    private function storefrontProgressStep(string $statusKey): int
    {
        return match ($statusKey) {
            'menunggu_konfirmasi' => 0,
            'pengemasan' => 1,
            'dalam_perjalanan' => 3,
            'diterima', 'selesai' => 4,
            default => 0,
        };
    }
}
