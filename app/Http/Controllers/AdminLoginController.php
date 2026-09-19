<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (
            Auth::attempt([
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'admin_status' => 1,
            ])
        ) {
            $request->session()->regenerate();

            return redirect('/admin/attendance/list');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->withInput();
    }
}
