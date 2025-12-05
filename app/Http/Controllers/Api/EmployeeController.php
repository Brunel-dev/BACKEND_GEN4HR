<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
       private string $companyId = '01k7464hqksr4f99rp3ff7x2jz';

    public function topPaidEmployees(): JsonResponse
    {
        $topEmployees = Employee::where('company_id', $this->companyId)
            ->where('status', 'active') // optionnel : ignorer les inactifs
            ->orderBy('salary_amount', 'desc')
            ->limit(5)
            ->get([
                'id',
                'first_name',
                'last_name',
                'email',
                'salary_amount',
                'department_id',
                'role_id'
            ]);

        return response()->json($topEmployees);
    }
}
