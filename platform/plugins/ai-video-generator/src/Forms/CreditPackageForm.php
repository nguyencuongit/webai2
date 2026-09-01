<?php

namespace Botble\AiVideoGenerator\Forms;

use Botble\AiVideoGenerator\Http\Requests\CreateCreditPackageRequest;
use Botble\AiVideoGenerator\Models\AiVideoCreditPackage;
use Botble\Base\Forms\FieldOptions\NameFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\FormAbstract;

class CreditPackageForm extends FormAbstract
{
    public function setup(): void
    {
        $this
            ->model(AiVideoCreditPackage::class)
            ->setValidatorClass(CreateCreditPackageRequest::class)
            ->columns()
            ->add(
                'code',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/ai-video-generator::ai-video-generator.credit_packages.code'))
                    ->required()
                    ->colspan(1)
            )
            ->add('name', TextField::class, NameFieldOption::make()->required()->maxLength(120)->colspan(1))
            ->add(
                'price',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/ai-video-generator::ai-video-generator.credit_packages.price'))
                    ->required()
                    ->attributes(['min' => 1, 'step' => 1])
                    ->colspan(1)
            )
            ->add(
                'credits',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/ai-video-generator::ai-video-generator.credit_packages.credits'))
                    ->required()
                    ->attributes(['min' => 1, 'step' => 1])
                    ->colspan(1)
            )
            ->add(
                'features',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label('Quyền lợi gói')
                    ->helperText('Nhập mỗi quyền lợi trên một dòng.')
                    ->rows(6)
                    ->colspan(2)
            )
            ->add(
                'is_popular',
                OnOffField::class,
                OnOffFieldOption::make()
                    ->label('Gói được chọn nhiều nhất')
                    ->defaultValue(false)
                    ->colspan(1)
            );
    }
}
