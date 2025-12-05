<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('company_id', 64);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedInteger('role_id')->nullable();
            $table->string('first_name', 150);
            $table->string('last_name', 150);
            $table->date('dateIntegration');
            $table->string('email', 191)->unique();
            $table->decimal('salary_amount', 13, 2)->default(0.00);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
