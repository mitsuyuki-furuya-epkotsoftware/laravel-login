<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    /**
     * @return View
     */
    public function showLogin(){
        return view('login.login_form');
    }

    /**
     * @param App\Http\Requests\LoginFormRequest
     * $request
     */
    public function login(LoginFormRequest $request){
        $credentials = $request->only('email', 'password');

        // ログイン成功の場合
        if(Auth::attempt($credentials)){
            $request->session()->regenerate();

            return redirect('home')->with('login_success', 'ログイン成功しました！');
        }

        // ログインエラーの場合
        return back()->withErrors([
            'login_error' => 'メールアドレスがパスワードが間違っています。'
        ]);
    }
}
