<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Task Management API is running.',
        'docs' => 'See README.md for API documentation.',
    ]);
});
