<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TodoPolicy
{
    use HandlesAuthorization;

    /**
     * 任意のTodoを閲覧できるか
     */
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    /**
     * 指定されたTodoを閲覧できるか
     */
    public function view(?User $user, Todo $todo): bool
    {
        return $user !== null && $user->id === $todo->user_id;
    }

    /**
     * Todoを作成できるか
     */
    public function create(?User $user): bool
    {
        return $user !== null;
    }

    /**
     * 指定されたTodoを更新できるか
     */
    public function update(?User $user, Todo $todo): bool
    {
        return $user !== null && $user->id === $todo->user_id;
    }

    /**
     * 指定されたTodoを削除できるか
     */
    public function delete(?User $user, Todo $todo): bool
    {
        return $user !== null && $user->id === $todo->user_id;
    }

    /**
     * 指定されたTodoを復元できるか
     */
    public function restore(?User $user, Todo $todo): bool
    {
        return $user !== null && $user->id === $todo->user_id;
    }

    /**
     * 指定されたTodoを完全に削除できるか
     */
    public function forceDelete(?User $user, Todo $todo): bool
    {
        return $user !== null && $user->id === $todo->user_id;
    }
}
