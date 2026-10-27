<?php

namespace App\Http\Requests\Instructor;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuestionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0'],
            'answers' => ['required', 'array', 'min:2', 'max:6'],
            'answers.*' => ['nullable', 'string', 'max:500'],
            'correct_answer' => ['required', 'integer', 'min:0', function (string $attribute, mixed $value, Closure $fail): void {
                if (blank($this->input("answers.{$value}"))) {
                    $fail('Đáp án đúng phải là một đáp án đã được nhập nội dung.');
                }
            }],
        ];
    }

    /**
     * Keep only the answers that were filled in, remembering which one is correct.
     *
     * @return list<array{content: string, is_correct: bool}>
     */
    public function answerRows(): array
    {
        return collect($this->input('answers'))
            ->map(fn (?string $content, int $index) => ['content' => trim((string) $content), 'is_correct' => $index === $this->integer('correct_answer')])
            ->filter(fn (array $answer) => $answer['content'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (count(array_filter($this->input('answers', []), filled(...))) < 2) {
                    $validator->errors()->add('answers', 'Cần nhập ít nhất 2 đáp án.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'content' => 'nội dung câu hỏi',
            'position' => 'thứ tự',
            'answers' => 'đáp án',
            'answers.*' => 'đáp án',
            'correct_answer' => 'đáp án đúng',
        ];
    }
}
