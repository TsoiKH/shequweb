@extends('layouts.admin')

@section('title', '帖子内容管理')

@section('content')

    <style>
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
            100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
        }
        .custom-pagination .pagination {
            gap: 5px;
        }
        .custom-pagination .page-link {
            border-radius: 4px !important;
            color: #2c3e50;
            border: 1px solid #dee2e6;
            padding: 8px 16px;
        }
        .custom-pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
            color: white;
        }
    </style>

<div style="background: white; padding: 25px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e1e4e8;">
    
    <div style="margin-bottom: 25px;">
        <h2 style="margin: 0 0 15px; font-size: 18px; color: #333;">内容审核中心</h2>
        <form action="{{ route('admin.posts.index') }}" method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="搜索关键词/发布者..." 
                   style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 200px;">
            
            <select name="media_type" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">所有类型</option>
                <option value="image" {{ request('media_type') == 'image' ? 'selected' : '' }}>图文</option>
                <option value="video" {{ request('media_type') == 'video' ? 'selected' : '' }}>视频</option>
                <option value="text" {{ request('media_type') == 'text' ? 'selected' : '' }}>纯文字</option>
            </select>

            <button type="submit" style="padding: 8px 20px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">筛选</button>
            <a href="{{ route('admin.posts.index') }}" style="padding: 8px; color: #7f8c8d; text-decoration: none; font-size: 14px;">重置</a>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        @foreach($posts as $post)
        <div style="border: 1px solid #e1e4e8; border-radius: 8px; overflow: hidden; background: #fff; display: flex; flex-direction: column; position: relative;">
            
            {{-- 新增：举报预警角标 --}}
            @php $pendingReports = $post->reports->where('status', 0); @endphp
            @if($pendingReports->count() > 0)
                <div style="position: absolute; top: 10px; right: 10px; z-index: 5; background: #e74c3c; color: white; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: bold; animation: pulse-red 2s infinite; display: flex; align-items: center; gap: 4px;">
                    <span>⚠️</span> {{ $pendingReports->count() }} 举报
                </div>
            @endif

            {{-- 媒体预览区 --}}
            <div style="height: 180px; background: #f8f9fc; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center;">
                @php $media = is_array($post->media_urls) ? $post->media_urls : json_decode($post->media_urls, true); @endphp

                @if($post->media_type === 'video' && !empty($media))
                    <div style="width: 100%; height: 100%; background: #000; color: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <span style="font-size: 24px;">▶</span>
                        <span style="font-size: 12px; margin-top: 5px;">视频内容</span>
                    </div>
                @elseif($post->media_type === 'image' && !empty($media) && isset($media[0]))
                    <img src="{{ $media[0] }}" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    <div style="padding: 20px; color: #555; font-size: 13px; line-height: 1.6; height: 100%; width: 100%; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center; text-align: center;">
                        <span style="position: absolute; top: 10px; left: 15px; color: #3498db; font-size: 24px; font-family: serif; font-weight: bold; opacity: 0.4;">“</span>
                        <div style="max-width: 85%; padding: 0 10px; font-style: italic;">
                            {{ Str::limit($post->content, 140) }}
                        </div>
                        <span style="position: absolute; bottom: 10px; right: 15px; color: #3498db; font-size: 24px; font-family: serif; font-weight: bold; opacity: 0.4;">”</span>
                        <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 30px; background: linear-gradient(transparent, #f8f9fc);"></div>
                    </div>
                @endif
            </div>

            <div style="padding: 15px; flex-grow: 1;">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <img src="{{ $post->user->avatar ?? '' }}" style="width: 24px; height: 24px; border-radius: 50%; margin-right: 8px; background: #eee;">
                    <span style="font-size: 13px; font-weight: bold; color: #555;">{{ $post->user->nickname }}</span>
                </div>
                
                <div style="font-size: 14px; color: #333; font-weight: bold; margin-bottom: 8px; height: 20px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                    {{ $post->title ?: '未设置标题' }}
                </div>
                
                {{-- 新增：举报详情摘要 --}}
                @if($pendingReports->count() > 0)
                    <div style="font-size: 11px; color: #c53030; background: #fff5f5; padding: 4px 8px; border-radius: 4px; margin-bottom: 8px; border: 1px solid #feb2b2;">
                        原因：{{ $pendingReports->pluck('reason')->unique()->first() }}...
                    </div>
                @endif

                <div style="font-size: 12px; color: #95a5a6; display: flex; justify-content: space-between;">
                    <span>❤️ {{ $post->like_count }} · 💬 {{ $post->comment_count }}</span>
                    <span>{{ $post->created_at->format('m-d H:i') }}</span>
                </div>
            </div>

            <div style="padding: 10px 15px; border-top: 1px solid #f4f6f9; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                <span style="font-size: 12px; color: {{ $post->status == 1 ? '#27ae60' : '#e74c3c' }}; font-weight: bold;">
                    ● {{ $post->status == 1 ? '公开' : '隐藏' }}
                </span>
                <div style="display: flex; gap: 8px;">
                    <form action="{{ route('admin.posts.toggle', $post->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" style="font-size: 12px; background: none; border: 1px solid #ddd; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                            {{ $post->status == 1 ? '屏蔽' : '恢复' }}
                        </button>
                    </form>
                    <a href="{{ route('admin.posts.show', $post->id) }}" style="font-size: 12px; text-decoration: none; border: 1px solid #3498db; color: #3498db; padding: 4px 8px; border-radius: 4px;">详情</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div style="margin-top: 30px; display: flex; justify-content: center; align-items: center;">
        <div class="custom-pagination">
            {!! $posts->links() !!}
        </div>
    </div>
</div>
@endsection