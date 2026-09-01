<?php

namespace Botble\AiVideoGenerator\Forms;

use Botble\AiVideoGenerator\Http\Requests\CreateAiVideoApiTokenRequest;
use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;

class AiVideoApiTokenForm extends FormAbstract
{
    public function setup(): void
    {
        $this
            ->model(AiVideoApiToken::class)
            ->setValidatorClass(CreateAiVideoApiTokenRequest::class)
            ->columns()
            ->add(
                'token_api',
                TextField::class,
                TextFieldOption::make()
                    ->label('API token')
                    ->required()
                    ->maxLength(255)
                    ->colspan(2)
            )
            ->add(
                'webhook_secret',
                TextField::class,
                TextFieldOption::make()
                    ->label('Webhook secret')
                    ->maxLength(255)
                    ->colspan(2)
            )
            ->add(
                'status',
                OnOffField::class,
                OnOffFieldOption::make()
                    ->label('Kích hoạt')
                    ->defaultValue(1)
                    ->colspan(2)
            );
    }
}
