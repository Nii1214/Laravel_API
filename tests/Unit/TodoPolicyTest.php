<?php

namespace Tests\Unit;

use App\Models\Todo;
use App\Models\User;
use App\Policies\TodoPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TodoPolicy $policy;
    private User $user;
    private User $otherUser;
    private Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TodoPolicy();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->todo = Todo::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * @test
     * ログインユーザーは自分のToDoを閲覧できる
     */
    public function test_user_can_view_own_todo()
    {
        $result = $this->policy->view($this->user, $this->todo);

        $this->assertTrue($result);
    }

    /**
     * @test
     * ログインユーザーは他のユーザーのToDoを閲覧できない
     */
    public function test_user_cannot_view_other_users_todo()
    {
        $result = $this->policy->view($this->otherUser, $this->todo);

        $this->assertFalse($result);
    }

    /**
     * @test
     * ログインユーザーは自分のToDoを更新できる
     */
    public function test_user_can_update_own_todo()
    {
        $result = $this->policy->update($this->user, $this->todo);

        $this->assertTrue($result);
    }

    /**
     * @test
     * ログインユーザーは他のユーザーのToDoを更新できない
     */
    public function test_user_cannot_update_other_users_todo()
    {
        $result = $this->policy->update($this->otherUser, $this->todo);

        $this->assertFalse($result);
    }

    /**
     * @test
     * ログインユーザーは自分のToDoを削除できる
     */
    public function test_user_can_delete_own_todo()
    {
        $result = $this->policy->delete($this->user, $this->todo);

        $this->assertTrue($result);
    }

    /**
     * @test
     * ログインユーザーは他のユーザーのToDoを削除できない
     */
    public function test_user_cannot_delete_other_users_todo()
    {
        $result = $this->policy->delete($this->otherUser, $this->todo);

        $this->assertFalse($result);
    }

    /**
     * @test
     * ユーザーは自分のToDoを作成できる
     */
    public function test_user_can_create_todo()
    {
        $result = $this->policy->create($this->user);

        $this->assertTrue($result);
    }

    /**
     * @test
     * 認証されていないユーザーはToDoを閲覧できない
     */
    public function test_unauthenticated_user_cannot_view_todo()
    {
        // 認証されていないユーザーはnullとして扱う
        $result = $this->policy->view(null, $this->todo);

        $this->assertFalse($result);
    }

    /**
     * @test
     * 認証されていないユーザーはToDoを更新できない
     */
    public function test_unauthenticated_user_cannot_update_todo()
    {
        // 認証されていないユーザーはnullとして扱う
        $result = $this->policy->update(null, $this->todo);

        $this->assertFalse($result);
    }

    /**
     * @test
     * 認証されていないユーザーはToDoを削除できない
     */
    public function test_unauthenticated_user_cannot_delete_todo()
    {
        // 認証されていないユーザーはnullとして扱う
        $result = $this->policy->delete(null, $this->todo);

        $this->assertFalse($result);
    }

    /**
     * @test
     * 認証されていないユーザーはToDoを作成できない
     */
    public function test_unauthenticated_user_cannot_create_todo()
    {
        // 認証されていないユーザーはnullとして扱う
        $result = $this->policy->create(null);

        $this->assertFalse($result);
    }

    /**
     * @test
     * ユーザーは自分のToDo一覧を閲覧できる
     */
    public function test_user_can_view_own_todos()
    {
        $result = $this->policy->viewAny($this->user);

        $this->assertTrue($result);
    }

    /**
     * @test
     * 認証されていないユーザーはToDo一覧を閲覧できない
     */
    public function test_unauthenticated_user_cannot_view_todos()
    {
        $result = $this->policy->viewAny(null);

        $this->assertFalse($result);
    }
}
