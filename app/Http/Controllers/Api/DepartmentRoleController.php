<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DepartmentRoleController extends Controller
{
    private string $companyId = '01k7464hqksr4f99rp3ff7x2jz';

    // =============== DÉPARTEMENTS ===============

    public function departments(): JsonResponse
    {
        $departments = Department::where('company_id', $this->companyId)->get();
        return response()->json($departments);
    }

    public function storeDepartment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:150',
        ]);

        $department = Department::create(array_merge($data, [
            'company_id' => $this->companyId
        ]));

        return response()->json($department, 201);
    }

    public function updateDepartment(string $id, Request $request): JsonResponse
    {
        $department = Department::where('id', $id)
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:150',
        ]);

        $department->update($data);
        return response()->json($department);
    }

    public function deleteDepartment(string $id): JsonResponse
    {
        $department = Department::where('id', $id)
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        // 🔒 Optionnel : empêcher la suppression si utilisé par des employés
        if ($department->employees()->exists()) {
            return response()->json([
                'error' => 'Impossible de supprimer ce département : des employés y sont affectés.'
            ], 400);
        }

        $department->delete();
        return response()->json(null, 204);
    }

    // =============== POSTES (RÔLES) ===============

    public function roles(): JsonResponse
    {
        $roles = Role::where('company_id', $this->companyId)->get();
        return response()->json($roles);
    }

    public function storeRole(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
        ]);

        $role = Role::create(array_merge($data, [
            'company_id' => $this->companyId
        ]));

        return response()->json($role, 201);
    }

    public function updateRole(string $id, Request $request): JsonResponse
    {
        $role = Role::where('id', $id)
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:150',
        ]);

        $role->update($data);
        return response()->json($role);
    }

    public function deleteRole(string $id): JsonResponse
    {
        $role = Role::where('id', $id)
            ->where('company_id', $this->companyId)
            ->firstOrFail();

        // 🔒 Optionnel : empêcher la suppression si utilisé
        if ($role->employees()->exists()) {
            return response()->json([
                'error' => 'Impossible de supprimer ce poste : des employés y sont affectés.'
            ], 400);
        }

        $role->delete();
        return response()->json(null, 204);
    }
}
