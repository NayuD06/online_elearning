<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['course_id', 'section_id', 'title', 'content', 'video_url', 'video_path', 'duration_minutes', 'position', 'is_preview'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /**
     * Video files instructors may upload, and the largest size in kilobytes
     * (Cloudinary's free plan refuses videos over 100 MB).
     */
    public const array VIDEO_EXTENSIONS = ['mp4', 'webm'];

    public const array VIDEO_MIME_TYPES = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v'];

    public const int MAX_VIDEO_KILOBYTES = 102400;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_preview' => 'boolean',
            'duration_minutes' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return HasMany<LessonAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class)->oldest('id');
    }

    /**
     * Questions asked under the lesson (answers hang off each question).
     *
     * @return HasMany<LessonQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(LessonQuestion::class)->whereNull('parent_id');
    }

    /**
     * Store an uploaded video for the lesson under a random name, replacing the previous one.
     */
    public function replaceVideo(UploadedFile $file): void
    {
        $previousPath = $this->video_path;

        $this->update([
            'video_path' => $file->storeAs('lesson-videos', Str::random(40).'.'.strtolower($file->getClientOriginalExtension()), 'media'),
        ]);

        if ($previousPath) {
            Storage::disk('media')->delete($previousPath);
        }
    }

    public function removeVideo(): void
    {
        if ($this->video_path) {
            Storage::disk('media')->delete($this->video_path);
            $this->update(['video_path' => null]);
        }
    }

    /**
     * Remove the uploaded videos of the given lessons (their rows go with the lesson or course).
     *
     * @param  iterable<Lesson>  $lessons
     */
    public static function deleteVideos(iterable $lessons): void
    {
        $paths = collect($lessons)->pluck('video_path')->filter()->all();

        if ($paths) {
            Storage::disk('media')->delete($paths);
        }
    }

    /**
     * Address of the uploaded video file (Cloudinary or storage/app/public).
     */
    protected function videoFileUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->video_path
            ? Storage::disk('media')->url($this->video_path)
            : null);
    }

    /**
     * Whether the lesson has any video: an uploaded file or a link.
     */
    protected function hasVideo(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) ($this->video_path || $this->video_url));
    }

    /**
     * Convert a YouTube watch/share URL into an embeddable URL.
     */
    protected function embedUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->video_url) {
                return null;
            }

            if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([\w-]{11})~', $this->video_url, $matches)) {
                return 'https://www.youtube.com/embed/'.$matches[1];
            }

            return null;
        });
    }
}
