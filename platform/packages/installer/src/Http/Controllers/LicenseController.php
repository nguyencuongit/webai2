<?php

namespace Botble\Installer\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;

class LicenseController extends BaseController
{
    public function index(): View|RedirectResponse
    {
        return redirect()->to(URL::temporarySignedRoute('installers.final', Carbon::now()->addMinutes(30)));
    }

    public function store(): RedirectResponse
    {
        return redirect()->to(URL::temporarySignedRoute('installers.final', Carbon::now()->addMinutes(30)));
    }

    public function skip(): RedirectResponse
    {
        return redirect()->to(URL::temporarySignedRoute('installers.final', Carbon::now()->addMinutes(30)));
    }
}
