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
        // Saved for the bell first, so it is kept even when the email fails.
        return ['database', 'mail'];
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

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        $lesson = $this->question->lesson;

        return [
            'icon' => 'chat',
            'title' => 'Câu hỏi của bạn đã có trả lời',
            'body' => "Bài \"{$lesson->title}\" trong khoá {$lesson->course->title}.",
            'url' => route('learn.show', [$lesson->course, $lesson]).'#hoi-dap-'.$this->question->id,
        ];
    }
}
