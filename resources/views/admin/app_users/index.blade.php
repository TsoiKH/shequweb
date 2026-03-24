@extends('layouts.admin')

@section('title', 'App 用户管理')

@section('content')
<div style="background: white; padding: 25px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e1e4e8;">
    
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; font-size: 18px; color: #333;">App 用户列表</h2>
        
        <form action="{{ route('admin.app_users.index') }}" method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="搜索昵称/手机/邮箱..." 
                   style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 250px; font-size: 14px;">
            <button type="submit" style="padding: 8px 20px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">查询</button>
            @if(request()->has('search'))
                <a href="{{ route('admin.app_users.index') }}" style="padding: 8px; color: #7f8c8d; text-decoration: none; font-size: 14px;">重置</a>
            @endif
        </form>
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <thead>
            <tr style="background-color: #f8f9fc; border-bottom: 2px solid #e1e4e8; color: #7f8c8d;">
                <th style="padding: 12px; text-align: left;">ID</th>
                <th style="padding: 12px; text-align: left;">头像</th>
                <th style="padding: 12px; text-align: left;">昵称 / 联系方式</th>
                <th style="padding: 12px; text-align: left;">地区</th>
                <th style="padding: 12px; text-align: center;">发帖数</th>
                <th style="padding: 12px; text-align: left;">最后登录</th>
                <th style="padding: 12px; text-align: center;">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px; color: #999;">{{ $user->id }}</td>
                <td style="padding: 12px;">
                    <img src="{{ $user->avatar ?? '/default-avatar.png' }}" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; background: #eee;">
                </td>
                <td style="padding: 12px;">
                    <div style="font-weight: bold; color: #2c3e50;">{{ $user->nickname }}</div>
                    <div style="font-size: 12px; color: #95a5a6;">
                        {{ $user->country_code ? '+'.$user->country_code : '' }} {{ $user->phone ?? $user->email }}
                    </div>
                </td>
                <td style="padding: 12px; color: #555;">{{ $user->city ?? '未知' }}</td>
                <td style="padding: 12px; text-align: center;">
                    <span style="background: #f4f6f9; padding: 2px 10px; border-radius: 10px; font-weight: bold; color: #2c3e50;">{{ $user->posts_count }}</span>
                </td>
                <td style="padding: 12px; color: #7f8c8d; font-size: 12px;">
                    {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : '从未' }}
                </td>
                <td style="padding: 12px; text-align: center;">
                    <a href="{{ route('admin.app_users.show', $user->id) }}" style="padding: 5px 12px; border: 1px solid #3498db; color: #3498db; text-decoration: none; border-radius: 4px; font-size: 12px;">详情</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="padding: 40px; text-align: center; color: #999;">未找到匹配用户</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 25px;">
        {{ $users->links() }}
    </div>
</div>
@endsection