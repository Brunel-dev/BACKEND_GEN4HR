<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    private string $companyId = '01k7464hqksr4f99rp3ff7x2jz';

    // Lister toutes les tâches
    public function index(): JsonResponse
    {
        $tasks = Task::with('assignedTo')
            ->where('company_id', $this->companyId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks);
    }

    // Créer une tâche (assignation incluse)
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'assigned_to' => 'required|exists:employees,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date_format:H:i:s', // Heure uniquement
            'status' => 'nullable|in:todo,in_progress,done,cancelled,in_review'
        ]);

        // Vérifier que l'employé appartient à la company
        $employee = Employee::where('id', $data['assigned_to'])
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        $task = Task::create(array_merge($data, [
            'company_id' => $this->companyId,
            'status' => $data['status'] ?? 'todo'
        ]));

        return response()->json($task, 201);
    }

    // Afficher une tâche
    public function show(string $id): JsonResponse
    {
        $task = Task::with('assignedTo')
            ->where('id', $id)
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        return response()->json($task);
    }

    // Modifier une tâche (y compris réassigner)
    public function update(string $id, Request $request): JsonResponse
    {
        $task = Task::where('id', $id)
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        $data = $request->validate([
            'assigned_to' => 'nullable|exists:employees,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date_format:H:i:s',
            'status' => 'nullable|in:todo,in_progress,done,cancelled,in_review'
        ]);

        $status = $request->validate([
            'status' => 'required|in:todo,in_progress,done,cancelled,in_review'
        ])['status'];

        $updateData = ['status' => $status];

        // Si la tâche passe à "done", enregistrer la date/heure de réalisation
        if ($status === 'done' && is_null($task->due_date)) {
            $updateData['due_date'] = now();
        }

        // Si la tâche est réouverte (ex: de "done" à "todo"), on peut vider due_date
        if ($status !== 'done' && !is_null($task->due_date)) {
            $updateData['due_date'] = null;
        }

        // Si changement d'employé, vérifier qu'il est dans la company
        if (isset($data['assigned_to'])) {
            Employee::where('id', $data['assigned_to'])
                ->where('company_id', $this->companyId)
                ->firstOrFail();
        }

        $task->update($data);
        return response()->json($task);
    }

    // Supprimer une tâche
    public function destroy(string $id): JsonResponse
    {
        $task = Task::where('id', $id)
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        $task->delete();
        return response()->json(null, 204);
    }

    // Raccourci : Mettre à jour le statut seulement
    public function updateStatus(string $id, Request $request): JsonResponse
    {
        $task = Task::where('id', $id)
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        $status = $request->validate([
            'status' => 'required|in:todo,in_progress,done,cancelled,in_review'
        ])['status'];

        $task->update(['status' => $status]);
        return response()->json($task);
    }

    // Liste des tâches assignées à un employé

    public function tasksByEmployee(string $employeeId): JsonResponse
    {
        // Vérifier que l'employé existe et appartient à la company
        Employee::where('id', $employeeId)
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        $tasks = Task::with('createdBy', 'assignedTo')
            ->where('assigned_to', $employeeId)
            ->where('company_id', $this->companyId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks);
    }

    // Tâches pour un employé/comptable
    public function myTasks(Request $request)
    {
        $user = $request->user();
        if (!$user->employee_id) {
            return response()->json([]);
        }

        return Task::where('assigned_to', $user->employee_id)
            ->where('status', '!=', 'done')
            ->get();
    }

}
