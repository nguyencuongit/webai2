<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Forms\AiContentPostForm;
use Botble\AiVideoGenerator\Http\Requests\AiContentPostRequest;
use Botble\AiVideoGenerator\Models\AiContentPost;
use Botble\AiVideoGenerator\Tables\AiContentPostTable;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;

class AiContentPostController extends BaseController
{
    public function index(AiContentPostTable $table) { $this->pageTitle('Nội dung hiển thị'); return $table->renderTable(); }
    public function create() { $this->pageTitle('Tạo nội dung'); return AiContentPostForm::create()->setUrl(route('ai-video-generator.content-posts.store'))->renderForm(); }
    public function store(AiContentPostRequest $request) { $post = AiContentPost::query()->create($request->validated()); return $this->httpResponse()->setPreviousUrl(route('ai-video-generator.content-posts.index'))->setNextUrl(route('ai-video-generator.content-posts.edit', $post))->withCreatedSuccessMessage(); }
    public function edit(AiContentPost $aiContentPost) { $this->pageTitle('Sửa nội dung: ' . $aiContentPost->title); return AiContentPostForm::createFromModel($aiContentPost)->setUrl(route('ai-video-generator.content-posts.update', $aiContentPost))->setMethod('PUT')->renderForm(); }
    public function update(AiContentPost $aiContentPost, AiContentPostRequest $request) { $aiContentPost->fill($request->validated())->save(); return $this->httpResponse()->setPreviousUrl(route('ai-video-generator.content-posts.index'))->withUpdatedSuccessMessage(); }
    public function destroy(AiContentPost $aiContentPost) { return DeleteResourceAction::make($aiContentPost); }
}
