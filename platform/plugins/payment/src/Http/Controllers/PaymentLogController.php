<?php

namespace Botble\Payment\Http\Controllers;

use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\Payment\Models\PaymentLog;
use Botble\Payment\Services\PaymentLogBackfillService;
use Botble\Payment\Tables\PaymentLogTable;

class PaymentLogController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/payment::payment.payment_log.name'), route('payments.logs.index'));
    }

    public function index(PaymentLogTable $paymentLogTable)
    {
        $this->pageTitle(trans('plugins/payment::payment.payment_log.name'));

        return $paymentLogTable->renderTable();
    }

    public function show(PaymentLog $paymentLog)
    {
        $this->pageTitle(trans('plugins/payment::payment.payment_log.view', ['id' => $paymentLog->getKey()]));

        return view('plugins/payment::logs.show', compact('paymentLog'));
    }

    public function destroy(PaymentLog $paymentLog)
    {
        return DeleteResourceAction::make($paymentLog);
    }

    public function backfill(PaymentLogBackfillService $backfillService)
    {
        $counts = $backfillService->backfillCompletedSePayPayments(request()->ip());

        return redirect()
            ->route('payments.logs.index')
            ->with('success_msg', sprintf(
                'Đã cập nhật nhật ký SePay: tạo %d bản ghi mới, liên kết %d bản ghi cũ.',
                $counts['created'],
                $counts['linked']
            ));
    }
}

