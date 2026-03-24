<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\UserController as AppUserController; 
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\ReportController;


Route::prefix('admin')->name('admin.')->group(function () {
    
    // 1. 登录与认证
    Route::controller(AuthController::class)->group(function () {
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login');
        Route::post('/logout', 'logout')->name('logout');
    });

    // 2. 需要登录的后台操作
    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // 后台管理员管理
        Route::resource('users', AdminUserController::class)->except(['show']);

        // App 用户管理
        Route::prefix('app-users')->name('app_users.')->controller(AppUserController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{id}', 'show')->name('show');
            Route::patch('/{id}/toggle', 'toggleStatus')->name('toggle'); 
        });

        // 帖子管理
        Route::prefix('posts')->name('posts.')->controller(PostController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{id}', 'show')->name('show');
            Route::patch('/{id}/toggle', 'toggleStatus')->name('toggle'); 
        });

        Route::prefix('comments')->name('comments.')->controller(CommentController::class)->group(function () {
            Route::delete('/{id}', 'destroy')->name('destroy');
        });

        Route::prefix('tags')->name('tags.')->controller(TagController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{id}/posts', 'posts')->name('posts'); 
            Route::put('/{id}', 'update')->name('update');  
            Route::delete('/{id}', 'destroy')->name('destroy');
        });

        Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::patch('/{id}/handle', 'handle')->name('handle'); 
        });

    });
});