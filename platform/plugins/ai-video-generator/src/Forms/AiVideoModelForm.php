<?php

namespace Botble\AiVideoGenerator\Forms;

use Botble\AiVideoGenerator\Http\Requests\AiVideoModelRequest;
use Botble\AiVideoGenerator\Models\AiVideoModel;
use Botble\Base\Forms\FieldOptions\NameFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;

class AiVideoModelForm extends FormAbstract
{
    public function setup(): void
    {
        $this->model(AiVideoModel::class)->setValidatorClass(AiVideoModelRequest::class)->columns()
            ->add('name', TextField::class, NameFieldOption::make()->required()->maxLength(120)->colspan(1))
            ->add('code', TextField::class, TextFieldOption::make()->label('Mã model')->required()->maxLength(120)->colspan(1))
            ->add('status', OnOffField::class, OnOffFieldOption::make()->label('Kích hoạt')->defaultValue(1)->colspan(2));
    }
}
