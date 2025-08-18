<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * @test
     * ユーザー登録のテスト
     */
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
            ])
            ->assertJson([
                'message' => 'ユーザー登録が完了しました',
                'data' => [
                    'user' => [
                        'name' => 'テストユーザー',
                        'email' => 'test@example.com'
                    ],
                    'token_type' => 'Bearer'
                ]
            ]);

        // データベースにユーザーが保存されているか確認
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com'
        ]);
    }

    /**
     * @test
     * ユーザー登録時のバリデーションテスト
     */
    public function test_user_registration_validation()
    {
        // 必須フィールドが不足
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error',
                'errors' => [
                    'name',
                    'email',
                    'password'
                ]
            ]);

        // パスワードが一致しない
        $response = $this->postJson('/api/auth/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error',
                'errors' => [
                    'password'
                ]
            ]);

        // メールアドレスが重複
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'テストユーザー2',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error',
                'errors' => [
                    'email'
                ]
            ]);
    }

    /**
     * @test
     * ユーザーログインのテスト
     */
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
            ])
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

    /**
     * @test
     * ログイン時のバリデーションテスト
     */
    public function test_user_login_validation()
    {
        // 認証情報が間違っている
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'メールアドレスまたはパスワードが正しくありません',
                'error' => 'INVALID_CREDENTIALS'
            ]);

        // 必須フィールドが不足
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error',
                'errors' => [
                    'email',
                    'password'
                ]
            ]);
    }

    /**
     * @test
     * ユーザーログアウトのテスト
     */
    public function test_user_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'ログアウトしました',
                'status' => 'success'
            ]);

        // トークンが削除されているか確認
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * @test
     * ユーザー情報取得のテスト
     */
    public function test_get_user_info()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ]
                ]
            ]);
    }

    /**
     * @test
     * 認証状態確認のテスト
     */
    public function test_auth_check()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // 認証済みの場合
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/auth/check');

        $response->assertStatus(200)
            ->assertJson([
                'message' => '認証されています',
                'authenticated' => true
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ]
                ]
            ]);

        // 未認証の場合
        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->getJson('/api/auth/check');

        $response->assertStatus(401)
            ->assertJson([
                'message' => '認証されていません',
                'authenticated' => false
            ]);
    }

    /**
     * @test
     * 認証が必要なエンドポイントのテスト
     */
    public function test_protected_endpoints()
    {
        // 認証なしでアクセス
        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->getJson('/api/auth/user');

        $response->assertStatus(401);

        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }
}
