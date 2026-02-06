<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskSuggestionController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        if ($q === '') return response()->json([]);

        $tasks = Task::query()
            ->where('name', 'like', "%$q%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'type', 'is_default']);

        return response()->json($tasks);
    }
}
