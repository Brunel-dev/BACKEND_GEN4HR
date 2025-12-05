<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskStatusHistory;
use Illuminate\Http\JsonResponse;

class TaskStatusHistoryController extends Controller
{
    /**
     * Display a listing of the status history for a given task assignment.
     *
     * @param int $assignmentId
     * @return JsonResponse
     */
    public function index(int $assignmentId): JsonResponse
    {
        $history = TaskStatusHistory::with('changedBy')
            ->where('task_assignment_id', $assignmentId)
            ->orderBy('changed_at', 'desc')
            ->get();

        return response()->json($history);
    }

    /**
     * Display the specified status history entry.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $entry = TaskStatusHistory::with('assignment.task', 'changedBy')
            ->findOrFail($id);

        return response()->json($entry);
    }
}
