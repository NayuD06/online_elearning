<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    /**
     * Get the Vietnamese label of the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Quản trị viên',
            self::Teacher => 'Giảng viên',
            self::Student => 'Học viên',
        };
    }

    /**
     * Get the badge color classes of the role.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Admin => 'bg-rose-100 text-rose-700',
            self::Teacher => 'bg-amber-100 text-amber-700',
            self::Student => 'bg-teal-100 text-teal-700',
        };
    }
}
