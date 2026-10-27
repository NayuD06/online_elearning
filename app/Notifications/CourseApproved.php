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
        return ['mail'];
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
}
