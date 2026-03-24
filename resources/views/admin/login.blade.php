<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>OVERSEA - 管理员登录</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-box { width: 360px; background: #fff; padding: 40px; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-top: 4px solid #2c3e50; }
        .login-box h1 { text-align: center; color: #2c3e50; font-size: 22px; margin-bottom: 30px; letter-spacing: 2px; }
        .form-item { margin-bottom: 20px; }
        .form-item label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: bold; color: #666; }
        .form-item input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-login { width: 100%; padding: 12px; background: #2c3e50; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 15px; }
        .btn-login:hover { background: #1e282c; }
        .errors { color: #e74c3c; font-size: 13px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>OVERSEA</h1>
        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('admin.login') }}" method="POST">
            @csrf
            <div class="form-item">
                <label>用户名</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-item">
                <label>密码</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">登录</button>
        </form>
    </div>
</body>
</html>