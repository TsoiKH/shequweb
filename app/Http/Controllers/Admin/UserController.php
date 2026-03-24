<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $ip = $request->ip();
        $testIp = (app()->environment('local') && $ip === '127.0.0.1') ? '62.157.140.133' : $ip;
        $query = User::withCount('posts');
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nickname', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.app_users.index', compact('users'));
    }

    public function show($id)
    {
        $user = User::withCount(['posts'])->findOrFail($id);
        $latestPosts = $user->posts()->latest()->take(5)->get();
        return view('admin.app_users.show', compact('user', 'latestPosts'));
    }
}