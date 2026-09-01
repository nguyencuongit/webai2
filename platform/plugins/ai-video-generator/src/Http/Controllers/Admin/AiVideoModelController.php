<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Api\MagnificApiCatalog;
use Botble\AiVideoGenerator\Forms\AiVideoModelForm;
use Botble\AiVideoGenerator\Http\Requests\AiVideoModelRequest;
use Botble\AiVideoGenerator\Models\AiVideoModel;
use Botble\AiVideoGenerator\Tables\AiVideoModelTable;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;

class AiVideoModelController extends BaseController
{
    public function index(AiVideoModelTable $table) { $this->pageTitle('Model AI'); return $table->renderTable(); }
    public function create() { $this->pageTitle('Tạo model AI'); return AiVideoModelForm::create()->setUrl(route('ai-video-generator.models.store'))->renderForm(); }
    public function store(AiVideoModelRequest $request) { $model = AiVideoModel::query()->create($request->validated()); MagnificApiCatalog::clearCache(); return $this->httpResponse()->setPreviousUrl(route('ai-video-generator.models.index'))->setNextUrl(route('ai-video-generator.models.edit', $model))->withCreatedSuccessMessage(); }
    public function edit(AiVideoModel $aiVideoModel) { $this->pageTitle('Sửa model AI: ' . $aiVideoModel->name); return AiVideoModelForm::createFromModel($aiVideoModel)->setUrl(route('ai-video-generator.models.update', $aiVideoModel))->setMethod('PUT')->renderForm(); }
    public function update(AiVideoModel $aiVideoModel, AiVideoModelRequest $request) { $aiVideoModel->fill($request->validated())->save(); MagnificApiCatalog::clearCache(); return $this->httpResponse()->setPreviousUrl(route('ai-video-generator.models.index'))->withUpdatedSuccessMessage(); }
    public function destroy(AiVideoModel $aiVideoModel) { MagnificApiCatalog::clearCache(); return DeleteResourceAction::make($aiVideoModel); }
}
