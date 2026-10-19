<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\TripayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripayWebhookController extends Controller
{
    public function handle(Request $request, TripayService $tripay, PaymentService $payments): JsonResponse
    {
        $json = $request->getContent();

        if (! $tripay->verifyCallbackSignature($json, $request->header('X-Callback-Signature'))) {
            return response()->json(['success' => false, 'message' => 'Invalid signature']);
        }

        if ($request->header('X-Callback-Event') !== 'payment_status') {
            return response()->json([
                'success' => false,
                'message' => 'Unrecognized callback event: '.$request->header('X-Callback-Event'),
            ]);
        }

        $data = json_decode($json, true);

        if (! is_array($data)) {
            return response()->json(['success' => false, 'message' => 'Invalid data sent by tripay']);
        }

        return response()->json($payments->handleTripayCallback($data));
    }
}
