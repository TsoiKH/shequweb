@extends('layouts.admin')

@section('title', '添加管理员')

@section('content')
    <div style="max-width: 650px; margin: 0 auto; background: white; padding: 25px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e1e4e8;">
        <h2 style="margin-top: 0; margin-bottom: 25px; font-size: 18px; color: #333; border-bottom: 2px solid #f8f9fc; padding-bottom: 10px;">添加管理员</h2>

        @if ($errors->any())
            <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; font-size: 14px;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 14px;">用户名:</label>
                <input type="text" name="username" value="{{ old('username') }}" 
                       style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 14px;">昵称:</label>
                <input type="text" name="name" value="{{ old('name') }}" 
                       style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 14px;">密码:</label>
                <input type="password" name="password" 
                       style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 14px;">确认密码:</label>
                <input type="password" name="password_confirmation" 
                       style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
            </div>

            <div style="border-top: 1px solid #eee; padding-top: 20px; text-align: right;">
                <a href="{{ route('admin.users.index') }}" style="margin-right: 15px; color: #7f8c8d; text-decoration: none; font-size: 14px;">取消</a>
                <button type="submit" style="padding: 8px 20px; background-color: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">保存</button>
            </div>
        </form>
    </div>
@endsection