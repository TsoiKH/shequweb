<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SensitiveWord;
use Illuminate\Http\Request;

class SensitiveWordController extends Controller
{
    public function index(Request $request)
    {
        $query = SensitiveWord::query();

        if ($request->filled('search')) {
            $query->where('word', 'like', "%{$request->search}%");
        }

        $words = $query->latest()->paginate(20);

        return view('admin.sensitive_words.index', compact('words'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'word' => 'required|string|max:100|unique:sensitive_words,word',
            'type' => 'required|in:block,replace',
        ]);

        SensitiveWord::create([
            'word' => $request->word,
            'type' => $request->type
        ]);

        return back()->with('success', '敏感词已添加');
    }

    public function destroy($id)
    {
        $word = SensitiveWord::findOrFail($id);
        $word->delete();

        return back()->with('success', '敏感词已删除');
    }
}
