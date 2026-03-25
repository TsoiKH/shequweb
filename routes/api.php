<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\CommentController;

/*
|--------------------------------------------------------------------------
| 1. 身份认证 (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/send-code', 'sendVerificationCode')->middleware('throttle:3,1');
    Route::post('/register', 'register')->middleware('throttle:10,1');
    Route::post('/login', 'login')->middleware('throttle:10,1');
    Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:3,1');
});

// 第三方登录相关路由
Route::prefix('auth/social')->controller(AuthController::class)->group(function () {
    // 引导用户去授权
    Route::get('{provider}/redirect', 'redirectToProvider');
    // 第三方授权后的回调
    Route::get('{provider}/callback', 'handleProviderCallback');
});

/*
|--------------------------------------------------------------------------
| 2. 帖子模块 (Mixed: Public & Private)
|--------------------------------------------------------------------------
*/
Route::prefix('posts')->controller(PostController::class)->group(function () {

    // --- A. 搜索相关 (必须放在 {id} 之前) ---
    Route::get('/search', 'search'); 
    Route::get('/search-discovery', 'searchDiscovery');

    // --- B. 公开列表查询 ---
    Route::get('/', 'index');                     // 发现页
    Route::get('/user/{userId}', 'userPosts');    // 某人的作品

    // --- C. 需要登录的操作 ---
    Route::middleware('auth:sanctum')->group(function () {
        // 1. 个人流信息
        Route::get('/followed', 'followedPosts');
        Route::get('/collections', 'userCollections');
        Route::get('/liked', 'userLiked');

        // 2. 搜索记录管理
        Route::delete('/search-history', 'clearSearchHistory');

        // 3. 帖子及互动动作
        Route::post('/', 'store');
        Route::post('/upload', 'upload');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/report', 'report');
        Route::post('/{id}/share-ticket', 'shareTicket');
        Route::post('/{id}/like', 'toggleLike');
        Route::post('/{id}/collect', 'toggleCollect');
        Route::post('/{id}/visibility', 'toggleVisibility');
    });
    // --- D. 详情查询 (通配符路由，必须放最后) ---
    Route::get('/{id}', 'show');
    Route::get('/{id}/comments', 'getComments');
});

/*
|--------------------------------------------------------------------------
| 3. 用户及关系模块 (Private)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    
    // 用户资料与社交
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/me', 'me');
        Route::get('/profile/{id?}', 'profile'); 
        Route::post('/{id}/follow', 'toggleFollow');
        Route::post('/avatar', 'uploadAvatar'); 
        Route::put('/update', 'updateProfile'); 
        Route::get('/{id}/followings', 'followings');
        Route::get('/{id}/followers', 'followers');
    });

    // 独立互动操作
    Route::prefix('comments')->controller(CommentController::class)->group(function () {
        Route::post('/{postId}', 'store');
        Route::delete('{id}', 'destroy');
        Route::post('{id}/like', 'toggleLike');
        Route::get('{id}/replies', 'getReplies');
    });

    // 通知系统
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/unread-count', 'unreadCount');
    });

    // 私信模块
    Route::prefix('messages')->controller(MessageController::class)->group(function () {
        Route::get('/conversations', 'conversationList'); // 会话列表
        Route::get('/history/{userId}', 'chatHistory');   // 聊天详情（增量）
        Route::post('/send', 'send');                    // 发送消息
    });

    // 标签系统
    Route::prefix('tags')->controller(TagController::class)->group(function () {
        Route::get('/hot', 'hot');
        Route::get('/search', 'search');
    });
});