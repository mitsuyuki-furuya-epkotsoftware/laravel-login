<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class AuthController extends Controller
{
    /**
     * @return View
     */
    public function showLogin()
    {
        return view('login.login_form');
    }

    /**
     * @param App\Http\Requests\LoginFormRequest
     * $request
     */
    public function login(LoginFormRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // アカウントがロックされていたら弾く
        $user = User::where('email', '=',  $credentials['email'])->first();

        // 取得したユーザーが存在した場合
        if (!is_null($user)) {
            // アカウントロックされている場合
            if ($user->locked_flg === 1) {
                // アカウントロックのメッセージ
                return back()->withErrors([
                    'danger' => 'アカウントがロックされています。'
                ]);
            }
            // アカウントロックされていない場合 (ログイン処理)
            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();
                // ログイン成功時、エラーカウントが0以外なら初期化
                if ($user->error_count > 0) {
                    $user->error_count = 0;
                    // エラーカウントを保存
                    $user->save();
                }
                // ログイン成功時のメッセージ
                return redirect()->route('home')->with('success', 'ログイン成功しました！');
            }

            // ログイン失敗の場合、エラーカウントをインクリメント
            $user->error_count = $user->error_count + 1;
            // エラーカウントが5以上の場合、アカウントをロック
            if ($user->error_count > 5) {
                $user->locked_flg = 1;
                // エラーカウントを保存
                $user->save();
                // アカウントロックした場合
                return back()->withErrors([
                    'danger' => 'アカウントがロックされました！解除したい場合は運営者に連絡してください。'
                ]);
            }
            // エラーカウントを保存
            $user->save();
        }

        // ログインエラーの場合
        return back()->withErrors([
            'danger' => 'メールアドレスがパスワードが間違っています。'
        ]);
    }

    /**
     * ユーザーをアプリケーションからログアウトさせる
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('showLogin')->with('danger', 'ログアウトしました！');
    }
}
