@extends('layouts.admin')

@section('content')
<div style="background: white; padding: 25px; border-radius: 4px; border: 1px solid #e1e4e8; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <div style="margin-bottom: 25px;">
        <a href="{{ route('admin.tags.index') }}" style="color: #3498db; text-decoration: none; font-size: 14px;">← 返回标签列表</a>
        <h2 style="margin: 15px 0; font-size: 20px;">包含标签 <span style="color: #3498db;">#{{ $tag->name }}</span> 的帖子</h2>
        <p style="color: #7f8c8d; font-size: 13px;">共找到 {{ $posts->total() }} 篇相关内容</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        @forelse($posts as $post)
        <div style="border: 1px solid #e1e4e8; border-radius: 8px; overflow: hidden; background: #fff; display: flex; flex-direction: column;">
            
            {{-- 媒体预览区 (完全同步居中双引号逻辑) --}}
            <div style="height: 160px; background: #f8f9fc; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center;">
                @php $media = is_array($post->media_urls) ? $post->media_urls : json_decode($post->media_urls, true); @endphp

                @if($post->media_type === 'video' && !empty($media))
                    <div style="width: 100%; height: 100%; background: #000; color: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <span style="font-size: 20px;">▶</span>
                        <span style="font-size: 11px; margin-top: 5px;">视频</span>
                    </div>
                @elseif($post->media_type === 'image' && !empty($media) && isset($media[0]))
                    <img src="{{ $media[0] }}" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    <div style="padding: 15px; color: #555; font-size: 12px; line-height: 1.5; height: 100%; width: 100%; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center; text-align: center;">
                        {{-- 左上引号 --}}
                        <span style="position: absolute; top: 8px; left: 12px; color: #3498db; font-size: 20px; font-family: serif; font-weight: bold; opacity: 0.4;">“</span>
                        
                        <div style="max-width: 85%; padding: 0 5px; font-style: italic;">
                            {{ Str::limit($post->content, 120) }}
                        </div>

                        {{-- 右下引号 --}}
                        <span style="position: absolute; bottom: 8px; right: 12px; color: #3498db; font-size: 20px; font-family: serif; font-weight: bold; opacity: 0.4;">”</span>
                        
                        <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 25px; background: linear-gradient(transparent, #f8f9fc);"></div>
                    </div>
                @endif
            </div>

            <div style="padding: 12px; flex-grow: 1;">
                <div style="font-size: 13px; font-weight: bold; margin-bottom: 8px; height: 18px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                    {{ $post->title ?: '未设置标题' }}
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center;">
                        <img src="{{ $post->user->avatar ?? '' }}" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 5px; background: #eee;">
                        <span style="font-size: 11px; color: #666;">{{ $post->user->nickname }}</span>
                    </div>
                    <a href="{{ route('admin.posts.show', $post->id) }}" style="font-size: 11px; color: #3498db; text-decoration: none; border: 1px solid #3498db; padding: 2px 6px; border-radius: 3px;">详情 →</a>
                </div>
            </div>
        </div>
        @empty
        <div style="grid-column: 1 / -1; padding: 50px; text-align: center; color: #999; background: #fefefe; border: 1px dashed #ddd;">
            该标签下暂无关联帖子
        </div>
        @endforelse
    </div>

    <div style="margin-top: 30px;">
        {{ $posts->links() }}
    </div>
</div>
@endsection