<?php

namespace App\Support;

use App\Models\Order;

class OrderNumber
{
    public const PREFIX = 'INV-';

    /** Characters after INV- (total id length = 10, e.g. INV-1234AG). */
    public const SUFFIX_LENGTH = 6;

    public const SUFFIX_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';

    /**
     * Neat storefront invoice, e.g. INV-1234AG (10 characters).
     */
    public static function isInvoiceCode(string $value): bool
    {
        return preg_match('/^INV-[A-Z0-9]{6}$/', trim($value)) === 1;
    }

    /**
     * Old timestamp-based ids from earlier checkouts.
     */
    public static function isLegacyInvoice(string $value): bool
    {
        return preg_match('/^INV-\d+-\d+(?:-\d+)?$/', trim($value)) === 1;
    }

    public static function isKnownInvoice(string $value): bool
    {
        return self::isInvoiceCode($value) || self::isLegacyInvoice($value);
    }

    /**
     * Invoice root for grouping / tracking.
     */
    public static function invoiceRoot(string $orderId): string
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            return $orderId;
        }

        if (preg_match('/^(INV-[A-Z0-9]{6})(?:-\d+)?$/', $orderId, $m) === 1) {
            return $m[1];
        }

        if (preg_match('/^(INV-\d+-\d+)(?:-\d+)?$/', $orderId, $m) === 1) {
            return $m[1];
        }

        return $orderId;
    }

    /**
     * Public order number = invoice id (no separate display code).
     */
    public static function display(string $orderId): string
    {
        return self::invoiceRoot($orderId);
    }

    public static function generateUnique(): string
    {
        for ($attempt = 0; $attempt < 40; $attempt++) {
            $code = self::generate();
            if (! Order::existsForInvoice($code)) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate unique order number.');
    }

    public static function resolveCheckoutInvoiceId(?string $invoiceId): string
    {
        $invoiceId = trim((string) $invoiceId);
        if ($invoiceId !== '' && self::isKnownInvoice($invoiceId)) {
            return self::invoiceRoot($invoiceId);
        }

        return self::generateUnique();
    }

    public static function resolveQuery(string $query): ?string
    {
        $query = trim(urldecode($query));
        if ($query === '') {
            return null;
        }

        $normalized = strtoupper($query);
        if (self::isInvoiceCode($normalized) && Order::existsForInvoice($normalized)) {
            return $normalized;
        }

        if (self::isLegacyInvoice($query)) {
            $root = self::invoiceRoot($query);
            if (Order::existsForInvoice($root)) {
                return $root;
            }
        }

        return null;
    }

    public static function generate(): string
    {
        $chars = self::SUFFIX_CHARSET;
        $max = strlen($chars) - 1;
        $suffix = '';
        for ($i = 0; $i < self::SUFFIX_LENGTH; $i++) {
            $suffix .= $chars[random_int(0, $max)];
        }

        return self::PREFIX.$suffix;
    }
}
