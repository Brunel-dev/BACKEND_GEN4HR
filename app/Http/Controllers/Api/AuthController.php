<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        // Récupérer l'employé associé à cet email
        $employee = Employee::where('email', $user->email)->first();

        // Déterminer le rôle à partir de l'employé
        $role = 'employe'; // Valeur par défaut

        if ($employee && $employee->role) {
            $roleName = $employee->role->name ?? '';
            if (str_contains($roleName, 'RH') || str_contains($roleName, 'Admin')) {
                $role = 'admin';
            } elseif ($roleName === 'Comptable') {
                $role = 'comptable';
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $role, // ✅ Utilise le rôle calculé
                'employee_id' => $employee ? $employee->id : null,
            ],
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('email', $user->email)->first();

        $role = 'employe';
        if ($employee && $employee->role) {
            $roleName = $employee->role->name ?? '';
            if (str_contains($roleName, 'RH') || str_contains($roleName, 'Admin')) {
                $role = 'admin';
            } elseif ($roleName === 'Comptable') {
                $role = 'comptable';
            }
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $role,
                'employee' => $employee
            ]
        ]);
    }
}
