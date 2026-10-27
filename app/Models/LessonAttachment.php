<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A downloadable document of a lesson (PDF, slides...), kept on the private "attachments" disk.
 */
#[Fillable(['lesson_id', 'name', 'path', 'size', 'mime_type'])]
class LessonAttachment extends Model
{
    /**
     * File types instructors may attach, and the largest size in kilobytes
     * (Cloudinary's free plan refuses raw files over 10 MB).
     */
    public const array EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip'];

    public const int MAX_KILOBYTES = 10240;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Save an uploaded file of the lesson under a random name, keeping its original name for downloads.
     */
    public static function storeFor(Lesson $lesson, UploadedFile $file): self
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return $lesson->attachments()->create([
            'name' => Str::limit(basename($file->getClientOriginalName()), 200, ''),
            'path' => $file->storeAs('lesson-'.$lesson->id, Str::random(40).'.'.$extension, 'attachments'),
            'size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);
    }

    /**
     * Remove the stored files of the given attachments (their rows go with the lesson or course).
     *
     * @param  iterable<LessonAttachment>  $attachments
     */
    public static function deleteFiles(iterable $attachments): void
    {
        $paths = collect($attachments)->pluck('path')->all();

        if ($paths) {
            Storage::disk('attachments')->delete($paths);
        }
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    protected function extension(): Attribute
    {
        return Attribute::get(fn (): string => strtolower(pathinfo($this->name, PATHINFO_EXTENSION)));
    }

    protected function sizeLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->size >= 1048576
            ? number_format($this->size / 1048576, 1, ',', '.').' MB'
            : max(1, (int) round($this->size / 1024)).' KB');
    }
}
