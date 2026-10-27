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
        // Saved for the bell first, so it is kept even when the email fails.
        return ['database', 'mail'];
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

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'x',
            'title' => 'Khoá học chưa được duyệt',
            'body' => "\"{$this->course->title}\": {$this->course->rejection_reason}",
            'url' => route('instructor.courses.edit', $this->course),
        ];
    }
}
