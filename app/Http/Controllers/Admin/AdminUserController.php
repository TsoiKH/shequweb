<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    // 用户列表
    public function index()
    {
        // 获取所有管理员，每页显示10条
        $users = AdminUser::paginate(10);
        
        return view('admin.users.index', compact('users'));
    }
    // 显示创建页面
    public function create()
    {
        return view('admin.users.create');
    }

    // 保存创建的数据
    public function store(Request $request)
    {
        // 1. 数据校验
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:admin_users',
            'password' => 'required|string|min:6|confirmed',
            'name'     => 'required|string|max:255',
        ]);

        // 2. 创建用户并加密密码
        AdminUser::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'name'     => $validated['name'],
        ]);

        // 3. 重定向回列表页
        return redirect()->route('admin.users.index')->with('success', '管理员创建成功！');
    }

    public function edit($id)
    {
        $user = AdminUser::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    // 更新用户数据
    public function update(Request $request, $id)
    {
        $user = AdminUser::findOrFail($id);

        // 1. 数据校验（用户名需排除当前用户ID）
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:admin_users,username,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed', // 密码可为空（不修改）
            'name'     => 'required|string|max:255',
        ]);

        // 2. 更新基础数据
        $user->username = $validated['username'];
        $user->name = $validated['name'];

        // 3. 如果输入了密码，则加密更新
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', '管理员更新成功！');
    }

    // 删除用户
    public function destroy($id)
    {
        $user = AdminUser::findOrFail($id);
        
        // 防止删除自己（可选的逻辑）
        if ($user->id === auth('admin')->id()) {
            return back()->withErrors(['error' => '不能删除自己！']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', '管理员删除成功！');
    }
}