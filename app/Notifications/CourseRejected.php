<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the instructor why an admin rejected their course, so they can fix it and resubmit.
 */
class CourseRejected extends Notification
{
    public function __construct(public Course $course) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Khoá học \"{$this->course->title}\" chưa được duyệt")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Khoá học \"{$this->course->title}\" của bạn chưa được duyệt với lý do:")
            ->line($this->course->rejection_reason)
            ->action('Sửa khoá học', route('instructor.courses.edit', $this->course))
            ->line('Sau khi chỉnh sửa, bấm "Gửi duyệt" để quản trị viên xem lại.');
    }
}
