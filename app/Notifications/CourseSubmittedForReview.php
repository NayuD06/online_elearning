<?php

namespace App\Notifications;

use App\Models\Course;

/**
 * Tells the admins that an instructor sent a course for review.
 */
class CourseSubmittedForReview extends InAppNotification
{
    public function __construct(public Course $course, public bool $resubmitted = false) {}

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'clock',
            'title' => $this->resubmitted ? 'Khoá học đã sửa, chờ duyệt lại' : 'Khoá học mới chờ duyệt',
            'body' => "\"{$this->course->title}\" của {$this->course->instructor->name}.",
            'url' => route('admin.courses.index', ['status' => 'pending']),
        ];
    }
}
