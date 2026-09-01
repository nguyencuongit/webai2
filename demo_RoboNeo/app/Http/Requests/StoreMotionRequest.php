<?php

namespace App\Http\Requests;

use App\Models\RoboNeoAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roboneo_account_id' => [
                Rule::requiredIf((bool) config('roboneo.live_enabled')),
                'nullable',
                Rule::exists(RoboNeoAccount::class, 'id')->where('is_active', true),
            ],
            'prompt' => ['required', 'string', 'max:2000'],
            'quality' => ['required', 'in:std'],
            'duration_seconds' => ['required', 'integer', 'between:3,30'],
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'],
            'video' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/quicktime,video/webm,application/octet-stream',
                'max:102400',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'roboneo_account_id.required' => 'Hãy chọn một tài khoản RoboNeo đang hoạt động.',
            'roboneo_account_id.exists' => 'Hãy chọn một tài khoản RoboNeo đang hoạt động.',
            'image.max' => 'Ảnh không được vượt quá 10 MB.',
            'image.mimetypes' => 'Ảnh phải là JPG, PNG hoặc WEBP.',
            'video.max' => 'Video không được vượt quá 100 MB.',
            'video.mimetypes' => 'Video phải là MP4, MOV hoặc WEBM.',
            'duration_seconds.between' => 'Thời lượng phải nằm trong khoảng 3–30 giây.',
        ];
    }
}
