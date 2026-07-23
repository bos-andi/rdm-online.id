<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubdomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubdomainController extends Controller
{
    public function check(Request $request, SubdomainService $subdomainService): JsonResponse
    {
        $request->validate([
            'subdomain' => ['required', 'string', 'max:30'],
        ]);

        return response()->json(
            $subdomainService->check($request->string('subdomain')->toString())
        );
    }
}
