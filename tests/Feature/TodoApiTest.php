<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TodoApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザー作成とトークン取得
        $this->user = User::factory()->create();
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password'
        ]);
        $this->token = $response->json('token');
    }

    /**
     * @test
     * ToDo一覧取得のテスト
     */
    public function test_todo_index_returns_paginated_todos()
    {
        // テストデータ作成
        Todo::factory()->count(25)->create(['user_id' => $this->user->id]);
        Todo::factory()->count(5)->create(['user_id' => $this->user->id, 'completed' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'completed',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'total_pages'
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next'
                ]
            ])
            ->assertJson([
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 30,
                    'total_pages' => 2
                ]
            ]);

        // レスポンスヘッダーの確認
        $response->assertHeader('Cache-Control', 'public, max-age=300');
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
    }

    /**
     * @test
     * ページネーションパラメータのテスト
     */
    public function test_todo_index_with_pagination_parameters()
    {
        Todo::factory()->count(50)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos?page=2&limit=10');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'current_page' => 2,
                    'per_page' => 10,
                    'total' => 50,
                    'total_pages' => 5
                ]
            ]);
    }

    /**
     * @test
     * フィルタリング機能のテスト
     */
    public function test_todo_index_with_filtering()
    {
        Todo::factory()->count(10)->create(['user_id' => $this->user->id, 'completed' => false]);
        Todo::factory()->count(5)->create(['user_id' => $this->user->id, 'completed' => true]);

        // 完了済みのみフィルタ
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos?completed=true');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'total' => 5
                ]
            ]);

        // 未完了のみフィルタ
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos?completed=false');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'total' => 10
                ]
            ]);
    }

    /**
     * @test
     * ソート機能のテスト
     */
    public function test_todo_index_with_sorting()
    {
        $todo1 = Todo::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'A Task',
            'created_at' => now()->subDays(2)
        ]);
        $todo2 = Todo::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'B Task',
            'created_at' => now()->subDay()
        ]);
        $todo3 = Todo::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'C Task',
            'created_at' => now()
        ]);

        // タイトル昇順ソート
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos?sort=title&order=asc');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('A Task', $data[0]['title']);
        $this->assertEquals('B Task', $data[1]['title']);
        $this->assertEquals('C Task', $data[2]['title']);

        // 作成日時降順ソート（デフォルト）
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos?sort=created_at&order=desc');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('C Task', $data[0]['title']);
        $this->assertEquals('B Task', $data[1]['title']);
        $this->assertEquals('A Task', $data[2]['title']);
    }

    /**
     * @test
     * ToDo詳細取得のテスト
     */
    public function test_todo_show_returns_single_todo()
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/todos/{$todo->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'completed',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'description' => $todo->description,
                    'completed' => $todo->completed
                ]
            ]);

        // キャッシュヘッダーの確認
        $response->assertHeader('Cache-Control', 'public, max-age=600');
    }

    /**
     * @test
     * ToDo作成のテスト
     */
    public function test_todo_store_creates_new_todo()
    {
        $todoData = [
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'completed' => false
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->postJson('/api/v1/todos', $todoData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'completed',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Todo',
                    'description' => 'Test Description',
                    'completed' => false
                ],
                'message' => 'Todo created successfully'
            ]);

        // データベースに保存されているか確認
        $this->assertDatabaseHas('todos', [
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'completed' => false,
            'user_id' => $this->user->id
        ]);
    }

    /**
     * @test
     * ToDo作成時のバリデーションテスト
     */
    public function test_todo_store_validation_errors()
    {
        // タイトルが空の場合
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->postJson('/api/v1/todos', [
            'description' => 'Test Description'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error',
                'errors' => [
                    'title'
                ]
            ])
            ->assertJson([
                'message' => 'The given data was invalid.',
                'error' => 'VALIDATION_ERROR'
            ]);

        // タイトルが長すぎる場合
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->postJson('/api/v1/todos', [
            'title' => str_repeat('a', 256),
            'description' => 'Test Description'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error',
                'errors' => [
                    'title'
                ]
            ]);

        // 説明が長すぎる場合
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->postJson('/api/v1/todos', [
            'title' => 'Test Todo',
            'description' => str_repeat('a', 1001)
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'error',
                'errors' => [
                    'description'
                ]
            ]);
    }

    /**
     * @test
     * ToDo更新（PUT）のテスト
     */
    public function test_todo_update_with_put()
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated Todo',
            'description' => 'Updated Description',
            'completed' => true
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->putJson("/api/v1/todos/{$todo->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'completed',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Updated Todo',
                    'description' => 'Updated Description',
                    'completed' => true
                ],
                'message' => 'Todo updated successfully'
            ]);

        // データベースが更新されているか確認
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Updated Todo',
            'description' => 'Updated Description',
            'completed' => true
        ]);
    }

    /**
     * @test
     * ToDo更新（PATCH）のテスト
     */
    public function test_todo_update_with_patch()
    {
        $todo = Todo::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'completed' => false
        ]);

        // 完了状態のみ更新
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->patchJson("/api/v1/todos/{$todo->id}", [
            'completed' => true
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Original Title',
                    'description' => 'Original Description',
                    'completed' => true
                ]
            ]);

        // データベースが部分更新されているか確認
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'completed' => true
        ]);
    }

    /**
     * @test
     * ToDo削除のテスト
     */
    public function test_todo_destroy_deletes_todo()
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->deleteJson("/api/v1/todos/{$todo->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Todo deleted successfully',
                'status' => 'success'
            ]);

        // データベースから削除されているか確認
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    /**
     * @test
     * 認証エラーのテスト
     */
    public function test_authentication_error()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
                'error' => 'UNAUTHENTICATED'
            ]);
    }

    /**
     * @test
     * 認可エラーのテスト
     */
    public function test_authorization_error()
    {
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/todos/{$todo->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You do not have permission to access this resource.',
                'error' => 'FORBIDDEN'
            ]);
    }

    /**
     * @test
     * 存在しないリソースのテスト
     */
    public function test_not_found_error()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos/99999');

        $response->assertStatus(404);
    }

    /**
     * @test
     * レート制限のテスト
     */
    public function test_rate_limiting()
    {
        // レート制限を一時的に下げる
        RateLimiter::for('api', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5);
        });

        // 6回リクエストを送信
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json'
            ])->getJson('/api/v1/todos');

            $response->assertStatus(200);
        }

        // 6回目でレート制限エラー
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos');

        $response->assertStatus(429)
            ->assertJson([
                'message' => 'Too many requests.',
                'error' => 'RATE_LIMIT_EXCEEDED'
            ])
            ->assertJsonStructure([
                'retry_after'
            ]);
    }

    /**
     * @test
     * キャッシュ機能のテスト
     */
    public function test_cache_functionality()
    {
        // キャッシュをクリア
        Cache::flush();

        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        // 初回アクセス
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/todos/{$todo->id}");

        $response1->assertStatus(200);

        // キャッシュキーが存在するか確認
        $this->assertTrue(Cache::has("todo_{$todo->id}"));

        // 2回目アクセス（キャッシュから取得）
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/todos/{$todo->id}");

        $response2->assertStatus(200);

        // 更新後はキャッシュが無効化される
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->patchJson("/api/v1/todos/{$todo->id}", [
            'completed' => true
        ]);

        // キャッシュが削除されているか確認
        $this->assertFalse(Cache::has("todo_{$todo->id}"));
    }

    /**
     * @test
     * 多言語対応のテスト
     */
    public function test_multilingual_support()
    {
        $todoData = [
            'title' => 'Test Todo',
            'description' => 'Test Description'
        ];

        // 日本語リクエスト
        $responseJa = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Accept-Language' => 'ja',
            'Content-Type' => 'application/json'
        ])->postJson('/api/v1/todos', $todoData);

        $responseJa->assertStatus(201)
            ->assertJson([
                'message' => 'ToDoが正常に作成されました'
            ]);

        // 英語リクエスト
        $responseEn = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'Content-Type' => 'application/json'
        ])->postJson('/api/v1/todos', $todoData);

        $responseEn->assertStatus(201)
            ->assertJson([
                'message' => 'Todo created successfully'
            ]);
    }

    /**
     * @test
     * ページ範囲外のテスト
     */
    public function test_pagination_out_of_range()
    {
        Todo::factory()->count(10)->create(['user_id' => $this->user->id]);

        // 存在しないページ番号を指定
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/todos?page=999');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'meta' => [
                    'current_page' => 999,
                    'total' => 10
                ]
            ]);
    }
}
