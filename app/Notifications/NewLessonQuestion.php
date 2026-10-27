<?php

namespace App\Notifications;

use App\Models\LessonQuestion;

/**
 * Tells the instructor that a learner asked something under one of their lessons.
 */
class NewLessonQuestion extends InAppNotification
{
    public function __construct(public LessonQuestion $question) {}

    public function toArray(object $notifiable): array
    {
        $lesson = $this->question->lesson;

        return [
            'icon' => 'question',
            'title' => "{$this->question->user->name} vừa đặt câu hỏi",
            'body' => "Bài \"{$lesson->title}\": ".str($this->question->body)->limit(80),
            'url' => route('learn.show', [$lesson->course, $lesson]).'#hoi-dap-'.$this->question->id,
        ];
    }
}
