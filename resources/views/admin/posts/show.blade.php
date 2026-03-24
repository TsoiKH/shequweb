@extends('layouts.admin')

@section('content')
<div style="display: flex; flex-direction: column; gap: 20px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('admin.posts.index') }}" style="text-decoration: none; color: #7f8c8d;">← 返回列表</a>
        <form action="{{ route('admin.posts.toggle', $post->id) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" style="background: {{ $post->status == 1 ? '#e74c3c' : '#27ae60' }}; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                {{ $post->status == 1 ? '屏蔽此贴' : '解除屏蔽' }}
            </button>
        </form>
    </div>

    <div style="background: white; border-radius: 8px; border: 1px solid #e1e4e8; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid #f4f6f9; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center;">
                <img src="{{ $post->user->avatar ?? '' }}" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 12px;">
                <div>
                    <div style="font-weight: bold;">{{ $post->user->nickname }}</div>
                    <div style="font-size: 12px; color: #999;">发布于 {{ $post->created_at }} · {{ $post->city }}</div>
                </div>
            </div>
            <div style="text-align: right;">
                @foreach($post->tags as $tag)
                    <span style="background: #ebf5ff; color: #007bff; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 5px;">#{{ $tag->name }}</span>
                @endforeach
            </div>
        </div>

        <div style="padding: 25px;">
            <h1 style="font-size: 20px; margin: 0 0 15px;">{{ $post->title ?: '无标题' }}</h1>
            <div style="font-size: 16px; line-height: 1.8; color: #2c3e50; white-space: pre-wrap;">{{ $post->content }}</div>
            
            <div style="margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                @php $media = is_array($post->media_urls) ? $post->media_urls : json_decode($post->media_urls, true); @endphp
                @if($post->media_type === 'video')
                    <video src="{{ $media[0] ?? '' }}" controls style="max-width: 100%; border-radius: 8px;"></video>
                @else
                    @foreach($media ?? [] as $url)
                        <img src="{{ $url }}" style="width: 100%; border-radius: 8px; border: 1px solid #eee;">
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div style="background: white; border-radius: 8px; border: 1px solid #e1e4e8;">
        <div style="padding: 15px 20px; border-bottom: 1px solid #f4f6f9; font-weight: bold; display: flex; justify-content: space-between;">
            <span>全部评论 ({{ $post->comments->count() }})</span>
        </div>
        
        <div style="padding: 10px 20px;">
            @forelse($post->comments as $comment)
            <div style="padding: 15px 0; border-bottom: 1px solid #f8f9fa; display: flex; gap: 15px;">
                <img src="{{ $comment->user->avatar ?? '' }}" style="width: 32px; height: 32px; border-radius: 50%;">
                <div style="flex: 1;">
                    <div style="font-size: 13px; margin-bottom: 5px;">
                        <span style="font-weight: bold;">{{ $comment->user->nickname }}</span>
                        @if($comment->parent_id)
                            <span style="color: #999; margin: 0 5px;">回复</span>
                            <span style="font-weight: bold;">{{ $comment->parent->user->nickname ?? '用户' }}</span>
                        @endif
                        <span style="color: #ccc; margin-left: 10px; font-size: 12px;">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <div style="font-size: 14px; color: #444; background: #f9f9f9; padding: 10px; border-radius: 4px; margin-top: 5px;">
                        {{ $comment->content }}
                    </div>
                    <div style="margin-top: 8px; text-align: right;">
                        <form action="{{ route('admin.comments.destroy', $comment->id) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" style="color: #e74c3c; background: none; border: none; font-size: 12px; cursor: pointer;" onclick="return confirm('确定删除此评论？')">删除该违规评论</button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div style="padding: 40px; text-align: center; color: #999;">暂无评论</div>
            @endforelse
        </div>
    </div>
</div>
@endsection