<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DeployPaymentController extends Controller
{
    public function success(): RedirectResponse
    {
        $ref = request()->query('ref');

        return redirect()->route('deploy', $ref ? ['paid' => $ref] : []);
    }
}
