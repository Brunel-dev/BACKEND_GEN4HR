<?php

use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\CheckController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RefreshController;
use App\Http\Controllers\Auth\WebhookController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentRoleController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes API organisées par fonctionnalité et middleware
|
*/

// ─────── 1. AUTHENTIFICATION ───────

// Routes Genuka OAuth (sans authentification)
Route::prefix('auth')->group(function () {
    Route::get('/callback', CallbackController::class);
    Route::get('/check', CheckController::class);
    Route::post('/refresh', RefreshController::class);
    Route::get('/me', MeController::class);
    Route::post('/logout', LogoutController::class);
    Route::post('/webhook', WebhookController::class);
});

// Authentification interne (username/password)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

// ─────── 2. ROUTES PROTÉGÉES (AUTHENTIFICATION REQUISE) ───────

Route::middleware('auth:sanctum')->group(function () {

    // ─── 2.1. Ressources communes ───
    Route::get('/company', [ResourceController::class, 'company']);

    // Employés
    Route::get('/employees', [ResourceController::class, 'employees']);
    Route::post('/employees', [ResourceController::class, 'storeEmployee']);
    Route::get('/employees/top-paid', [EmployeeController::class, 'topPaidEmployees']);
    Route::get('/employees/{employeeId}/payments', [ResourceController::class, 'employeePayments']);
    Route::get('/employees/{employeeId}/tasks', [TaskController::class, 'tasksByEmployee']);

    // Tâches
    Route::get('/tasks', [ResourceController::class, 'tasks']);
    Route::get('/tasks/my', [TaskController::class, 'myTasks']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);

    // Paies
    Route::post('/salaries/pay', [ResourceController::class, 'paySalary']);

    // ─── 2.2. Ressources administratives (requiert rôle admin) ───
    Route::middleware('role:admin')->group(function () {
        // Départements
        Route::get('/departments', [DepartmentRoleController::class, 'departments']);
        Route::post('/departments', [DepartmentRoleController::class, 'storeDepartment']);
        Route::put('/departments/{id}', [DepartmentRoleController::class, 'updateDepartment']);
        Route::delete('/departments/{id}', [DepartmentRoleController::class, 'deleteDepartment']);

        // Rôles/Postes
        Route::get('/roles', [DepartmentRoleController::class, 'roles']);
        Route::post('/roles', [DepartmentRoleController::class, 'storeRole']);
        Route::put('/roles/{id}', [DepartmentRoleController::class, 'updateRole']);
        Route::delete('/roles/{id}', [DepartmentRoleController::class, 'deleteRole']);
    });
});
