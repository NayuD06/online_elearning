<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a user whether an admin accepted their request to become an instructor.
 */
class InstructorRequestReviewed extends Notification
{
    public function __construct(public bool $approved) {}

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
        $message = (new MailMessage)->greeting("Xin chào {$notifiable->name},");

        if (! $this->approved) {
            return $message
                ->subject('Yêu cầu làm giảng viên chưa được chấp nhận')
                ->line('Quản trị viên chưa chấp nhận yêu cầu làm giảng viên của bạn. Bạn vẫn học bình thường với tài khoản học viên.')
                ->line('Bạn có thể cập nhật phần giới thiệu bản thân trong hồ sơ rồi gửi lại yêu cầu.');
        }

        return $message
            ->subject('Bạn đã trở thành giảng viên')
            ->line('Quản trị viên đã duyệt yêu cầu làm giảng viên của bạn. Từ giờ bạn có thể tạo khoá học và gửi duyệt.')
            ->action('Vào kênh giảng viên', route('instructor.dashboard'));
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return $this->approved ? [
            'icon' => 'academic',
            'title' => 'Bạn đã trở thành giảng viên',
            'body' => 'Từ giờ bạn có thể tạo khoá học và gửi duyệt.',
            'url' => route('instructor.dashboard'),
        ] : [
            'icon' => 'x',
            'title' => 'Yêu cầu làm giảng viên chưa được chấp nhận',
            'body' => 'Bạn có thể cập nhật hồ sơ rồi gửi lại yêu cầu.',
            'url' => route('profile.edit'),
        ];
    }
}
