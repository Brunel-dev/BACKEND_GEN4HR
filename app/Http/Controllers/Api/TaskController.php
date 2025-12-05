<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Employee;
use App\Models\TaskStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    // 1. Lister toutes les tâches
    public function index(): JsonResponse
    {
        $companyId = '01k7464hqksr4f99rp3ff7x2jz'; // ton ID fixe
        $tasks = Task::with('assignments.assignedTo')
            ->where('organization_id', $companyId)
            ->get();
        return response()->json($tasks);
    }

    // 2. Créer une tâche
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $task = Task::create([
            'organization_id' => '01k7464hqksr4f99rp3ff7x2jz',
            'title' => $data['title'],
            'description' => $data['description'],
            'created_by' => null, // ou un employee_id si tu veux
        ]);

        return response()->json($task, 201);
    }

    // 3. Attribuer une tâche à un employé
    public function assign(Request $request, string $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $data = $request->validate([
            'assigned_to' => 'required|exists:employees,id',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high,critical',
            'notes' => 'nullable|string',
        ]);

        $assignment = TaskAssignment::create([
            'task_id' => $task->id,
            'assigned_to' => $data['assigned_to'],
            'assigned_by' => null, // ou ID d’un manager
            'assigned_at' => now(),
            'due_date' => $data['due_date'],
            'status' => 'todo',
            'priority' => $data['priority'] ?? 'medium',
            'progress' => 0,
            'notes' => $data['notes'],
        ]);

        // Historique
        TaskStatusHistory::create([
            'task_assignment_id' => $assignment->id,
            'old_status' => null,
            'new_status' => 'todo',
            'changed_by' => null,
            'note' => 'Tâche attribuée',
            'changed_at' => now(),
        ]);

        return response()->json($assignment, 201);
    }

    // 4. Mettre à jour le statut d’une attribution
    public function updateStatus(Request $request, string $assignmentId): JsonResponse
    {
        $assignment = TaskAssignment::findOrFail($assignmentId);
        $newStatus = $request->input('status');

        if (!in_array($newStatus, ['todo', 'in_progress', 'done', 'cancelled'])) {
            return response()->json(['error' => 'Statut invalide'], 400);
        }

        $oldStatus = $assignment->status;
        $assignment->update(['status' => $newStatus]);

        // Historique
        TaskStatusHistory::create([
            'task_assignment_id' => $assignment->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => null,
            'note' => $request->input('note', 'Statut mis à jour'),
            'changed_at' => now(),
        ]);

        return response()->json($assignment);
    }
}
