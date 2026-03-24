<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['user', 'post.user']);

        // 筛选待处理
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->latest()->paginate(15);
        return view('admin.reports.index', compact('reports'));
    }

    // 处理举报逻辑
    public function handle(Request $request, $id)
    {
        $report = Report::findOrFail($id);
        $action = $request->input('action'); // 'process' or 'reject'

        DB::transaction(function () use ($report, $action) {
            if ($action === 'process') {
                // 1. 更新举报状态为已处理
                $report->update(['status' => 1]);
                // 2. 自动屏蔽关联帖子
                Post::where('id', $report->post_id)->update(['status' => 0]);
            } else {
                // 驳回
                $report->update(['status' => 2]);
            }

            // 3. (可选) 给举报者发个通知，利用你之前的 notifications 表
            DB::table('notifications')->insert([
                'user_id' => $report->user_id,
                'sender_id' => 0,
                'type' => 'system',
                'content' => $action === 'process' ? '感谢举报！您举报的帖子已被下架。' : '关于您的举报：经核实该内容暂未违规。',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });

        return back()->with('success', '处理成功');
    }
}