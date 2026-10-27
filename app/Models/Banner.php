<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A slide of the home page banner, managed by admins.
 */
#[Fillable(['title', 'subtitle', 'image', 'link_url', 'button_label', 'position', 'is_active', 'starts_at', 'ends_at'])]
class Banner extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Banners switched on whose display period (if any) includes now, in display order.
     *
     * @param  Builder<Banner>  $query
     */
    public function scopeShowing(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * What visitors see now: shown, switched off, scheduled or expired.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            ! $this->is_active => 'Đang tắt',
            $this->starts_at?->isFuture() => 'Chờ đến ngày',
            $this->ends_at?->isPast() => 'Đã hết hạn',
            default => 'Đang hiển thị',
        });
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image
            ? Storage::disk('media')->url($this->image)
            : null);
    }

    /**
     * Links to this site's own pages open in the same tab, the rest in a new one.
     */
    protected function isExternalLink(): Attribute
    {
        return Attribute::get(fn (): bool => $this->link_url !== null
            && ! str_starts_with($this->link_url, '/')
            && parse_url($this->link_url, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST));
    }
}
