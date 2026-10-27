<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UniqueSlug
{
    /**
     * Build a slug from the given text that is unique within the model's table.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function for(string $modelClass, string $text, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($text) ?: Str::lower(Str::random(8));
        $slug = $baseSlug;
        $suffix = 2;

        while ($modelClass::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
