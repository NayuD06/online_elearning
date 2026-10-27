<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case Vnpay = 'vnpay';
    case Momo = 'momo';
    // Fake gateway for demos without sandbox keys; only offered when PAYMENT_DEMO=true.
    case Demo = 'demo';

    public function label(): string
    {
        return match ($this) {
            self::Vnpay => 'VNPay',
            self::Momo => 'Ví MoMo',
            self::Demo => 'Thanh toán thử (demo)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Vnpay => 'Thẻ ATM nội địa, thẻ quốc tế hoặc quét mã VNPay-QR',
            self::Momo => 'Thanh toán bằng ứng dụng ví MoMo',
            self::Demo => 'Không qua cổng thanh toán, chỉ dùng để chạy thử',
        };
    }

    /**
     * Gateways that have their keys set in .env, in display order.
     *
     * @return list<self>
     */
    public static function available(): array
    {
        return array_values(array_filter(self::cases(), fn (self $gateway) => $gateway->isConfigured()));
    }

    public function isConfigured(): bool
    {
        return match ($this) {
            self::Vnpay => filled(config('payment.vnpay.tmn_code')) && filled(config('payment.vnpay.hash_secret')),
            self::Momo => filled(config('payment.momo.partner_code')) && filled(config('payment.momo.access_key')) && filled(config('payment.momo.secret_key')),
            self::Demo => (bool) config('payment.demo'),
        };
    }
}
