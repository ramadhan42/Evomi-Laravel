<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderNumber;
use App\Services\OrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderPaymentController extends Controller
{
    public function __construct(private OrderPaymentService $payments) {}

    /**
     * GET /api/payments/orders/{invoiceId}
     * Public payment page payload (invoice IDs are unguessable enough for storefront use).
     */
    public function show(Request $request, string $invoiceId)
    {
        $this->payments->expireDueOrders();

        $primary = $this->payments->primaryOrder($invoiceId);
        if (! $primary) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        $orders = $this->payments->ordersForInvoice($invoiceId);
        $sync = $this->payments->syncGatewayStatus($primary->fresh());
        $primary = $this->payments->primaryOrder($invoiceId);
        $orders = $this->payments->ordersForInvoice($invoiceId);

        $amount = $orders->sum(fn (Order $o) => $o->grand_total);
        $meta = is_array($primary->payment_meta) ? $primary->payment_meta : [];
        $product = $primary->product;
        $brand = is_string($product?->color) && trim($product->color) !== ''
            ? trim($product->color)
            : '#1172BA';
        $invoiceRoot = $this->payments->invoiceRoot($primary->id);
        $invoiceNumber = OrderNumber::display($invoiceRoot);

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_id' => $invoiceNumber,
                'order_number' => $invoiceNumber,
                'payment_status' => $primary->payment_status,
                'order_status' => $primary->status,
                'payment_method' => $primary->metode_pembayaran,
                'payment_provider' => $primary->payment_provider,
                'payment_channel' => $primary->payment_channel,
                'payment_ref' => $primary->payment_ref,
                'payment_expires_at' => optional($primary->payment_expires_at)?->toIso8601String(),
                'seconds_remaining' => max(0, (int) ($primary->payment_window_seconds ?? 0)),
                'is_awaiting_payment' => $primary->is_awaiting_payment,
                'is_cod' => $primary->isCodPayment(),
                'is_awaiting_cod' => $primary->isAwaitingCodPayment(),
                'can_cancel' => $primary->canUserCancelUnpaid()
                    && $request->user('sanctum')
                    && (int) $primary->user_id === (int) $request->user('sanctum')->id,
                'sync_status' => $sync['status'],
                'amount' => (float) $amount,
                'brand_color' => $brand,
                'meta' => [
                    'qr_string' => $meta['qr_string'] ?? null,
                    'va_number' => $meta['va_number'] ?? null,
                    'bank' => $meta['bank'] ?? null,
                    'biller_code' => $meta['biller_code'] ?? null,
                    'bill_key' => $meta['bill_key'] ?? null,
                ],
                'items' => $orders->map(fn (Order $o) => [
                    'id' => $o->id,
                    'title' => $o->product?->title ?: 'Produk Evomi',
                    'quantity' => $o->quantity,
                    'price' => (float) $o->total_price,
                    'image' => $o->product?->image_1 ?: $o->product?->image_produk_belanja,
                    'color' => $o->product?->color,
                ])->values(),
                'recipient' => null,
            ],
        ]);
    }

    /**
     * POST /api/payments/orders/{invoiceId}/sync
     */
    public function sync(string $invoiceId)
    {
        $primary = $this->payments->primaryOrder($invoiceId);
        if (! $primary) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        $result = $this->payments->syncGatewayStatus($primary);
        $fresh = $this->payments->primaryOrder($invoiceId);

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_id' => $this->payments->invoiceRoot($primary->id),
                'paid' => $result['paid'],
                'expired' => $result['expired'],
                'status' => $result['status'],
                'payment_status' => $fresh?->payment_status ?? $result['payment_status'],
                'seconds_remaining' => max(0, (int) ($fresh?->payment_window_seconds ?? 0)),
                'is_awaiting_payment' => (bool) ($fresh?->is_awaiting_payment),
            ],
        ]);
    }

    /**
     * POST /api/orders/{invoiceId}/payment-intent
     * Attach gateway refs after order + charge creation.
     */
    public function attachIntent(Request $request, string $invoiceId)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:midtrans,xendit',
            'channel' => 'required|string|in:qris,va',
            'payment_ref' => 'required|string|max:120',
            'meta' => 'nullable|array',
            'meta.qr_string' => 'nullable|string',
            'meta.va_number' => 'nullable|string|max:80',
            'meta.bank' => 'nullable|string|max:30',
            'meta.biller_code' => 'nullable|string|max:40',
            'meta.bill_key' => 'nullable|string|max:80',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $primary = $this->payments->primaryOrder($invoiceId);
        if (! $primary) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        if ($primary->payment_status !== Order::PAYMENT_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak menunggu pembayaran.',
            ], 422);
        }

        // Route is public (guest checkout) — resolve Sanctum user from Bearer token manually.
        $user = $request->user('sanctum') ?? $request->user();
        if ($primary->user_id) {
            $ownsOrder = $user && (int) $user->id === (int) $primary->user_id;
            // Allow first attach right after checkout even if session auth wasn't resolved,
            // as long as payment details are not set yet and order is fresh (< 30 min).
            $freshFirstAttach = empty($primary->payment_ref)
                && $primary->created_at
                && $primary->created_at->gt(now()->subMinutes(30));

            if (! $ownsOrder && ! $freshFirstAttach) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak diizinkan.',
                ], 403);
            }
        }

        $order = $this->payments->attachPaymentIntent(
            $invoiceId,
            $data['provider'],
            $data['channel'],
            $data['payment_ref'],
            $data['meta'] ?? [],
        );

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_id' => $invoiceId,
                'payment_ref' => $order?->payment_ref,
                'payment_url' => url('/pembayaran/'.$invoiceId),
            ],
        ]);
    }

    /**
     * POST /api/payments/orders/{invoiceId}/cancel — user cancels unpaid order.
     */
    public function cancel(Request $request, string $invoiceId)
    {
        $this->payments->expireDueOrders();

        $primary = $this->payments->primaryOrder($invoiceId);
        if (! $primary) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        $invoiceId = $this->payments->invoiceRoot($primary->id);

        $user = $request->user();
        if (! $user || (int) $primary->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak diizinkan.',
            ], 403);
        }

        if (in_array($primary->payment_channel, ['qris', 'va'], true)) {
            $this->payments->syncGatewayStatus($primary);
            $primary = $this->payments->primaryOrder($invoiceId);
            if (! $primary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak ditemukan.',
                ], 404);
            }
        }

        if ($primary->isPaymentSuccessful()) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan sudah dibayar dan tidak bisa dibatalkan dari sini.',
            ], 422);
        }

        $orders = $this->payments->ordersForInvoice($invoiceId);
        if ($orders->contains(fn (Order $o) => $o->hasShipped())) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak bisa dibatalkan karena sudah dikirim / dalam perjalanan.',
            ], 422);
        }

        if (! $primary->canUserCancelUnpaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan ini tidak bisa dibatalkan. Hanya tagihan yang belum dibayar.',
            ], 422);
        }

        $updated = $this->payments->expireInvoice($invoiceId, 'user_cancelled');
        if ($updated < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak bisa dibatalkan.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibatalkan.',
            'data' => [
                'invoice_id' => $invoiceId,
                'cancelled' => $updated,
            ],
        ]);
    }
    public function pending(Request $request)
    {
        $this->payments->expireDueOrders();

        $user = $request->user();
        $orders = Order::with('product')
            ->where('user_id', $user->id)
            ->awaitingAnyPayment()
            ->orderByDesc('created_at')
            ->get();

        // Group by invoice root
        $groups = [];
        foreach ($orders as $order) {
            $root = $this->payments->invoiceRoot($order->id);
            $isCod = $order->isCodPayment();
            if (! isset($groups[$root])) {
                $groups[$root] = [
                    'invoice_id' => $root,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->metode_pembayaran,
                    'payment_channel' => $order->payment_channel,
                    'payment_provider' => $order->payment_provider,
                    'payment_expires_at' => optional($order->payment_expires_at)?->toIso8601String(),
                    'seconds_remaining' => max(0, (int) $order->payment_window_seconds),
                    'amount' => 0.0,
                    'brand_color' => $order->product?->color ?: '#1172BA',
                    'title' => $order->product?->title ?: 'Pesanan Evomi',
                    'image' => $order->product?->image_1 ?: $order->product?->image_produk_belanja,
                    'extra_count' => 0,
                    'is_cod' => $isCod,
                    'can_cancel' => $order->canUserCancelUnpaid(),
                    'order_status' => $order->status,
                    'payment_url' => url('/pembayaran/'.$root),
                    'created_at' => optional($order->created_at)?->toIso8601String(),
                ];
            } else {
                $groups[$root]['extra_count']++;
                if (! $order->canUserCancelUnpaid()) {
                    $groups[$root]['can_cancel'] = false;
                }
            }
            $groups[$root]['amount'] += (float) $order->grand_total;
        }

        return response()->json([
            'success' => true,
            'data' => array_values($groups),
        ]);
    }
}
