<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonAttachmentController extends Controller
{
    /**
     * Stream a lesson document to an enrolled learner (or the instructor / an admin) under its original name.
     */
    public function download(Course $course, Lesson $lesson, LessonAttachment $attachment): StreamedResponse
    {
        Gate::authorize('learn', $course);

        $disk = Storage::disk('attachments');

        abort_unless($disk->exists($attachment->path), 404, 'Không tìm thấy tệp tài liệu.');

        return $disk->download($attachment->path, $attachment->name, array_filter([
            // Known from the upload, so no extra call to the storage service is needed.
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Length' => $attachment->size ?: null,
        ]));
    }
}
