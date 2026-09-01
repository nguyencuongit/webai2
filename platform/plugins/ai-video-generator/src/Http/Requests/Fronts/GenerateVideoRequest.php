<?php

namespace Botble\AiVideoGenerator\Http\Requests\Fronts;

use Botble\AiVideoGenerator\Api\MagnificApiCatalog;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class GenerateVideoRequest extends Request
{
    public function rules(): array
    {
        $catalog = app(MagnificApiCatalog::class);
        $model = $catalog->get((string) $this->input('model'));

        $rules = [
            'prompt' => ['required', 'string', 'max:5000'],
            'model' => ['required', 'string', 'max:120', Rule::in(array_keys($catalog->all()))],
            'aspect_ratio' => ['nullable', 'string', 'max:20'],
            'quality' => ['nullable', 'string', 'max:20'],
            'duration' => ['nullable', 'string', 'max:20'],
            'mode' => ['nullable', 'string', 'max:50'],
            'count' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'string', 'max:2000'],
            'image_end' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'string', 'max:2000'],
            'video_url' => ['nullable', 'string', 'max:2000'],
            'character_orientation' => ['nullable', 'string', 'in:video,image'],
            'cfg_scale' => ['nullable', 'numeric'],
            'multi_prompt' => ['nullable', 'array'],
            'multi_prompt.*' => ['array'],
            'start_image_url' => ['nullable', 'string', 'max:2000'],
            'end_image_url' => ['nullable', 'string', 'max:2000'],
            'elements' => ['nullable', 'array'],
            'elements.*' => ['array'],
            'generate_audio' => ['nullable', 'boolean'],
            'multi_shot' => ['nullable', 'boolean'],
            'shot_type' => ['nullable', 'string', 'in:customize,intelligent'],
        ];

        foreach ($model['required_fields'] ?? [] as $field) {
            $rules[$field] = $this->requiredRules($rules[$field] ?? ['string']);
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'prompt' => 'mô tả video',
            'model' => 'model',
            'aspect_ratio' => 'tỷ lệ',
            'quality' => 'chất lượng',
            'duration' => 'thời lượng',
            'mode' => 'chế độ',
            'count' => 'số kết quả',
            'image' => 'anh',
            'image_end' => 'anh ket thuc',
            'image_url' => 'anh',
            'video_url' => 'video',
            'character_orientation' => 'huong nhan vat',
            'cfg_scale' => 'cfg scale',
            'multi_prompt' => 'multi prompt',
            'start_image_url' => 'anh bat dau',
            'end_image_url' => 'anh ket thuc',
            'elements' => 'elements',
            'generate_audio' => 'generate audio',
            'multi_shot' => 'multi shot',
            'shot_type' => 'shot type',
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Vui lòng nhập mô tả video.',
            'prompt.max' => 'Mô tả video không được vượt quá :max ký tự.',
        ];
    }

    protected function requiredRules(array $rules): array
    {
        $rules = array_values(array_filter($rules, static fn ($rule) => $rule !== 'nullable'));

        array_unshift($rules, 'required');

        return array_values(array_unique($rules));
    }
}
