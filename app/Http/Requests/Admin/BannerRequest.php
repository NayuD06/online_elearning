<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            // Required when creating; when editing, an empty input keeps the current image.
            'image' => [$this->route('banner') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            // A page of this site ("/khoa-hoc?...") or a full http(s) address; never "javascript:" and the like.
            'link_url' => ['nullable', 'string', 'max:255', 'regex:~^(https?://[^\s]+|/(?!/)[^\s]*)$~i'],
            'button_label' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($this->filled('starts_at'), 'after:starts_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'tiêu đề',
            'subtitle' => 'mô tả ngắn',
            'image' => 'ảnh banner',
            'link_url' => 'đường dẫn',
            'button_label' => 'chữ trên nút',
            'position' => 'thứ tự',
            'starts_at' => 'ngày bắt đầu',
            'ends_at' => 'ngày kết thúc',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'link_url.regex' => 'Đường dẫn phải bắt đầu bằng / (trang trong web) hoặc https://.',
            'image.mimes' => 'Ảnh banner phải là JPG, PNG hoặc WebP.',
            'image.max' => 'Ảnh banner không được lớn hơn 4 MB.',
            'ends_at.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
