<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 基础统计
        $stats = [
            'total_users' => User::count(),
            'total_posts' => Post::count(),
            'total_comments' => Comment::count(),
            'today_users' => User::whereDate('created_at', Carbon::today())->count(),
            'today_posts' => Post::whereDate('created_at', Carbon::today())->count(),
        ];

        // 过去 7 天趋势数据
        $last7Days = collect([]);
        for ($i = 6; $i >= 0; $i--) {
            $last7Days->push(Carbon::today()->subDays($i)->format('m-d'));
        }

        $userTrends = User::select(DB::raw('DATE_FORMAT(created_at, "%m-%d") as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->groupBy('date')
            ->pluck('count', 'date');

        $postTrends = Post::select(DB::raw('DATE_FORMAT(created_at, "%m-%d") as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->groupBy('date')
            ->pluck('count', 'date');

        $chartData = [
            'labels' => $last7Days,
            'users' => $last7Days->map(function($date) use ($userTrends) {
                return $userTrends->get($date, 0);
            }),
            'posts' => $last7Days->map(function($date) use ($postTrends) {
                return $postTrends->get($date, 0);
            }),
        ];

        return view('admin.dashboard', compact('stats', 'chartData'));
    }
}