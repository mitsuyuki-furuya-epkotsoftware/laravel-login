<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class AuthController extends Controller
{

    public function __construct(User $user){
        $this->user = $user;
    }

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
        $user = $this->user->getUserByEmail($credentials['email']);

        // 取得したユーザーが存在した場合
        if (!is_null($user)) {
            // アカウントロックされている場合
            if ($this->user->isAccountLocked($user)) {
                // アカウントロックのメッセージ
                return back()->withErrors([
                    'danger' => 'アカウントがロックされています。'
                ]);
            }
            // アカウントロックされていない場合 (ログイン処理)
            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();
                // ログイン成功時、エラーカウントが0以外なら初期化
                $this->user->resetErrorCount($user);
                // ログイン成功時のメッセージ
                return redirect()->route('home')->with('success', 'ログイン成功しました！');
            }

            // ログイン失敗の場合、エラーカウントをインクリメント
            $user->error_count = $this->user->addErrorCount($user->error_count);
            // エラーカウントが6以上の場合、アカウントをロック
            if ($this->user->lockAccount($user)) {
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
