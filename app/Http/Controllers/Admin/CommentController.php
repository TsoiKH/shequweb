<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::with(['user', 'post']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('content', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('nickname', 'like', "%{$search}%");
                  });
        }

        $comments = $query->latest()->paginate(20)->appends($request->all());

        return view('admin.comments.index', compact('comments'));
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        
        if ($comment->parent_id == 0) {
            Comment::where('parent_id', $comment->id)->delete();
        }
        
        $comment->delete();
    
        return back()->with('success', '评论及其关联回复已删除');
    }
}