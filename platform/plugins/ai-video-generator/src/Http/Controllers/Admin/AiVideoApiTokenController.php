<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Forms\AiVideoApiTokenForm;
use Botble\AiVideoGenerator\Http\Requests\CreateAiVideoApiTokenRequest;
use Botble\AiVideoGenerator\Http\Requests\UpdateAiVideoApiTokenRequest;
use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Tables\AiVideoApiTokenTable;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;

class AiVideoApiTokenController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()->add('API token', route('ai-video-generator.api-tokens.index'));
    }

    public function index(AiVideoApiTokenTable $table)
    {
        $this->pageTitle('API token');

        return $table->renderTable();
    }

    public function create()
    {
        $this->pageTitle('Thêm API token');

        return AiVideoApiTokenForm::create()
            ->setUrl(route('ai-video-generator.api-tokens.store'))
            ->renderForm();
    }

    public function store(CreateAiVideoApiTokenRequest $request)
    {
        $apiToken = AiVideoApiToken::query()->create($request->validated());

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('ai-video-generator.api-tokens.index'))
            ->setNextUrl(route('ai-video-generator.api-tokens.edit', $apiToken))
            ->withCreatedSuccessMessage();
    }

    public function edit(AiVideoApiToken $apiToken)
    {
        $this->pageTitle('Sửa API token');

        return AiVideoApiTokenForm::createFromModel($apiToken)
            ->setValidatorClass(UpdateAiVideoApiTokenRequest::class)
            ->setUrl(route('ai-video-generator.api-tokens.update', $apiToken))
            ->setMethod('PUT')
            ->renderForm();
    }

    public function update(AiVideoApiToken $apiToken, UpdateAiVideoApiTokenRequest $request)
    {
        $apiToken->fill($request->validated());
        $apiToken->save();

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('ai-video-generator.api-tokens.index'))
            ->withUpdatedSuccessMessage();
    }

    public function destroy(AiVideoApiToken $apiToken)
    {
        return DeleteResourceAction::make($apiToken);
    }
}
