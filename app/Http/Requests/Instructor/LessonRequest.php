<?php

namespace App\Http\Requests\Instructor;

use App\Models\Lesson;
use App\Models\LessonAttachment;
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
            'video_url' => ['nullable', 'url:http,https', 'max:255'],
            'video' => [
                'nullable',
                'file',
                'extensions:'.implode(',', Lesson::VIDEO_EXTENSIONS),
                'mimetypes:'.implode(',', Lesson::VIDEO_MIME_TYPES),
                'max:'.Lesson::MAX_VIDEO_KILOBYTES,
            ],
            'remove_video' => ['boolean'],
            'duration_minutes' => ['required', 'integer', 'min:0', 'max:1000'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['boolean'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [
                'file',
                'extensions:'.implode(',', LessonAttachment::EXTENSIONS),
                'mimes:'.implode(',', LessonAttachment::EXTENSIONS),
                'max:'.LessonAttachment::MAX_KILOBYTES,
            ],
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
            'video' => 'video bài giảng',
            'duration_minutes' => 'thời lượng',
            'position' => 'thứ tự',
            'attachments' => 'tài liệu đính kèm',
            'attachments.*' => 'tài liệu đính kèm',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $megabytes = Lesson::MAX_VIDEO_KILOBYTES / 1024;

        return [
            'video.extensions' => 'Video phải là tệp MP4 hoặc WebM.',
            'video.mimetypes' => 'Video phải là tệp MP4 hoặc WebM.',
            'video.max' => "Video không được lớn hơn {$megabytes} MB.",
            'video.uploaded' => "Tải video lên thất bại. Tệp có thể vượt giới hạn tải lên của máy chủ ({$megabytes} MB).",
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_preview' => $this->boolean('is_preview'),
            'remove_video' => $this->boolean('remove_video'),
        ]);
    }
}
