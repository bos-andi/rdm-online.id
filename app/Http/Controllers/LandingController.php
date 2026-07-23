<?php

namespace App\Http\Controllers;

use App\Services\PackagePricingService;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(PackagePricingService $pricing): View
    {
        return view('landing.index', [
            'pricing' => $pricing->display(),
        ]);
    }
}
