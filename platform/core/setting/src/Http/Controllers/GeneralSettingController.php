<?php

namespace Botble\Setting\Http\Controllers;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Setting\Forms\GeneralSettingForm;
use Botble\Setting\Http\Requests\GeneralSettingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class GeneralSettingController extends SettingController
{
    public function edit(): View
    {
        $this->pageTitle(trans('core/setting::setting.general_setting'));

        $form = GeneralSettingForm::create();

        return view('core/setting::general', compact('form'));
    }

    public function update(GeneralSettingRequest $request): BaseHttpResponse
    {
        $data = Arr::except($request->input(), ['locale']);

        return $this->performUpdate($data);
    }

    public function getVerifyLicense(Request $request): BaseHttpResponse
    {
        return $this->httpResponse()->setMessage('License verification bypassed.')->setData([
            'activated_at' => date('M d Y'),
            'licensed_to' => 'Bypassed User',
        ]);
    }

    public function activateLicense(): BaseHttpResponse
    {
        return $this->httpResponse()->setMessage('License activation bypassed.');
    }

    public function deactivateLicense(): BaseHttpResponse
    {
        return $this->httpResponse()->setMessage('License deactivation bypassed.');
    }
}
