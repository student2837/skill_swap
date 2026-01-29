<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuizController;

/*
|--------------------------------------------------------------------------
| Quiz Routes
|--------------------------------------------------------------------------
|
| Routes for the AI-powered quiz generation and grading system
|
*/

// Course setup form
Route::get('/quiz/setup', [QuizController::class, 'create'])->name('quiz.create');

// Generate exam
Route::post('/quiz/generate', [QuizController::class, 'generateExam'])->name('quiz.generate');

// Display exam
Route::get('/quiz', [QuizController::class, 'show'])->name('quiz.show');

// Grade exam
Route::post('/quiz/grade', [QuizController::class, 'gradeExam'])->name('quiz.grade');

// View results
Route::get('/quiz/results', [QuizController::class, 'results'])->name('quiz.results');
