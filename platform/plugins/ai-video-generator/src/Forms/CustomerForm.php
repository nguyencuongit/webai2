<?php

namespace Botble\AiVideoGenerator\Forms;

use Botble\AiVideoGenerator\Http\Requests\CreateCustomerRequest;
use Botble\AiVideoGenerator\Models\Customer;
use Botble\Base\Forms\FieldOptions\EmailFieldOption;
use Botble\Base\Forms\FieldOptions\NameFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\StatusFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;

class CustomerForm extends FormAbstract
{
    public function setup(): void
    {
        $customer = $this->getModel();
        // $parents = Customer::query()
        //     ->when($customer->exists, fn ($query) => $query->whereKeyNot($customer->getKey()))
        //     ->orderBy('name')
        //     ->get(['id', 'name', 'email'])
        //     ->mapWithKeys(fn (Customer $item) => [$item->id => trim($item->name . ' - ' . $item->email)])
        //     ->all();

        $this
            ->model(Customer::class)
            ->setValidatorClass(CreateCustomerRequest::class)
            ->columns()
            ->add('name', TextField::class, NameFieldOption::make()->required()->maxLength(120)->colspan(1))
            ->add('email', TextField::class, EmailFieldOption::make()->required()->maxLength(120)->colspan(1))
            ->add(
                'phone',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/ai-video-generator::ai-video-generator.customers.phone'))
                    ->maxLength(30)
                    ->colspan(1)
            )
            ->add(
                'credits_balance',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('plugins/ai-video-generator::ai-video-generator.customers.credits_balance'))
                    ->required()
                    ->attributes(['min' => 0, 'step' => 1])
                    ->defaultValue(0)
                    ->colspan(1)
            )
            // Tạm ẩn trường tài khoản cha trên form, vẫn giữ cột parent_id để dùng sau.
            // ->add(
            //     'parent_id',
            //     SelectField::class,
            //     \Botble\Base\Forms\FieldOptions\SelectFieldOption::make()
            //         ->label(trans('plugins/ai-video-generator::ai-video-generator.customers.parent'))
            //         ->choices(['' => trans('plugins/ai-video-generator::ai-video-generator.customers.no_parent')] + $parents)
            //         ->colspan(1)
            // )
            ->add(
                'is_change_password',
                OnOffField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/ai-video-generator::ai-video-generator.customers.change_password'))
                    ->attributes([
                        'data-bb-toggle' => 'collapse',
                        'data-bb-target' => '#password-collapse',
                    ])
                    ->defaultValue(0)
                    ->colspan(1)
            )
            ->add(
                'password',
                'password',
                TextFieldOption::make()
                    ->label(trans('plugins/ai-video-generator::ai-video-generator.customers.password'))
                    ->required()
                    ->maxLength(60)
                    ->collapsible('is_change_password', 1, ! $customer->exists || $customer->is_change_password)
                    ->colspan(1)
            )
            ->add(
                'password_confirmation',
                'password',
                TextFieldOption::make()
                    ->label(trans('plugins/ai-video-generator::ai-video-generator.customers.password_confirmation'))
                    ->required()
                    ->maxLength(60)
                    ->collapsible('is_change_password', 1, ! $customer->exists || $customer->is_change_password)
                    ->colspan(1)
            )
            ->add(
                'private_notes',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(trans('plugins/ai-video-generator::ai-video-generator.customers.private_notes'))
                    ->rows(3)
                    ->colspan(2)
            )
            ->add(
                'status',
                SelectField::class,
                StatusFieldOption::make()
                    ->choices([
                        'activated' => trans('plugins/ai-video-generator::ai-video-generator.customers.statuses.activated'),
                        'locked' => trans('plugins/ai-video-generator::ai-video-generator.customers.statuses.locked'),
                    ])
                    ->defaultValue('activated')
            )
            ->setBreakFieldPoint('status');
    }
}
