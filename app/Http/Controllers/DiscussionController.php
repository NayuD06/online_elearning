<?php

namespace App\Http\Controllers;

use App\Models\LessonQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inbox of lesson questions: every course for admins, their own courses for instructors.
 */
class DiscussionController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $status = $request->string('status', 'unanswered')->toString();

        $questions = LessonQuestion::questions()
            ->with(['user', 'lesson.course', 'answers.user'])
            ->unless($user->isAdmin(), fn (Builder $query) => $query->whereHas(
                'lesson.course', fn (Builder $query) => $query->where('instructor_id', $user->id)
            ))
            ->when($status === 'unanswered', fn (Builder $query) => $query->doesntHave('answers'))
            ->when($request->string('q')->toString(), fn (Builder $query, string $keyword) => $query->where('body', 'like', "%{$keyword}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('discussions.index', [
            'questions' => $questions,
            'status' => $status,
        ]);
    }
}
