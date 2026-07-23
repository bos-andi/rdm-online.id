<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class XenditWebhookController extends Controller
{
    public function invoice(Request $request, XenditService $xendit, PaymentService $payments): Response
    {
        if (! $xendit->verifyCallbackToken($request->header('x-callback-token'))) {
            return response('Unauthorized', 401);
        }

        $payments->handleInvoiceWebhook($request->all());

        return response('OK', 200);
    }

    public function qrCode(Request $request, XenditService $xendit, PaymentService $payments): Response
    {
        if (! $xendit->verifyCallbackToken($request->header('x-callback-token'))) {
            return response('Unauthorized', 401);
        }

        $payments->handleQrCodeWebhook($request->all());

        return response('OK', 200);
    }

    public function paymentRequest(Request $request, XenditService $xendit, PaymentService $payments): Response
    {
        if (! $xendit->verifyCallbackToken($request->header('x-callback-token'))) {
            return response('Unauthorized', 401);
        }

        $payments->handlePaymentRequestWebhook($request->all());

        return response('OK', 200);
    }
}
