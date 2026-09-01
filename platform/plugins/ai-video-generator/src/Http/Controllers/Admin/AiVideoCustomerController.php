<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Forms\CustomerForm;
use Botble\AiVideoGenerator\Http\Requests\CreateCustomerRequest;
use Botble\AiVideoGenerator\Http\Requests\UpdateCustomerRequest;
use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Tables\CustomerTable;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Illuminate\Support\Facades\Hash;

class AiVideoCustomerController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/ai-video-generator::ai-video-generator.customers.name'), route('ai-video-generator.customers.index'));
    }

    public function index(CustomerTable $table)
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.customers.name'));

        return $table->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.customers.create'));

        return CustomerForm::create()
            ->setUrl(route('ai-video-generator.customers.store'))
            ->remove('is_change_password')
            ->renderForm();
    }

    public function store(CreateCustomerRequest $request)
    {
        $customer = new Customer();
        $customer->fill($request->validated());
        $customer->password = Hash::make($request->input('password'));
        $customer->save();

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('ai-video-generator.customers.index'))
            ->setNextUrl(route('ai-video-generator.customers.edit', $customer))
            ->withCreatedSuccessMessage();
    }

    public function edit(Customer $customer)
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.customers.edit', ['name' => $customer->name]));

        $customer->password = null;

        return CustomerForm::createFromModel($customer)
            ->setValidatorClass(UpdateCustomerRequest::class)
            ->setUrl(route('ai-video-generator.customers.update', $customer))
            ->setMethod('PUT')
            ->renderForm();
    }

    public function update(Customer $customer, UpdateCustomerRequest $request)
    {
        $customer->fill($request->safe()->except(['password', 'password_confirmation', 'is_change_password']));

        if ($request->boolean('is_change_password')) {
            $customer->password = Hash::make($request->input('password'));
        }

        $customer->save();

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('ai-video-generator.customers.index'))
            ->withUpdatedSuccessMessage();
    }

    public function destroy(Customer $customer)
    {
        return DeleteResourceAction::make($customer);
    }
}
