<?php

namespace App\Enums;

enum CourseLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    /**
     * Get the Vietnamese label of the level.
     */
    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Cơ bản',
            self::Intermediate => 'Trung cấp',
            self::Advanced => 'Nâng cao',
        };
    }
}
