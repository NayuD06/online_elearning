<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', Rule::exists('sections', 'id')->where('course_id', $this->route('course')->id)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:0', 'max:1000'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'section_id' => 'chương',
            'title' => 'tên bài học',
            'content' => 'nội dung',
            'video_url' => 'đường dẫn video',
            'duration_minutes' => 'thời lượng',
            'position' => 'thứ tự',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_preview' => $this->boolean('is_preview')]);
    }
}
