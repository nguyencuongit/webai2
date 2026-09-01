<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Models\AiVideoModel;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class AiVideoModelRequest extends Request
{
    public function rules(): array
    {
        $model = $this->route('aiVideoModel');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:120', Rule::unique((new AiVideoModel())->getTable(), 'code')->ignore($model)],
            'status' => ['required', 'boolean'],
        ];
    }
}
