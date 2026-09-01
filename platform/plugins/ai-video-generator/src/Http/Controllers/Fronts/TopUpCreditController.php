<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Fronts;

use Botble\AiVideoGenerator\Models\AiVideoCreditPackage;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Theme\Facades\Theme;

class TopUpCreditController extends BaseController
{
    public function index()
    {
        $creditPackages = AiVideoCreditPackage::query()
            ->orderBy('price')
            ->get();
        $customer = auth('customer')->user();

        return Theme::scope('top-up-credit', compact('creditPackages', 'customer'))->render();
    }
}
