<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * ユーザー登録
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => '名前は必須です',
            'email.required' => 'メールアドレスは必須です',
            'email.email' => '有効なメールアドレスを入力してください',
            'email.unique' => 'このメールアドレスは既に使用されています',
            'password.required' => 'パスワードは必須です',
            'password.min' => 'パスワードは8文字以上で入力してください',
            'password.confirmed' => 'パスワードが一致しません',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'error' => 'VALIDATION_ERROR',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // ユーザー作成
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // トークン生成
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'ユーザー登録が完了しました',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at->toISOString(),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'ユーザー登録に失敗しました',
                'error' => 'REGISTRATION_FAILED'
            ], 500);
        }
    }

    /**
     * ユーザーログイン
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'メールアドレスは必須です',
            'email.email' => '有効なメールアドレスを入力してください',
            'password.required' => 'パスワードは必須です',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'error' => 'VALIDATION_ERROR',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 認証試行
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'メールアドレスまたはパスワードが正しくありません',
                    'error' => 'INVALID_CREDENTIALS'
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            // 既存のトークンを削除（セキュリティ向上）
            $user->tokens()->delete();

            // 新しいトークン生成
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'ログインに成功しました',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at->toISOString(),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'ログインに失敗しました',
                'error' => 'LOGIN_FAILED'
            ], 500);
        }
    }

    /**
     * ユーザーログアウト
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // 現在のトークンを削除
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'ログアウトしました',
                'status' => 'success'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'ログアウトに失敗しました',
                'error' => 'LOGOUT_FAILED'
            ], 500);
        }
    }

    /**
     * ユーザー情報取得
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at->toISOString(),
                        'updated_at' => $user->updated_at->toISOString(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'ユーザー情報の取得に失敗しました',
                'error' => 'USER_FETCH_FAILED'
            ], 500);
        }
    }

    /**
     * 認証状態確認
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        try {
            if (Auth::check()) {
                return response()->json([
                    'message' => '認証されています',
                    'authenticated' => true,
                    'data' => [
                        'user' => [
                            'id' => $request->user()->id,
                            'name' => $request->user()->name,
                            'email' => $request->user()->email,
                        ]
                    ]
                ], 200);
            }

            return response()->json([
                'message' => '認証されていません',
                'authenticated' => false
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'message' => '認証状態の確認に失敗しました',
                'error' => 'AUTH_CHECK_FAILED'
            ], 500);
        }
    }
}
