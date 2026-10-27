<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\User;

/**
 * Tells the instructor that someone joined (or bought) one of their courses.
 */
class NewStudentEnrolled extends InAppNotification
{
    public function __construct(public Course $course, public User $student, public bool $paid = false) {}

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'users',
            'title' => $this->paid ? 'Có học viên mua khoá học' : 'Có học viên mới',
            'body' => "{$this->student->name} vừa ".($this->paid ? 'mua' : 'ghi danh')." khoá \"{$this->course->title}\".",
            'url' => route('instructor.courses.students', $this->course),
        ];
    }
}
