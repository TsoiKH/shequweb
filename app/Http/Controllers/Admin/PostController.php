<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with(['user', 'reports'])->withCount(['reports as pending_reports_count' => function ($query) {
            $query->where('status', 0);
        }]); 
    
        // 搜索：支持标题、内容、发布者昵称
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('nickname', 'like', "%{$search}%");
                  });
            });
        }
    
        // 筛选：媒体类型
        if ($request->filled('media_type')) {
            $query->where('media_type', $request->media_type);
        }
    
        // 筛选：状态
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    
        $posts = $query->orderBy('pending_reports_count', 'desc')->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
    
        return view('admin.posts.index', compact('posts'));
    }

    // 切换状态 (屏蔽/显示)
    public function toggleStatus($id)
    {
        $post = Post::findOrFail($id);
        $post->status = $post->status == 1 ? 0 : 1;
        $post->save();

        return back()->with('success', '帖子状态更新成功');
    }


    public function show($id)
    {
        $post = Post::with([
            'user', 
            'tags', 
            'comments.user', 
            'comments.parent.user'
        ])->findOrFail($id);
    
        return view('admin.posts.show', compact('post'));
    }
}