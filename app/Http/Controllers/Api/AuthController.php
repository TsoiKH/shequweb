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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\PhoneNumber;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Carbon\Carbon;


class AuthController extends Controller
{
    /**
     * 发送验证码
     */
    public function sendVerificationCode(Request $request)
    {
        // 1. 验证输入
        $validator = Validator::make($request->all(), [
            'account' => 'required|string',
            'type'    => 'required|in:register,login,reset',
            // 如果是邮箱，country_code 可选；如果是手机，则为必填
            'country_code' => ['nullable', 'string', Rule::requiredIf(function () use ($request) {
                return !filter_var($request->account, FILTER_VALIDATE_EMAIL);
            })],
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'msg' => $validator->errors()->first()], 422);
        }

        $account = $request->account;
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);
        
        // 使用账户做频率限制键名
        $key = 'send_code_' . $account;

        // 2. 频率限制 (60秒内只能发送一次)
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'code' => 429, 
                'msg' => "发送太频繁，请 {$seconds} 秒后再试"
            ], 429);
        }

        // 3. 生成 6 位随机验证码
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // 4. 保存到数据库
        try {
            VerificationCode::create([
                'account'      => $account,
                'country_code' => $isEmail ? null : $request->country_code,
                'code'         => $code,
                'type'         => $request->type,
                'expired_at'   => now()->addMinutes(10), // 10分钟有效期
            ]);
        } catch (\Exception $e) {
            Log::error('保存验证码失败: ' . $e->getMessage());
            return response()->json(['code' => 500, 'msg' => '内部服务器错误']);
        }

        // 5. 发送逻辑
        try {
            if ($isEmail) {
                // 发送邮件
                Mail::raw("您的验证码是：{$code}，10分钟内有效。", function ($message) use ($account) {
                    $message->to($account)->subject('账户安全验证');
                });
                
                if (count(Mail::failures()) > 0) {
                    throw new \Exception('邮件服务不可用');
                }
            } else {
                // --- 直接使用 EasySms 发送短信 ---
                $config = config('sms'); // 加载 config/sms.php
                $easySms = new EasySms($config);

                // 使用 PhoneNumber 类处理号码
                $phoneNumber = new PhoneNumber($account, $request->country_code ?? '86');
                
                // 从 .env 读取模板 ID (建议)
                $templateId = env('ALIYUN_SMS_TEMPLATE_CODE');
                
                if (empty($templateId)) {
                    throw new \Exception('短信模板 ID 未配置 (ALIYUN_SMS_TEMPLATE_CODE)');
                }
                
                $easySms->send($phoneNumber, [
                    'template' => $templateId, // 你的模板ID
                    'data'     => [
                        'code' => $code // 你的模板变量名
                    ],
                ]);
                
                Log::info("EasySms 发送成功: {$account}, 模板: {$templateId}");
            }
        } catch (NoGatewayAvailableException $exception) {
            // 获取网关错误信息
            $message = $exception->getException('aliyun')->getMessage();
            Log::error('短信发送失败: ' . $message);
            
            // 发送失败需要释放频率限制
            RateLimiter::clear($key);
            
            return response()->json(['code' => 500, 'msg' => '验证码发送失败，请稍后再试']);
        } catch (\Exception $e) {
            Log::error('发送异常: ' . $e->getMessage());
            RateLimiter::clear($key);
            return response()->json(['code' => 500, 'msg' => '服务故障，请稍后再试']);
        }

        // 频率限制通过后，标记一次
        RateLimiter::hit($key, 60);

        // 6. 返回结果
        $response = [
            'code' => 200,
            'msg' => '验证码发送成功',
        ];

        // 开发环境为了调试方便，可以在 data 中返回 code
        if (app()->environment('local')) {
            $response['data'] = ['code' => $code];
        } else {
            $response['data'] = [];
        }

        return response()->json($response);
    }

    /**
     * 注册用户
     */
    public function register(Request $request)
    {
        // 1. 数据验证
        $validator = Validator::make($request->all(), [
            'nickname'          => 'required|string|max:50',
            'account'           => 'required|string', // 填手机或邮箱
            'password'          => 'required|string|min:6',
            'verification_code' => 'required|string',
            // 如果是手机，country_code 为必填
            'country_code'      => ['nullable', 'string', Rule::requiredIf(function () use ($request) {
                return !filter_var($request->account, FILTER_VALIDATE_EMAIL);
            })],
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'msg' => $validator->errors()->first()], 422);
        }
    
        $account = $request->account;
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);
    
        // 2. 校验验证码
        $vCode = VerificationCode::where('account', $account)
            ->where('code', $request->verification_code)
            ->where('type', 'register')
            ->where('status', 0) // 未使用
            ->where('expired_at', '>', now()) // 未过期
            ->latest()
            ->first();
    
        if (!$vCode) {
            return response()->json(['code' => 400, 'msg' => '验证码错误或已过期']);
        }
    
        // 3. 检查用户是否已存在
        $existsQuery = $isEmail 
            ? User::where('email', $account)
            : User::where('phone', $account);

        // 如果是手机，必须检查国家代码
        if (!$isEmail) {
            $existsQuery->where('country_code', $request->country_code);
        }
    
        if ($existsQuery->exists()) {
            return response()->json(['code' => 400, 'msg' => '该账号已被注册']);
        }
    
        // 4. 创建用户
        try {
            $user = User::create([
                'nickname'     => $request->nickname,
                'email'        => $isEmail ? $account : null,
                'phone'        => $isEmail ? null : $account,
                'country_code' => $isEmail ? null : $request->country_code,
                'password'     => Hash::make($request->password),
                'ip_address'   => $request->ip(),
            ]);
    
            // 5. 标记验证码为已使用
            $vCode->update(['status' => 1]);

        } catch (\Exception $e) {
            Log::error('用户注册失败: ' . $e->getMessage());
            return response()->json(['code' => 500, 'msg' => '注册失败，请稍后再试']);
        }
    
        // 6. 返回结果（包含Token）
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
/**
     * 用户登录
     */
    public function login(Request $request)
    {
        // 1. 数据验证
        $validator = Validator::make($request->all(), [
            'account'           => 'required|string',
            // 如果是手机，country_code 为必填
            'country_code'      => ['nullable', 'string', Rule::requiredIf(function () use ($request) {
                return !filter_var($request->account, FILTER_VALIDATE_EMAIL);
            })],
            // 密码和验证码至少填写一个
            'password'          => 'required_without:verification_code',
            'verification_code' => 'required_without:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'msg' => $validator->errors()->first()], 422);
        }
    
        $account = $request->account;
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);
    
        // 2. 查找用户
        $user = $isEmail 
            ? User::where('email', $account)->first()
            : User::where('phone', $account)->where('country_code', $request->country_code)->first();
    
        if (!$user) {
            return response()->json(['code' => 404, 'msg' => '账号不存在']);
        }
    
        // 3. 校验凭证
        if ($request->filled('verification_code')) {
            // 验证码模式
            $vCode = VerificationCode::where('account', $account)
                ->where('code', $request->verification_code)
                ->where('type', 'login')
                ->where('status', 0) // 未使用
                ->where('expired_at', '>', now()) // 未过期
                ->latest()
                ->first();

            if (!$vCode) {
                return response()->json(['code' => 400, 'msg' => '验证码错误或已过期']);
            }
            
            $vCode->update(['status' => 1]);
            
        } else {
            // 密码模式
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['code' => 401, 'msg' => '密码错误']);
            }
        }
    
        // 4. 返回结果
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
        // 1. 验证输入
        $validator = Validator::make($request->all(), [
            'account'           => 'required|string',
            // 如果是手机，country_code 为必填
            'country_code'      => ['nullable', 'string', Rule::requiredIf(function () use ($request) {
                return !filter_var($request->account, FILTER_VALIDATE_EMAIL);
            })],
            'verification_code' => 'required|string',
            'new_password'      => 'required|string|min:6|confirmed', // 需配合 new_password_confirmation
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'msg' => $validator->errors()->first()], 422);
        }

        $account = $request->account;
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);

        // 2. 校验验证码
        $vCodeQuery = VerificationCode::where('account', $account)
            ->where('code', $request->verification_code)
            ->where('type', 'reset') 
            ->where('status', 0) // 未使用
            ->where('expired_at', '>', now()); // 未过期

        // 如果是手机，必须带上区号查询
        if (!$isEmail) {
            $vCodeQuery->where('country_code', $request->country_code);
        }

        $vCode = $vCodeQuery->latest()->first();

        if (!$vCode) {
            return response()->json(['code' => 400, 'msg' => '验证码错误或已过期'], 400);
        }

        // 3. 查找用户
        $userQuery = $isEmail 
            ? User::where('email', $account)
            : User::where('phone', $account)->where('country_code', $request->country_code);

        $user = $userQuery->first();

        if (!$user) {
            return response()->json(['code' => 404, 'msg' => '该账号不存在'], 404);
        }

        // 4. 更新密码并消耗验证码
        try {
            DB::beginTransaction();

            $user->update(['password' => Hash::make($request->new_password)]);
            $vCode->update(['status' => 1]); // 标记验证码已使用

            // 5. 让旧的 Token 全部失效，确保安全性 (非常好的实践)
            $user->tokens()->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('重置密码失败: ' . $e->getMessage());
            return response()->json(['code' => 500, 'msg' => '系统繁忙，请稍后再试'], 500);
        }

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