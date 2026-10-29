<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SmartStudyController;

Route::get('/', [SmartStudyController::class, 'dashboard'])->name('dashboard');
Route::get('/tasks', [SmartStudyController::class, 'tasks'])->name('tasks');
Route::post('/tasks', [SmartStudyController::class, 'store'])->name('tasks.store');
Route::post('/tasks/{task}/toggle', [SmartStudyController::class, 'toggle'])->name('tasks.toggle');
Route::delete('/tasks/{task}', [SmartStudyController::class, 'destroy'])->name('tasks.destroy');

Route::get('/study-plan', [SmartStudyController::class, 'studyPlan'])->name('study-plan');
Route::post('/study-plan/regenerate', [SmartStudyController::class, 'regenerateStudyPlan'])->name('study-plan.regenerate');

Route::get('/ai-generator', [SmartStudyController::class, 'aiGenerator'])->name('ai-generator');
Route::post('/ai-generator', [SmartStudyController::class, 'processAIGenerator'])->name('ai-generator.process');
Route::delete('/ai-generator/{id}', [SmartStudyController::class, 'destroyStudyMaterial'])->name('ai-generator.destroy');
Route::post('/ai-generator/{id}/refresh-quiz', [SmartStudyController::class, 'refreshQuiz'])->name('ai-generator.refresh-quiz');

Route::get('/settings', [SmartStudyController::class, 'settings'])->name('settings');
Route::post('/settings', [SmartStudyController::class, 'updateSettings'])->name('settings.update');

Route::post('/ai-chat', [SmartStudyController::class, 'chat'])->name('ai-chat');
