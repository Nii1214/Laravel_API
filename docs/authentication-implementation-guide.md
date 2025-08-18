# Laravel Sanctum認証システム実装ガイド

## 目次
1. [概要](#概要)
2. [技術スタック](#技術スタック)
3. [実装前準備](#実装前準備)
4. [段階的実装手順](#段階的実装手順)
5. [技術的詳細](#技術的詳細)
6. [セキュリティ考慮事項](#セキュリティ考慮事項)
7. [テスト実装](#テスト実装)
8. [Next.js連携](#nextjs連携)
9. [トラブルシューティング](#トラブルシューティング)
10. [学習ポイント](#学習ポイント)

## 概要

### 実装する機能
- ユーザー登録（新規アカウント作成）
- ユーザーログイン（既存アカウント認証）
- ユーザーログアウト（セッション終了）
- ユーザー情報取得（認証済みユーザーの情報）
- 認証状態確認（トークンの有効性確認）

### 認証方式の選択理由
**Laravel Sanctum**を選択した理由：
- **初学者向け**: シンプルで理解しやすい
- **セキュリティ**: Laravel公式の安全な認証システム
- **SPA対応**: Next.jsなどのモダンなフロントエンドと相性が良い
- **軽量**: 必要最小限の機能で高速

## 技術スタック

### バックエンド
- **Laravel 11**: PHPフレームワーク
- **Laravel Sanctum**: API認証ライブラリ
- **MySQL**: データベース
- **Docker**: 開発環境

### フロントエンド（連携先）
- **Next.js**: Reactフレームワーク
- **TypeScript**: 型安全なJavaScript

## 実装前準備

### 1. Laravel Sanctumのインストール
```bash
# Sanctumパッケージのインストール
composer require laravel/sanctum

# 設定ファイルの公開
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 2. マイグレーションの実行
```bash
# データベースマイグレーション
php artisan migrate
```

### 3. アプリケーションキーの生成
```bash
# アプリケーションキーの生成
php artisan key:generate
```

## 段階的実装手順

### Step 1: Userモデルの更新

#### 1.1 HasApiTokensトレイトの追加
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 追加

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // HasApiTokensを追加

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Todoとのリレーションシップ
    public function todos()
    {
        return $this->hasMany(Todo::class);
    }
}
```

#### 1.2 重要なポイント
- **HasApiTokens**: Sanctumのトークン管理機能を有効化
- **リレーションシップ**: ユーザーとToDoの関連付け

### Step 2: 認証コントローラーの作成

#### 2.1 AuthControllerの基本構造
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // 各メソッドを実装
}
```

#### 2.2 ユーザー登録メソッド
```php
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
```

#### 2.3 ログインメソッド
```php
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
```

#### 2.4 ログアウトメソッド
```php
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
```

#### 2.5 ユーザー情報取得メソッド
```php
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
```

### Step 3: APIルートの設定

#### 3.1 routes/api.phpの更新
```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TodoController;

// 認証関連のルート（認証不要）
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    // 認証関連
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::get('/auth/check', [AuthController::class, 'check']);

    // ToDo関連
    Route::get('/todos', [TodoController::class, 'index']);
    Route::post('/todos', [TodoController::class, 'store']);
    Route::get('/todos/{todo}', [TodoController::class, 'show']);
    Route::put('/todos/{todo}', [TodoController::class, 'update']);
    Route::patch('/todos/{todo}', [TodoController::class, 'update']);
    Route::delete('/todos/{todo}', [TodoController::class, 'destroy']);
});
```

#### 3.2 ルート設計のポイント
- **認証不要ルート**: 登録・ログインは認証なしでアクセス可能
- **認証必須ルート**: ログアウト・ユーザー情報・ToDo操作は認証が必要
- **ミドルウェア**: `auth:sanctum`でトークン認証を強制

### Step 4: リソースクラスの作成

#### 4.1 UserResourceの作成
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

## 技術的詳細

### Sanctumの仕組み

#### 1. トークン生成
```php
// ユーザーに対してトークンを生成
$token = $user->createToken('auth_token')->plainTextToken;
```

#### 2. トークン検証
```php
// リクエストからユーザーを取得（自動的にトークンを検証）
$user = $request->user();
```

#### 3. トークン削除
```php
// 現在のトークンを削除
$request->user()->currentAccessToken()->delete();

// すべてのトークンを削除
$user->tokens()->delete();
```

### バリデーションの詳細

#### 1. カスタムエラーメッセージ
```php
$validator = Validator::make($request->all(), [
    'email' => 'required|string|email|unique:users',
    'password' => 'required|string|min:8|confirmed',
], [
    'email.required' => 'メールアドレスは必須です',
    'email.email' => '有効なメールアドレスを入力してください',
    'email.unique' => 'このメールアドレスは既に使用されています',
    'password.confirmed' => 'パスワードが一致しません',
]);
```

#### 2. バリデーションルールの説明
- **required**: 必須フィールド
- **string**: 文字列型
- **email**: メールアドレス形式
- **unique:users**: usersテーブルで一意
- **min:8**: 最小8文字
- **confirmed**: 確認フィールドと一致

### エラーハンドリング

#### 1. 統一されたエラーレスポンス
```php
return response()->json([
    'message' => 'エラーメッセージ',
    'error' => 'ERROR_CODE',
    'errors' => $validator->errors() // バリデーションエラーの場合
], 422);
```

#### 2. HTTPステータスコード
- **200 OK**: 成功
- **201 Created**: 作成成功
- **401 Unauthorized**: 認証エラー
- **422 Unprocessable Entity**: バリデーションエラー
- **500 Internal Server Error**: サーバーエラー

## セキュリティ考慮事項

### 1. パスワードハッシュ化
```php
// パスワードをハッシュ化して保存
'password' => Hash::make($request->password),
```

### 2. トークン管理
```php
// ログイン時に既存のトークンを削除（セキュリティ向上）
$user->tokens()->delete();
```

### 3. 認証ミドルウェア
```php
// 認証が必要なルートを保護
Route::middleware('auth:sanctum')->group(function () {
    // 保護されたルート
});
```

### 4. 入力値検証
```php
// すべての入力値をバリデーション
$validator = Validator::make($request->all(), [
    'email' => 'required|string|email',
    'password' => 'required|string',
]);
```

## テスト実装

### 1. テストファイルの作成
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    // テストメソッドを実装
}
```

### 2. ユーザー登録テスト
```php
public function test_user_registration()
{
    $userData = [
        'name' => 'テストユーザー',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at'
                ],
                'token',
                'token_type'
            ]
        ]);

    // データベースにユーザーが保存されているか確認
    $this->assertDatabaseHas('users', [
        'name' => 'テストユーザー',
        'email' => 'test@example.com'
    ]);
}
```

### 3. ログインテスト
```php
public function test_user_login()
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'ログインに成功しました',
            'data' => [
                'user' => [
                    'email' => 'test@example.com'
                ],
                'token_type' => 'Bearer'
            ]
        ]);
}
```

### 4. テスト実行
```bash
# 認証APIのテスト実行
php artisan test tests/Feature/AuthApiTest.php

# 全テスト実行
php artisan test
```

## Next.js連携

### 1. ユーザー登録
```javascript
const register = async (userData) => {
  const response = await fetch('/api/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(userData),
  });
  
  const data = await response.json();
  
  if (response.ok) {
    // トークンを保存
    localStorage.setItem('token', data.data.token);
    return data;
  } else {
    throw new Error(data.message);
  }
};
```

### 2. ログイン
```javascript
const login = async (credentials) => {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(credentials),
  });
  
  const data = await response.json();
  
  if (response.ok) {
    // トークンを保存
    localStorage.setItem('token', data.data.token);
    return data;
  } else {
    throw new Error(data.message);
  }
};
```

### 3. 認証が必要なAPI呼び出し
```javascript
const getUserInfo = async () => {
  const token = localStorage.getItem('token');
  
  const response = await fetch('/api/auth/user', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  
  const data = await response.json();
  
  if (response.ok) {
    return data;
  } else {
    throw new Error(data.message);
  }
};
```

### 4. ログアウト
```javascript
const logout = async () => {
  const token = localStorage.getItem('token');
  
  const response = await fetch('/api/auth/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  
  if (response.ok) {
    // ローカルストレージからトークンを削除
    localStorage.removeItem('token');
  }
};
```

## トラブルシューティング

### 1. よくある問題と解決方法

#### 問題: Sanctumがインストールされていない
```bash
# 解決方法
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

#### 問題: トークン認証が失敗する
```php
// 確認事項
// 1. UserモデルにHasApiTokensトレイトが追加されているか
// 2. ルートにauth:sanctumミドルウェアが設定されているか
// 3. トークンが正しく送信されているか
```

#### 問題: CORSエラーが発生する
```php
// config/cors.phpで設定を確認
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

### 2. デバッグ方法

#### ログの確認
```bash
# Laravelログの確認
tail -f storage/logs/laravel.log
```

#### データベースの確認
```bash
# ユーザーテーブルの確認
php artisan tinker
>>> App\Models\User::all();
```

#### トークンテーブルの確認
```bash
# トークンテーブルの確認
php artisan tinker
>>> DB::table('personal_access_tokens')->get();
```

## 学習ポイント

### 1. 重要な概念

#### Sanctumの仕組み
- **トークンベース認証**: セッションではなくトークンで認証
- **ステートレス**: サーバー側でセッション情報を保持しない
- **軽量**: 必要最小限の機能で高速

#### セキュリティ
- **パスワードハッシュ化**: 平文で保存しない
- **トークン管理**: 適切なトークンの生成・削除
- **入力値検証**: すべての入力値をバリデーション

### 2. 実践的なスキル

#### API設計
- **RESTful設計**: 適切なHTTPメソッドとステータスコード
- **統一レスポンス**: 一貫性のあるJSONレスポンス
- **エラーハンドリング**: 適切なエラー処理

#### テスト駆動開発
- **機能テスト**: APIエンドポイントの動作確認
- **バリデーションテスト**: 入力値検証の確認
- **セキュリティテスト**: 認証・認可の確認

### 3. 次のステップ

#### 機能拡張
- **パスワードリセット**: メール送信機能
- **メール認証**: アカウント有効化
- **2FA認証**: 二要素認証
- **ソーシャルログイン**: OAuth連携

#### セキュリティ強化
- **レート制限**: ログイン試行回数制限
- **セッション管理**: トークンの有効期限設定
- **監査ログ**: 認証ログの記録

### 4. ベストプラクティス

#### コード品質
- **単一責任**: 各メソッドは一つの責任を持つ
- **DRY原則**: 重複コードを避ける
- **型宣言**: 適切な型宣言を使用

#### ドキュメント
- **API仕様書**: 詳細なAPI仕様の記録
- **コメント**: 重要なロジックの説明
- **README**: セットアップ手順の記録

## まとめ

この実装ガイドを通じて、Laravel Sanctumを使用した安全で使いやすい認証システムを構築できました。

### 実装のポイント
1. **段階的な実装**: 小さなステップで確実に実装
2. **セキュリティ重視**: 認証システムの安全性を最優先
3. **テスト駆動**: 動作確認を確実に行う
4. **ドキュメント化**: 実装内容を明確に記録

### 学習効果
- Laravel Sanctumの理解
- API設計の実践
- セキュリティの重要性
- テスト駆動開発の体験

この実装を基盤として、さらに高度な機能を追加していくことができます。
