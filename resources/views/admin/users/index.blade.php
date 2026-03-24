@extends('layouts.admin')

@section('title', '管理员列表')

@section('content')
    <div style="background: white; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e1e4e8;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
            <h2 style="margin: 0; font-size: 18px; color: #333;">管理员列表</h2>
            <a href="{{ route('admin.users.create') }}" style="padding: 8px 16px; background-color: #2c3e50; color: white; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold; transition: background 0.3s;">
                + 添加管理员
            </a>
        </div>

        @if(session('success'))
            <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; font-size: 14px;">
                {{ session('success') }}
            </div>
        @endif

        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background-color: #f8f9fc;">
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e1e4e8; color: #555;">ID</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e1e4e8; color: #555;">用户名</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e1e4e8; color: #555;">昵称</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e1e4e8; color: #555;">创建时间</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e1e4e8; color: #555;">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">{{ $user->id }}</td>
                        <td style="padding: 12px; font-weight: bold; color: #2c3e50;">{{ $user->username }}</td>
                        <td style="padding: 12px;">{{ $user->name }}</td>
                        <td style="padding: 12px; color: #7f8c8d;">{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                        <td style="padding: 12px; text-align: center;">
                            <div style="display: flex; gap: 5px; justify-content: center;">
                                <a href="{{ route('admin.users.edit', $user->id) }}" style="padding: 4px 8px; background-color: #fff; color: #3498db; border: 1px solid #3498db; text-decoration: none; border-radius: 3px; font-size: 12px;">编辑</a>
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('确定删除吗？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="padding: 4px 8px; background-color: #fff; color: #e74c3c; border: 1px solid #e74c3c; border-radius: 3px; cursor: pointer; font-size: 12px;">删除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $users->links() }}
        </div>
    </div>
@endsection