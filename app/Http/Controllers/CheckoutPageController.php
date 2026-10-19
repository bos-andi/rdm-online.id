<?php

namespace App\Http\Controllers;

use App\Services\PackagePricingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutPageController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentService $payments,
        PackagePricingService $pricing,
    ): View {
        $package = in_array($request->query('package'), PackagePricingService::PACKAGES, true)
            ? $request->query('package')
            : 'yearly';

        return view('checkout.index', [
            'selectedPackage' => $package,
            'paymentMethods' => $payments->groupedPaymentMethods(),
            'adminFees' => $payments->adminFeesMap(),
            'pricing' => $pricing->display(),
        ]);
    }
}
