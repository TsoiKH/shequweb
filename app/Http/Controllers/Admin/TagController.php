<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    // 标签列表
    public function index(Request $request)
    {
        $query = Tag::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // 按热度排序
        $tags = $query->orderBy('use_count', 'desc')
                      ->paginate(20)
                      ->appends($request->all());

        return view('admin.tags.index', compact('tags'));
    }

    // 更新标签名
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|max:50|unique:tags,name,' . $id
        ]);

        $tag = Tag::findOrFail($id);
        $tag->name = $request->name;
        $tag->save();

        return back()->with('success', '标签名称已更新');
    }

    // 查看该标签下的所有帖子
    public function posts($id)
    {
        $tag = Tag::findOrFail($id);
        
        // 关联查询帖子及发布者
        $posts = $tag->posts()->with('user')->latest()->paginate(12);

        return view('admin.tags.posts', compact('tag', 'posts'));
    }

    // 删除标签
    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        // 先解除中间表的关联
        $tag->posts()->detach();
        $tag->delete();

        return back()->with('success', '标签已成功删除');
    }
}