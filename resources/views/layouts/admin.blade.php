<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oversea Manager - @yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 商务沉稳风格基础样式 */
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            margin: 0; 
            display: flex; 
            min-height: 100vh; 
            background-color: #f4f6f9; /* 更加灰白色的背景 */
            color: #333;
        }

        /* 侧边栏样式 - 深色商务风 */
        .sidebar { 
            width: 250px; 
            background-color: #2c3e50; /* 深蓝灰色 */
            color: #ecf0f1; 
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar h1 { 
            font-size: 18px; 
            text-align: center; 
            margin-bottom: 40px;
            letter-spacing: 1px;
            color: #fff;
            text-transform: uppercase;
        }
        .sidebar a { 
            color: #b8c7ce; 
            text-decoration: none; 
            display: block; 
            padding: 12px 20px; 
            transition: all 0.2s;
            font-size: 14px;
            border-left: 3px solid transparent;
        }
        .sidebar a:hover { 
            background-color: #1e282c; 
            color: #fff;
            border-left-color: #3498db; /* 增加左侧边框指示 */
        }
        
        /* 主体区域 */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        
        /* 顶部导航 - 更加简洁 */
        .header { 
            background: #fff; 
            padding: 0 25px; 
            height: 60px;
            border-bottom: 1px solid #e1e4e8; 
            display: flex; 
            justify-content: flex-end; 
            align-items: center;
        }
        .user-info {
            color: #7f8c8d;
            font-size: 14px;
            margin-right: 20px;
        }
        
        /* 退出按钮 - 简洁小按钮 */
        .logout-form { margin: 0; }
        .logout-btn { 
            background-color: #fff; 
            border: 1px solid #ddd; 
            color: #333; 
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer; 
            font-size: 13px;
            transition: all 0.3s;
        }
        .logout-btn:hover { 
            background-color: #f4f4f4;
            border-color: #ccc;
        }

        /* 内容区域 */
        .content { padding: 25px; flex: 1; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1>Oversea</h1>
        <a href="{{ route('admin.dashboard') }}">控制台</a>
        <a href="{{ route('admin.users.index') }}">管理员管理</a>
        <a href="{{ route('admin.app_users.index') }}">用户管理</a>
        <a href="{{ route('admin.posts.index') }}">帖子内容管理</a>
        <a href="{{ route('admin.reports.index') }}">举报管理审核</a>
        <a href="{{ route('admin.tags.index') }}">话题标签</a>
    </div>

    <div class="main-content">
        <div class="header">
            <span class="user-info">
                管理员：{{ Auth::guard('admin')->user()->name }}
            </span>
            <form action="{{ route('admin.logout') }}" method="POST" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">退出</button>
            </form>
        </div>
        
        <div class="content">
            @yield('content')
        </div>
    </div>
</body>
</html>