<?php

namespace App\Enums;

/**
 * Course lifecycle: draft → pending → published (or rejected → draft, or hidden).
 */
enum CourseStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bản nháp',
            self::Pending => 'Chờ duyệt',
            self::Published => 'Đã xuất bản',
            self::Rejected => 'Bị từ chối',
            self::Hidden => 'Đã ẩn',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-600',
            self::Pending => 'bg-amber-100 text-amber-700',
            self::Published => 'bg-emerald-100 text-emerald-700',
            self::Rejected => 'bg-rose-100 text-rose-700',
            self::Hidden => 'bg-slate-200 text-slate-700',
        };
    }

    /**
     * Whether the instructor may send the course to the admin for review.
     */
    public function canBeSubmitted(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }
}
