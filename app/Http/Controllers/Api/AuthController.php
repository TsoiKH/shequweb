<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class AuthController extends Controller
{
    /**
     * 发送验证码
     */
    public function sendVerificationCode(Request $request)
    {
        $request->validate([
            'account' => 'required|string',
            'type'    => 'required|in:register,login,reset',
            'country_code' => 'nullable|string' 
        ]);
    
        $account = $request->account;
        // 自动识别是否为邮箱
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);
    
        // 1. 频率限制 (60秒)
        $exists = VerificationCode::where('account', $account)
            ->where('created_at', '>', now()->subMinute())
            ->exists();
        if ($exists) return response()->json(['code' => 429, 'msg' => '发送太频繁']);
    
        // 2. 生成并保存
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        VerificationCode::create([
            'account'      => $account,
            'country_code' => $isEmail ? null : $request->country_code,
            'code'         => $code,
            'type'         => $request->type,
            'expired_at'   => now()->addMinutes(10),
        ]);
    
        // 3. 返回 (开发阶段直接返回 code)
        return response()->json([
            'code' => 200, 
            'msg' => '验证码已发送至' . ($isEmail ? '邮箱' : '手机'),
            'data' => ['code' => $code] 
        ]);
    }

    /**
     * 注册用户
     */
    public function register(Request $request)
    {
        $request->validate([
            'nickname'          => 'required|string|max:50',
            'account'           => 'required|string', // 填手机或邮箱
            'password'          => 'required|string|min:6',
            'verification_code' => 'required|string',
            'country_code'      => 'nullable|string', // 手机注册必填
        ]);
    
        $account = $request->account;
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);
    
        // 1. 校验验证码
        $vCode = VerificationCode::where('account', $account)
            ->where('code', $request->verification_code)
            ->where('type', 'register')
            ->where('status', 0)
            ->where('expired_at', '>', now())
            ->latest()
            ->first();
    
        if (!$vCode) return response()->json(['code' => 400, 'msg' => '验证码错误']);
    
        // 2. 检查用户是否已存在
        $exists = $isEmail 
            ? User::where('email', $account)->exists()
            : User::where('phone', $account)->where('country_code', $request->country_code)->exists();
    
        if ($exists) return response()->json(['code' => 400, 'msg' => '该账号已被注册']);
    
        // 3. 创建用户
        $user = User::create([
            'nickname'     => $request->nickname,
            'email'        => $isEmail ? $account : null,
            'phone'        => $isEmail ? null : $account,
            'country_code' => $isEmail ? null : $request->country_code,
            'password'     => Hash::make($request->password),
            'ip_address'   => $request->ip(),
        ]);
    
        $vCode->update(['status' => 1]);
    
        return response()->json([
            'code' => 200,
            'msg'  => '注册成功',
            'data' => [
                'token' => $user->createToken('auth_token')->plainTextToken,
                'user'  => $user
            ]
        ]);
    }

    /**
     * 登录接口 (支持密码登录或验证码快捷登录)
     */
    public function login(Request $request)
    {
        $request->validate([
            'account'           => 'required|string',
            'country_code'      => 'nullable|string',
            'password'          => 'required_without:verification_code',
            'verification_code' => 'required_without:password',
        ]);
    
        $account = $request->account;
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);
    
        // 1. 查找用户
        $user = $isEmail 
            ? User::where('email', $account)->first()
            : User::where('phone', $account)->where('country_code', $request->country_code)->first();
    
        if (!$user) return response()->json(['code' => 404, 'msg' => '账号不存在']);
    
        // 2. 校验凭证
        if ($request->filled('verification_code')) {
            // 验证码模式
            $vCheck = VerificationCode::where('account', $account)
                ->where('code', $request->verification_code)
                ->where('type', 'login')
                ->where('status', 0)
                ->where('expired_at', '>', now())
                ->exists();
            if (!$vCheck) return response()->json(['code' => 400, 'msg' => '验证码错误']);
        } else {
            // 密码模式
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['code' => 401, 'msg' => '密码错误']);
            }
        }
    
        return response()->json([
            'code' => 200,
            'msg'  => '登录成功',
            'data' => [
                'token' => $user->createToken('auth_token')->plainTextToken,
                'user'  => $user
            ]
        ]);
    }

    /**
     * 忘记密码/重置密码
     */
    public function forgotPassword(Request $request)
    {
        $v = Validator::make($request->all(), [
            'account'           => 'required|string', // 手机号或邮箱
            'country_code'      => 'nullable|string', // 手机号重置时必填
            'verification_code' => 'required|string',
            'new_password'      => 'required|string|min:6|confirmed', // 需配合 new_password_confirmation
        ]);

        if ($v->fails()) {
            return response()->json(['code' => 422, 'msg' => '校验失败', 'errors' => $v->errors()], 422);
        }

        $account = $request->account;
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);

        // 1. 校验验证码逻辑 (适配 account 字段)
        $vCode = VerificationCode::where('account', $account)
            ->when(!$isEmail, function($q) use ($request) {
                return $q->where('country_code', $request->country_code);
            })
            ->where('code', $request->verification_code)
            ->where('type', 'reset') 
            ->where('status', 0)
            ->where('expired_at', '>', now())
            ->latest()
            ->first();

        if (!$vCode) {
            return response()->json(['code' => 400, 'msg' => '验证码无效或已过期'], 400);
        }

        // 2. 查找用户 (适配手机/邮箱双渠道)
        $user = $isEmail 
            ? User::where('email', $account)->first()
            : User::where('phone', $account)->where('country_code', $request->country_code)->first();

        if (!$user) {
            return response()->json(['code' => 404, 'msg' => '该账号不存在'], 404);
        }

        // 3. 更新密码并消耗验证码
        $user->update(['password' => Hash::make($request->new_password)]);
        $vCode->update(['status' => 1]);

        // 4. 让旧的 Token 全部失效，确保安全性
        $user->tokens()->delete();

        return response()->json(['code' => 200, 'msg' => '密码重置成功，请重新登录']);
    }

/**
     * 重定向至第三方平台
     * GET /api/auth/social/{provider}/redirect
     */
    public function redirectToProvider($provider)
    {
        $supported = ['google', 'github', 'facebook', 'apple'];
        if (!in_array($provider, $supported)) {
            return response()->json(['code' => 400, 'msg' => '暂不支持该平台登录']);
        }

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver->stateless()->redirect()->getTargetUrl();
    }

    /**
     * 第三方登录回调处理
     * GET /api/auth/social/{provider}/callback
     */
    public function handleProviderCallback($provider)
    {
        // 1. 初始化变量，防止 Try-Catch 作用域问题
        $socialId = null;
        $email = null;
        $nickname = null;
        $avatar = null;

        // 2. Mock 逻辑或正式逻辑获取用户信息
        if (app()->environment('local') && request()->has('test')) {
            $socialId = '123456789';
            $email = 'test_oauth@example.com';
            $nickname = 'OAuth测试员';
            $avatar = 'https://via.placeholder.com/150';
        } else {
            try {
                /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
                $driver = Socialite::driver($provider);
                $socialUser = $driver->stateless()->user();
                
                $socialId = $socialUser->getId();
                $email    = $socialUser->getEmail();
                $nickname = $socialUser->getName() ?? $socialUser->getNickname();
                $avatar   = $socialUser->getAvatar();
            } catch (\Exception $e) {
                return response()->json(['code' => 401, 'msg' => '第三方授权失败: ' . $e->getMessage()]);
            }
        }

        // 3. 执行数据库绑定逻辑 (使用 DB 事务保证原子性)
        return DB::transaction(function () use ($provider, $socialId, $email, $nickname, $avatar) {
            // 查找社交账号
            $account = SocialAccount::where('provider', $provider)
                ->where('provider_id', $socialId)
                ->first();

            if ($account) {
                $user = $account->user;
            } else {
                // 处理邮箱逻辑：如果第三方没给邮箱，生成固定格式占位符
                $targetEmail = $email ?: ($provider . '_' . $socialId . '@no-email.com');
                
                // 查找或创建用户
                $user = User::where('email', $targetEmail)->first();

                if (!$user) {
                    $user = User::create([
                        'nickname'   => $nickname,
                        'email'      => $targetEmail,
                        'password'   => Hash::make(Str::random(24)),
                        'ip_address' => request()->ip(),
                    ]);
                }

                // 建立社交关联
                $user->socials()->create([
                    'provider'    => $provider,
                    'provider_id' => $socialId,
                    'avatar'      => $avatar,
                ]);
            }

            return response()->json([
                'code' => 200,
                'msg'  => '第三方登录成功',
                'data' => [
                    'token' => $user->createToken('auth_token')->plainTextToken,
                    'user'  => $user
                ]
            ]);
        });
    }
}