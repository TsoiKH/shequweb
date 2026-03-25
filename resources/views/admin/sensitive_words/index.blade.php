@extends('layouts.admin')

@section('title', '敏感词管理')

@section('content')
<div style="background: white; padding: 25px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e1e4e8;">
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; font-size: 18px; color: #333;">敏感词库管理</h2>
        
        <div style="display: flex; gap: 10px;">
            <form action="{{ route('admin.sensitive_words.store') }}" method="POST" style="display: flex; gap: 5px;">
                @csrf
                <input type="text" name="word" placeholder="添加新敏感词..." required
                       style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 180px;">
                <select name="type" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="block">拦截 (Block)</option>
                    <option value="replace">替换 (Replace)</option>
                </select>
                <button type="submit" style="padding: 8px 15px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">添加</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div style="padding: 10px 15px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="margin-bottom: 20px;">
        <form action="{{ route('admin.sensitive_words.index') }}" method="GET">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="搜索敏感词..." 
                   style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
            <button type="submit" style="padding: 8px 15px; background: #f8f9fc; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">搜索</button>
        </form>
    </div>

    <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <thead>
            <tr style="background: #f8f9fc; text-align: left; border-bottom: 2px solid #eee;">
                <th style="padding: 12px;">ID</th>
                <th style="padding: 12px;">敏感词</th>
                <th style="padding: 12px;">处理方式</th>
                <th style="padding: 12px;">创建时间</th>
                <th style="padding: 12px; text-align: right;">操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach($words as $word)
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px; color: #7f8c8d;">#{{ $word->id }}</td>
                <td style="padding: 12px; font-weight: bold; color: #c53030;">{{ $word->word }}</td>
                <td style="padding: 12px;">
                    @if($word->type === 'block')
                        <span style="background: #fff5f5; color: #c53030; padding: 2px 8px; border-radius: 10px; font-size: 12px; border: 1px solid #feb2b2;">
                            拦截 (Block)
                        </span>
                    @else
                        <span style="background: #e6fffa; color: #2c7a7b; padding: 2px 8px; border-radius: 10px; font-size: 12px; border: 1px solid #b2f5ea;">
                            替换 (Replace)
                        </span>
                    @endif
                </td>
                <td style="padding: 12px; color: #7f8c8d; font-size: 13px;">{{ $word->created_at->format('Y-m-d H:i') }}</td>
                <td style="padding: 12px; text-align: right;">
                    <form action="{{ route('admin.sensitive_words.destroy', $word->id) }}" method="POST" onsubmit="return confirm('确定删除该敏感词吗？')">
                        @csrf @method('DELETE')
                        <button type="submit" style="color: #e74c3c; background: none; border: none; cursor: pointer; font-size: 13px;">删除</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $words->links() }}
    </div>
</div>
@endsection
