<?php

namespace App\Notifications;

use App\Models\LessonQuestion;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a learner that the instructor or an admin answered their question under a lesson.
 */
class LessonQuestionAnswered extends Notification
{
    public function __construct(public LessonQuestion $question) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lesson = $this->question->lesson;

        return (new MailMessage)
            ->subject("Câu hỏi của bạn trong bài \"{$lesson->title}\" đã có trả lời")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Câu hỏi của bạn trong bài \"{$lesson->title}\" vừa được trả lời.")
            ->action('Xem câu trả lời', route('learn.show', [$lesson->course, $lesson]).'#hoi-dap-'.$this->question->id);
    }
}
