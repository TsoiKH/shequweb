<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TagController extends Controller
{
    /**
     * 1. 获取热门标签 (用于首页滑动栏或发帖推荐)
     * Route: GET /api/tags/hot
     */
    public function hot(Request $request)
    {
        $tags = Cache::remember('hot_tags_list', 3600, function () {
            return Tag::orderBy('use_count', 'desc')
                ->limit(15)
                ->get(['id', 'name', 'use_count']);
        });

        return response()->json([
            'code' => 200,
            'msg'  => '获取成功',
            'data' => $tags
        ]);
    }

    /**
     * 2. 标签搜索/联想 (用于发帖时输入 # 后的实时提示)
     * Route: GET /api/tags/search
     */
    public function search(Request $request)
    {
        $keyword = $request->get('keyword');
        if (empty($keyword)) {
            return response()->json(['code' => 200, 'data' => []]);
        }

        // 模糊匹配已有标签
        $tags = Tag::where('name', 'like', "%{$keyword}%")
            ->orderBy('use_count', 'desc')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json([
            'code' => 200,
            'data' => $tags
        ]);
    }
}