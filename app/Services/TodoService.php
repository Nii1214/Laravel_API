<?php

namespace App\Services;

use App\Models\Todo;

class TodoService
{
    public function getAllTodos()
    {
        return Todo::orderBy('created_at', 'desc')->get();
    }

    public function getTodoById(int $id)
    {
        return Todo::findOrFail($id);
    }

    public function createTodo(array $data)
    {
        return Todo::create($data);
    }

    public function updateTodo(int $id, array $data)
    {
        $todo = Todo::findOrFail($id);
        $todo->update($data);
        return $todo;
    }

    public function deleteTodo(int $id)
    {
        $todo = Todo::findOrFail($id);
        $todo->delete();
        return true;
    }
}
