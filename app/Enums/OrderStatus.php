<?php

namespace App\Enums;

/**
 * Payment state of an order: pending until the gateway answers, then paid or failed.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ thanh toán',
            self::Paid => 'Đã thanh toán',
            self::Failed => 'Thất bại',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-700',
            self::Paid => 'bg-emerald-100 text-emerald-700',
            self::Failed => 'bg-rose-100 text-rose-700',
        };
    }
}
