@extends('layouts.admin')

@section('title', '用户详情 - ' . $user->nickname)

@section('content')
<div style="display: flex; gap: 20px;">
    <div style="flex: 1; background: white; padding: 25px; border-radius: 4px; border: 1px solid #e1e4e8;">
        <div style="text-align: center; border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px;">
            <img src="{{ $user->avatar ?? '/default-avatar.png' }}" style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid #f4f6f9;">
            <h2 style="margin: 15px 0 5px;">{{ $user->nickname }}</h2>
            <p style="color: #7f8c8d; font-size: 14px;">{{ $user->bio ?? '这人很懒，什么都没写' }}</p>
        </div>
        
        <div style="font-size: 14px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span style="color: #7f8c8d;">手机号码:</span>
                <span>{{ $user->country_code ? '+'.$user->country_code : '' }} {{ $user->phone ?? '-' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span style="color: #7f8c8d;">电子邮箱:</span>
                <span>{{ $user->email ?? '-' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span style="color: #7f8c8d;">所在城市:</span>
                <span>{{ $user->city ?? '未设置' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span style="color: #7f8c8d;">注册 IP:</span>
                <span>{{ $user->ip_address ?? '-' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #7f8c8d;">注册时间:</span>
                <span>{{ $user->created_at }}</span>
            </div>
        </div>

        <a href="{{ route('admin.app_users.index') }}" style="display: block; text-align: center; margin-top: 30px; color: #7f8c8d; text-decoration: none; font-size: 13px;">返回列表</a>
    </div>

    <div style="flex: 2;">
        <div style="background: white; padding: 20px; border-radius: 4px; border: 1px solid #e1e4e8; margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px; font-size: 16px;">活动统计</h3>
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1; background: #f8f9fc; padding: 15px; border-radius: 4px; text-align: center;">
                    <div style="font-size: 20px; font-weight: bold; color: #2c3e50;">{{ $user->posts_count }}</div>
                    <div style="font-size: 12px; color: #7f8c8d;">发布作品</div>
                </div>
                </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 4px; border: 1px solid #e1e4e8;">
            <h3 style="margin: 0 0 15px; font-size: 16px;">最近发布的帖子</h3>
            @forelse($latestPosts as $post)
                <div style="display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #f4f6f9;">
                    <div style="width: 55px; height: 55px; background: #eee; border-radius: 4px; overflow: hidden; margin-right: 15px; flex-shrink: 0;">
                        @php 
                            $media = is_array($post->media_urls) ? $post->media_urls : json_decode($post->media_urls, true); 
                        @endphp
                        
                        @if(!empty($media) && isset($media[0]))
                            @if($post->media_type === 'video')
                                <div style="position: relative; width: 100%; height: 100%; background: #000; color: white; font-size: 10px; display: flex; align-items: center; justify-content: center;">VIDEO</div>
                            @else
                                <img src="{{ $media[0] }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @endif
                        @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 10px;">无图</div>
                        @endif
                    </div>
                    
                    <div style="flex: 1;">
                        <div style="font-size: 14px; font-weight: bold; color: #333; margin-bottom: 4px;">
                            {{ $post->title ?: Str::limit($post->content, 30) }}
                        </div>
                        <div style="font-size: 12px; color: #999;">
                            {{ $post->created_at->format('m-d H:i') }} · 
                            <span style="color: #3498db;">{{ $post->like_count }} 点赞</span> · 
                            <span>{{ $post->media_type === 'video' ? '视频' : '图文' }}</span>
                        </div>
                    </div>
                    <a href="{{ route('admin.posts.show', $post->id) }}" style="font-size: 12px; color: #3498db; text-decoration: none; padding: 4px 8px; border: 1px solid #3498db; border-radius: 3px;">管理</a>
                </div>
            @empty
                <p style="color: #999; font-size: 14px; padding: 20px 0; text-align: center;">该用户暂无发布内容</p>
            @endforelse
        </div>
    </div>
</div>
@endsection