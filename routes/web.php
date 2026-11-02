<?php

use App\Http\Controllers\IncidentCommentController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncidentStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/incidents');

Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');
Route::patch('/incidents/{incident}/status', [IncidentStatusController::class, 'update'])->name('incidents.status.update');
Route::post('/incidents/{incident}/comments', [IncidentCommentController::class, 'store'])->name('incidents.comments.store');
