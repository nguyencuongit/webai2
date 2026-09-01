<?php

namespace Botble\AiVideoGenerator\Forms;

use Botble\AiVideoGenerator\Http\Requests\AiContentPostRequest;
use Botble\AiVideoGenerator\Models\AiContentPost;
use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\NameFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\FormAbstract;

class AiContentPostForm extends FormAbstract
{
    public function setup(): void
    {
        $this->model(AiContentPost::class)->setValidatorClass(AiContentPostRequest::class)->columns()
            ->add('title', TextField::class, NameFieldOption::make()->label('Tiêu đề')->required()->maxLength(255)->colspan(2))
            ->add('display_location', SelectField::class, SelectFieldOption::make()->label('Vị trí hiển thị')->choices(['home' => 'Trang chủ', 'intro_modal' => 'Modal giới thiệu'])->required()->colspan(1))
            ->add('status', OnOffField::class, OnOffFieldOption::make()->label('Kích hoạt')->defaultValue(1)->colspan(1))
            ->add('image', MediaImageField::class, MediaImageFieldOption::make()->label('Ảnh')->colspan(1))
            ->add('link', TextField::class, TextFieldOption::make()->label('Link khi bấm')->maxLength(500)->colspan(1))
            ->add('excerpt', TextareaField::class, TextareaFieldOption::make()->label('Mô tả ngắn')->rows(3)->colspan(2))
            ->add('content', TextareaField::class, TextareaFieldOption::make()->label('Nội dung')->rows(8)->colspan(2));
    }
}
