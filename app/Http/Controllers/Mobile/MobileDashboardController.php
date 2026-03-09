<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user?->role;

        return view('mobile.dashboard', [
            'user' => $user,
            'rol' => $role,
        ]);
    }
}

