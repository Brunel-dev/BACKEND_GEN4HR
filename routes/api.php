<?php

use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\CheckController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RefreshController;
use App\Http\Controllers\Auth\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


 use App\Http\Controllers\Api\TaskController;

Route::prefix('tasks')->group(function () {
    Route::get('/', [TaskController::class, 'index']);
    Route::post('/', [TaskController::class, 'store']);
    Route::post('/{taskId}/assign', [TaskController::class, 'assign']);
    Route::patch('/assignment/{assignmentId}/status', [TaskController::class, 'updateStatus']);
});

use App\Http\Controllers\Api\ResourceController;

Route::get('/company', [ResourceController::class, 'company']);

// Departments
Route::get('/departments', [ResourceController::class, 'departments']);
Route::post('/departments', [ResourceController::class, 'storeDepartment']);

// Roles
Route::get('/roles', [ResourceController::class, 'roles']);
Route::post('/roles', [ResourceController::class, 'storeRole']);

// Employees
Route::get('/employees', [ResourceController::class, 'employees']);
Route::post('/employees', [ResourceController::class, 'storeEmployee']);

// Tasks
Route::get('/tasks', [ResourceController::class, 'tasks']);
Route::post('/tasks', [ResourceController::class, 'storeTask']);
Route::patch('/tasks/{id}/status', [ResourceController::class, 'updateTaskStatus']);

// Salary Payments
Route::post('/salaries/pay', [ResourceController::class, 'paySalary']);
Route::get('/employees/{employeeId}/payments', [ResourceController::class, 'employeePayments']);
