<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Role;
use App\Models\Employee;
use App\Models\Task;
use App\Models\SalaryPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResourceController extends Controller
{
    private string $companyId = '01k7464hqksr4f99rp3ff7x2jz'; // ton ID fixe

    // --- COMPANIES ---
    public function company(): JsonResponse {
        $company = Company::firstOrCreate(
            ['id' => $this->companyId],
            ['name' => 'Restaurant Garnish']
        );
        return response()->json($company);
    }

    // --- DEPARTMENTS ---
    public function departments(): JsonResponse {
        return response()->json(Department::where('company_id', $this->companyId)->get());
    }

    public function storeDepartment(Request $request): JsonResponse {
        $data = $request->validate(['name' => 'required|string', 'description' => 'nullable|string']);
        $dept = Department::create(array_merge($data, ['company_id' => $this->companyId]));
        return response()->json($dept, 201);
    }

    // --- ROLES ---
    public function roles(): JsonResponse {
        return response()->json(Role::where('company_id', $this->companyId)->get());
    }

    public function storeRole(Request $request): JsonResponse {
        $data = $request->validate(['name' => 'required|string']);
        $role = Role::create(array_merge($data, ['company_id' => $this->companyId]));
        return response()->json($role, 201);
    }

    // --- EMPLOYEES ---
    public function employees(): JsonResponse {
        return response()->json(Employee::with('department', 'role')
            ->where('company_id', $this->companyId)->get());
    }

    public function storeEmployee(Request $request): JsonResponse {
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:employees',
            'dateIntegration' => 'required|date',
            'salary_amount' => 'required|numeric|min:0',
            'department_id' => 'nullable|exists:departments,id',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'nullable|in:active,inactive'
        ]);
        $emp = Employee::create(array_merge($data, ['company_id' => $this->companyId]));
        return response()->json($emp, 201);
    }

    // --- TASKS ---
    public function tasks(): JsonResponse {
        return response()->json(Task::with('assignedTo')
            ->where('company_id', $this->companyId)->get());
    }

    public function storeTask(Request $request): JsonResponse {
        $data = $request->validate([
            'assigned_to' => 'required|exists:employees,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'due_date' => 'required|date_format:H:i:s', // ou datetime si tu changes le schéma
            'status' => 'nullable|in:todo,in_progress,done,cancelled,in_review'
        ]);
        $task = Task::create(array_merge($data, ['company_id' => $this->companyId]));
        return response()->json($task, 201);
    }

    public function updateTaskStatus(string $id, Request $request): JsonResponse {
        $task = Task::where('id', $id)->where('company_id', $this->companyId)->firstOrFail();
        $task->update($request->validate(['status' => 'required|in:todo,in_progress,done,cancelled,in_review']));
        return response()->json($task);
    }

    // --- SALARY PAYMENTS ---
    public function paySalary(Request $request): JsonResponse {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0',
            'period_month' => 'required|date_format:Y-m'
        ]);

        $payment = SalaryPayment::create([
            'employee_id' => $data['employee_id'],
            'amount' => $data['amount'],
            'period_month' => $data['period_month'] . '-01', // normalize to first day
            'status' => 'paid'
        ]);

        return response()->json($payment, 201);
    }

    public function employeePayments(string $employeeId): JsonResponse {
        return response()->json(SalaryPayment::where('employee_id', $employeeId)->get());
    }
}
