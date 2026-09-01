<?php

use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\TextField;
use Botble\Widget\AbstractWidget;
use Botble\Widget\Forms\WidgetForm;

class StoreInfoWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => __('Store Info Box'),
            'description' => __('Display store address and phone with map link'),
            'title' => 'Mua tại cửa hàng:',
            'address' => '142 Nguyễn Văn Cừ, Phường Cầu Ông Lãnh, TP.HCM',
            'phone' => '1900 633 045',
            'google_map_link' => 'https://maps.google.com',
        ]);
    }

    protected function settingForm(): WidgetForm|string|null
    {
        return WidgetForm::createFromArray($this->getConfig())
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
                    ->defaultValue('Mua tại cửa hàng:')
            )
            ->add(
                'address',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Store Address'))
            )
            ->add(
                'phone',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Phone Number'))
            )
            ->add(
                'google_map_link',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Google Map Link'))
                    ->helperText(__('Link to your store on Google Maps'))
            );
    }
}
