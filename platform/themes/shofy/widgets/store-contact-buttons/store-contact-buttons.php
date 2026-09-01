<?php

use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\TextField;
use Botble\Widget\AbstractWidget;
use Botble\Widget\Forms\WidgetForm;

class StoreContactButtonsWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => __('Store Contact Buttons'),
            'description' => __('Display quick contact buttons (Hotline, Zalo, Facebook)'),
            'hotline' => '1900633045',
            'hotline_label' => 'Đặt nhanh',
            'zalo_link' => 'https://zalo.me/0123456789',
            'zalo_label' => 'Zalo OA',
            'facebook_link' => 'https://facebook.com',
            'facebook_label' => 'Facebook',
        ]);
    }

    protected function settingForm(): WidgetForm|string|null
    {
        return WidgetForm::createFromArray($this->getConfig())
            ->add(
                'hotline',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Hotline'))
                    ->helperText(__('Phone number for quick call'))
            )
            ->add(
                'hotline_label',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Hotline Button Label'))
                    ->defaultValue('Đặt nhanh')
            )
            ->add(
                'zalo_link',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Zalo Link'))
                    ->helperText(__('E.g: https://zalo.me/0123456789'))
            )
            ->add(
                'zalo_label',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Zalo Button Label'))
                    ->defaultValue('Zalo OA')
            )
            ->add(
                'facebook_link',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Facebook Link'))
                    ->helperText(__('E.g: https://facebook.com/yourpage'))
            )
            ->add(
                'facebook_label',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Facebook Button Label'))
                    ->defaultValue('Facebook')
            );
    }
}