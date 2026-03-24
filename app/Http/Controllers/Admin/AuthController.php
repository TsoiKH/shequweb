<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 显示登录页面
    public function showLoginForm()
    {
        // 我们稍后会创建这个视图
        return view('admin.login');
    }

    // 处理登录请求
    public function login(Request $request)
    {
        // 校验输入
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // 使用我们在 auth.php 中定义的 'admin' guard
        if (Auth::guard('admin')->attempt($credentials)) {
            // 登录成功，重定向到后台主页
            return redirect()->intended(route('admin.dashboard'));
        }

        // 登录失败，返回登录页并显示错误
        return back()->withInput($request->only('username'))
                     ->withErrors(['username' => '用户名或密码错误']);
    }

    // 退出登录
    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin.login');
    }
}