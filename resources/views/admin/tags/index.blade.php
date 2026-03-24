@extends('layouts.admin')

@section('content')
<div style="background: white; padding: 25px; border-radius: 4px; border: 1px solid #e1e4e8;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; font-size: 18px;">话题标签管理</h2>
        <form action="{{ route('admin.tags.index') }}" method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="搜索标签名称..." style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" style="padding: 8px 15px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">查询</button>
        </form>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f9fc; border-bottom: 2px solid #eee;">
                <th style="padding: 12px; text-align: left;">标签名称</th>
                <th style="padding: 12px; text-align: center;">使用次数</th>
                <th style="padding: 12px; text-align: center;">操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tags as $tag)
            <tr style="border-bottom: 1px solid #f4f6f9;">
                <td style="padding: 12px;">
                    <span style="color: #3498db; font-weight: bold; margin-right: 10px;"># {{ $tag->name }}</span>
                    <button onclick="editName({{ $tag->id }}, '{{ $tag->name }}')" style="background: none; border: none; color: #999; cursor: pointer; font-size: 12px;">[修改名称]</button>
                </td>
                <td style="padding: 12px; text-align: center;">{{ $tag->use_count }}</td>
                <td style="padding: 12px; text-align: center;">
                    <a href="{{ route('admin.tags.posts', $tag->id) }}" style="color: #3498db; text-decoration: none; margin-right: 15px; font-size: 13px;">查看帖子</a>
                    <form action="{{ route('admin.tags.destroy', $tag->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('确定删除该标签？')">
                        @csrf @method('DELETE')
                        <button type="submit" style="color: #e74c3c; background: none; border: none; cursor: pointer; font-size: 13px;">删除</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top: 20px;">{{ $tags->links() }}</div>
</div>

<script>
function editName(id, currentName) {
    let newName = prompt("修改标签名称：", currentName);
    if (newName && newName !== currentName) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/tags/${id}`;
        form.innerHTML = `@csrf @method('PUT') <input type="hidden" name="name" value="${newName}">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection