<?php

namespace Botble\Payment\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Payment\Services\PaymentTransactionReportService;
use Illuminate\Http\Request;

class PaymentTransactionReportController extends BaseController
{
    public function index(Request $request, PaymentTransactionReportService $reportService)
    {
        $this->pageTitle('Báo cáo giao dịch');

        $date = $request->string('date')->toString();
        $month = $request->string('month')->toString();

        return view('plugins/payment::reports.transactions', [
            'report' => $reportService->getReport($date, $month),
            'date' => $date,
            'month' => $month,
        ]);
    }

    public function show(int $paymentId, PaymentTransactionReportService $reportService)
    {
        $transfer = $reportService->getPendingTransferByPaymentId($paymentId);

        abort_unless($transfer, 404);

        $this->pageTitle('Xác nhận giao dịch ' . $transfer['charge_id']);

        return view('plugins/payment::reports.pending-transfer-detail', compact('transfer'));
    }

    public function confirm(int $paymentId, PaymentTransactionReportService $reportService)
    {
        $transfer = $reportService->confirmPendingTransfer($paymentId);

        if (! $transfer) {
            return redirect()
                ->route('payments.reports.transactions')
                ->with('error_msg', 'Không thể xác nhận: giao dịch không còn ở trạng thái chờ hoặc không còn khớp với SePay.');
        }

        return redirect()
            ->route('payments.reports.transactions')
            ->with('success_msg', 'Đã xác nhận giao dịch ' . $transfer['charge_id'] . ' thành công.');
    }
}

