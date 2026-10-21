<?php

namespace App\Http\Controllers;

use App\Services\AndidevPayService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AndidevWebhookController extends Controller
{
    public function handle(Request $request, AndidevPayService $andidev, PaymentService $payments): Response
    {
        $raw = $request->getContent();

        if (! $andidev->verifyWebhook($raw, $request->header('X-Andidev-Signature'))) {
            abort(401);
        }

        $event = json_decode($raw, true);

        if (! is_array($event)) {
            abort(400);
        }

        $payments->handleAndidevWebhook($event);

        return response()->noContent();
    }
}
