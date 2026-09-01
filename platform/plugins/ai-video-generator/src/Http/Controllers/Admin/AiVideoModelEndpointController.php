<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Api\MagnificApiCatalog;
use Botble\AiVideoGenerator\Forms\AiVideoModelEndpointForm;
use Botble\AiVideoGenerator\Http\Requests\AiVideoModelEndpointRequest;
use Botble\AiVideoGenerator\Models\AiVideoModelEndpoint;
use Botble\AiVideoGenerator\Tables\AiVideoModelEndpointTable;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;

class AiVideoModelEndpointController extends BaseController
{
    public function index(AiVideoModelEndpointTable $table) { $this->pageTitle('Endpoint AI'); return $table->renderTable(); }
    public function create() { $this->pageTitle('Tạo endpoint AI'); return AiVideoModelEndpointForm::create()->setUrl(route('ai-video-generator.model-endpoints.store'))->renderForm(); }
    public function store(AiVideoModelEndpointRequest $request) { $endpoint = AiVideoModelEndpoint::query()->create($request->validated()); MagnificApiCatalog::clearCache(); return $this->httpResponse()->setPreviousUrl(route('ai-video-generator.model-endpoints.index'))->setNextUrl(route('ai-video-generator.model-endpoints.edit', $endpoint))->withCreatedSuccessMessage(); }
    public function edit(AiVideoModelEndpoint $aiVideoModelEndpoint) { $this->pageTitle('Sửa endpoint AI: ' . $aiVideoModelEndpoint->name); return AiVideoModelEndpointForm::createFromModel($aiVideoModelEndpoint)->setUrl(route('ai-video-generator.model-endpoints.update', $aiVideoModelEndpoint))->setMethod('PUT')->renderForm(); }
    public function update(AiVideoModelEndpoint $aiVideoModelEndpoint, AiVideoModelEndpointRequest $request) { $aiVideoModelEndpoint->fill($request->validated())->save(); MagnificApiCatalog::clearCache(); return $this->httpResponse()->setPreviousUrl(route('ai-video-generator.model-endpoints.index'))->withUpdatedSuccessMessage(); }
    public function destroy(AiVideoModelEndpoint $aiVideoModelEndpoint) { MagnificApiCatalog::clearCache(); return DeleteResourceAction::make($aiVideoModelEndpoint); }
}
