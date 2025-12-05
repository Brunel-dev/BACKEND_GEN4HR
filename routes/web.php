<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth.sanctum'])->get('/', function () {
    $company = request()->attributes->get('company_id');

    return view('welcome', ['company' => $company]);
});
