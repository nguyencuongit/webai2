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
            'name' => __('Zalo contact button'),
            'description' => __('Display a floating Zalo contact button.'),
            'zalo_link' => '',
            'zalo_label' => 'Zalo',
        ]);
    }

    protected function settingForm(): WidgetForm|string|null
    {
        return WidgetForm::createFromArray($this->getConfig())
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
                TextFieldOption::make()->label(__('Zalo Button Label'))
            );
    }
}
