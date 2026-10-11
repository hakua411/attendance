<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    /**
     * 管理者のログイン処理を行う。
     *
     * @param  AdminLoginRequest  $request  ログインフォームの入力データ
     * @return RedirectResponse ログイン成功時または失敗時のリダイレクト
     */
    public function login(AdminLoginRequest $request): RedirectResponse
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

    /**
     * 管理者のログアウト処理を行う。
     *
     * @param  Request  $request  現在のリクエスト
     * @return RedirectResponse ログイン画面へのリダイレクト
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
