<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the instructor that an admin approved and published their course.
 */
class CourseApproved extends Notification
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
            ->subject("Khoá học \"{$this->course->title}\" đã được duyệt")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Khoá học \"{$this->course->title}\" của bạn đã được quản trị viên duyệt và đang hiển thị công khai trên trang web.")
            ->action('Xem khoá học', route('courses.show', $this->course))
            ->line('Cảm ơn bạn đã chia sẻ kiến thức cùng học viên!');
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'check-circle',
            'title' => 'Khoá học đã được duyệt',
            'body' => "\"{$this->course->title}\" đã được xuất bản công khai.",
            'url' => route('courses.show', $this->course),
        ];
    }
}
