<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodoResource;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\TodoService;

class TodoController extends Controller
{
    protected $todoService;

    public function __construct(TodoService $todoService)
    {
        $this->todoService = $todoService;
    }

    /**
     * 全件表示
     */
    public function index()
    {
        $todos = $this->todoService->getAllTodos();
        return TodoResource::collection($todos);
    }

    /**
     * 詳細表示
     */
    public function show(string $id)
    {
        $todo = $this->todoService->getTodoById($id);;
        return new TodoResource($todo);
    }

    /**
     * 新規作成
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
        ]);

        $todo = $this->todoService->createTodo($validatedData);

        return (new TodoResource($todo))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * 更新
     */
    public function update(Request $request, string $id)
    {
        $todo = Todo::findOrFail($id);

        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'sometimes|boolean',
        ]);

        $todo->update($validatedData);

        return new TodoResource($todo);
    }

    /**
     * 削除
     */
    public function destroy(string $id)
    {
        $todo = Todo::findOrFail($id);
        $todo->delete();

        return response()->json([
            'message' => 'Todo deleted successfully',
            'status' => 'success'
        ], Response::HTTP_OK);
    }
}
