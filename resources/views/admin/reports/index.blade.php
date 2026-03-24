@extends('layouts.admin')

@section('content')
<div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e1e4e8; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0; font-size: 20px; color: #2d3748;">举报审核中心</h2>
        <div style="display: flex; gap: 15px;">
            <a href="{{ route('admin.reports.index') }}" style="text-decoration: none; color: {{ !request('status') ? '#3498db' : '#7f8c8d' }}; font-weight: bold; font-size: 14px;">全部</a>
            <a href="{{ route('admin.reports.index', ['status' => 0]) }}" style="text-decoration: none; color: {{ request('status') === '0' ? '#3498db' : '#7f8c8d' }}; font-weight: bold; font-size: 14px;">待审核</a>
        </div>
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <thead>
            <tr style="background: #f7fafc; border-bottom: 2px solid #edf2f7; color: #718096; text-align: left;">
                <th style="padding: 15px;">举报人</th>
                <th style="padding: 15px;">举报类型 & 详情</th>
                <th style="padding: 15px;">被举报内容预览</th>
                <th style="padding: 15px; text-align: center;">状态</th>
                <th style="padding: 15px; text-align: right;">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $report)
            <tr style="border-bottom: 1px solid #f7fafc; transition: background 0.2s;" onmouseover="this.style.background='#fcfcfc'" onmouseout="this.style.background='transparent'">
                <td style="padding: 15px;">
                    <div style="font-weight: bold;">{{ $report->user->nickname }}</div>
                    <div style="font-size: 11px; color: #a0aec0;">ID: {{ $report->user_id }}</div>
                </td>
                <td style="padding: 15px;">
                    <span style="background: #fff5f5; color: #e53e3e; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                        {{ $report->reason }}
                    </span>
                    @if($report->content)
                        <div style="font-size: 12px; color: #718096; margin-top: 6px; line-height: 1.4;">{{ $report->content }}</div>
                    @endif
                    <div style="font-size: 11px; color: #cbd5e0; margin-top: 5px;">{{ $report->created_at->format('Y-m-d H:i') }}</div>
                </td>
                <td style="padding: 15px;">
                    <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; border: 1px solid #eee;">
                        <a href="{{ route('admin.posts.show', $report->post_id) }}" style="color: #4a5568; text-decoration: none; font-size: 12px; display: block; max-width: 250px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                            📄 {{ $report->post->title ?: Str::limit($report->post->content, 30) }}
                        </a>
                    </div>
                </td>
                <td style="padding: 15px; text-align: center;">
                    @if($report->status == 0)
                        <span style="color: #ed8936; font-size: 12px;">🕒 待审核</span>
                    @elseif($report->status == 1)
                        <span style="color: #48bb78; font-size: 12px;">✅ 已下架</span>
                    @else
                        <span style="color: #a0aec0; font-size: 12px;">🚫 已驳回</span>
                    @endif
                </td>
                <td style="padding: 15px; text-align: right;">
                    @if($report->status == 0)
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <form action="{{ route('admin.reports.handle', $report->id) }}" method="POST">
                            @csrf @method('DELETE') {{-- 注意：这里对应你路由里的 DELETE --}}
                            <input type="hidden" name="action" value="process">
                            <button type="submit" style="background: #e53e3e; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px;">下架</button>
                        </form>
                        <form action="{{ route('admin.reports.handle', $report->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" style="background: #edf2f7; color: #4a5568; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px;">驳回</button>
                        </form>
                    </div>
                    @else
                        <a href="{{ route('admin.posts.show', $report->post_id) }}" style="font-size: 11px; color: #3498db; text-decoration: none;">查看记录</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="padding: 50px; text-align: center; color: #a0aec0;">清净了，目前没有任何举报记录。</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 25px;">
        {{ $reports->links() }}
    </div>
</div>
@endsection