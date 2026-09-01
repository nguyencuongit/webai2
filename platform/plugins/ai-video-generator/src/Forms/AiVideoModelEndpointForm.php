<?php

namespace Botble\AiVideoGenerator\Forms;

use Botble\AiVideoGenerator\Enums\AiVideoModelEndpointTag;
use Botble\AiVideoGenerator\Http\Requests\AiVideoModelEndpointRequest;
use Botble\AiVideoGenerator\Models\AiVideoModelEndpoint;
use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\FormAbstract;

class AiVideoModelEndpointForm extends FormAbstract
{
    public function setup(): void
    {
        $this->model(AiVideoModelEndpoint::class)->setValidatorClass(AiVideoModelEndpointRequest::class);

        $endpoint = $this->getModel();

        $this->columns()
            ->add('name', TextField::class, TextFieldOption::make()->label('Tên endpoint')->required()->maxLength(120)->colspan(1))
            ->add('code', TextField::class, TextFieldOption::make()->label('Mã model nhỏ')->required()->maxLength(120)->colspan(1))
            ->add('image', MediaImageField::class, MediaImageFieldOption::make()->label('Ảnh')->colspan(1))
            ->add('description', TextareaField::class, TextareaFieldOption::make()->label('Thông tin chi tiết')->rows(4)->colspan(1))
            ->add('tag', SelectField::class, SelectFieldOption::make()->label('Nhãn hiển thị')->choices(AiVideoModelEndpointTag::choices())->placeholder('Không chọn')->emptyValue('')->colspan(1))
            ->add('price', NumberField::class, NumberFieldOption::make()->label('Giá')->required()->value($endpoint->exists ? (int) $endpoint->price : null)->attributes(['min' => 0, 'step' => 1])->colspan(1))
            ->add('status', OnOffField::class, OnOffFieldOption::make()->label('Kích hoạt')->defaultValue(1)->colspan(1));
    }
}
