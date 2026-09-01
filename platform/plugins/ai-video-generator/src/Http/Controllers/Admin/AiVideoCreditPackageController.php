<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Forms\CreditPackageForm;
use Botble\AiVideoGenerator\Http\Requests\CreateCreditPackageRequest;
use Botble\AiVideoGenerator\Http\Requests\UpdateCreditPackageRequest;
use Botble\AiVideoGenerator\Models\AiVideoCreditPackage;
use Botble\AiVideoGenerator\Tables\CreditPackageTable;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;

class AiVideoCreditPackageController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()->add(
            trans('plugins/ai-video-generator::ai-video-generator.credit_packages.name'),
            route('ai-video-generator.credit-packages.index')
        );
    }

    public function index(CreditPackageTable $table)
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.credit_packages.name'));

        return $table->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.credit_packages.create'));

        return CreditPackageForm::create()
            ->setUrl(route('ai-video-generator.credit-packages.store'))
            ->renderForm();
    }

    public function store(CreateCreditPackageRequest $request)
    {
        $package = AiVideoCreditPackage::query()->create($request->validated());

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('ai-video-generator.credit-packages.index'))
            ->setNextUrl(route('ai-video-generator.credit-packages.edit', $package))
            ->withCreatedSuccessMessage();
    }

    public function edit(AiVideoCreditPackage $creditPackage)
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.credit_packages.edit', ['name' => $creditPackage->name]));

        return CreditPackageForm::createFromModel($creditPackage)
            ->setValidatorClass(UpdateCreditPackageRequest::class)
            ->setUrl(route('ai-video-generator.credit-packages.update', $creditPackage))
            ->setMethod('PUT')
            ->renderForm();
    }

    public function update(AiVideoCreditPackage $creditPackage, UpdateCreditPackageRequest $request)
    {
        $creditPackage->fill($request->validated());
        $creditPackage->save();

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('ai-video-generator.credit-packages.index'))
            ->withUpdatedSuccessMessage();
    }

    public function destroy(AiVideoCreditPackage $creditPackage)
    {
        return DeleteResourceAction::make($creditPackage);
    }
}
