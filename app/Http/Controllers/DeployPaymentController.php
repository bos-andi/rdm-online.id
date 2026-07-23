<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DeployPaymentController extends Controller
{
    public function success(): View
    {
        return view('deploy.payment-success', [
            'ref' => request()->query('ref'),
        ]);
    }
}
