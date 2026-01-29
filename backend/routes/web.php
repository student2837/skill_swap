<?php

use App\Http\Controllers\QuizController;
use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('index');
})->name('index');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/register', function () {
    return view('register');
})->name('register');

Route::get('/browse', function () {
    return view('browse');
})->name('browse');

// AI-powered quiz routes
Route::prefix('quiz')->group(function () {
    // Manual setup route (legacy - kept for backward compatibility)
    Route::get('/setup', [QuizController::class, 'create'])->name('quiz.setup');
    
    // Automatic quiz generation from completed request (must come before generic /generate)
    Route::get('/generate/request/{requestId}', [QuizController::class, 'generateFromRequest'])
        ->name('quiz.generate.from_request');
    
    // Student access to quiz for their completed request
    Route::get('/request/{requestId}', [QuizController::class, 'accessQuizForRequest'])
        ->name('quiz.access.request');

    // Start quiz flow (warning + loading)
    Route::get('/start/{requestId}', [QuizController::class, 'startQuizForRequest'])
        ->name('quiz.start');

    // Certificate view
    Route::get('/certificate/{certificateId}', [CertificateController::class, 'showPage'])
        ->name('quiz.certificate');
    
    Route::post('/generate', [QuizController::class, 'generateExam'])->name('quiz.generate');
    // Fallback GET route for /generate - redirects to setup
    Route::get('/generate', function () {
        return redirect()->route('quiz.setup')->with('status', 'Please use the form to generate an exam.');
    });
    
    Route::get('/exam', [QuizController::class, 'show'])->name('quiz.exam');
    Route::post('/grade', [QuizController::class, 'gradeExam'])->name('quiz.grade');
    // Fallback GET route for /grade - redirects to setup
    Route::get('/grade', function () {
        return redirect()->route('quiz.setup')->with('status', 'Please take the exam first.');
    });
    Route::get('/results', [QuizController::class, 'results'])->name('quiz.results');
});

// Protected routes (authentication handled client-side via API tokens)
// Note: These routes don't use auth:sanctum middleware because the frontend
// uses Bearer token authentication via API, not session-based auth
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/my-skills', function () {
    return view('my-skills');
})->name('my-skills');

Route::get('/add-skill', function () {
    return view('add-skill');
})->name('add-skill');

Route::get('/edit-skill', function () {
    return view('edit-skill');
})->name('edit-skill');

Route::get('/requests', function () {
    return view('requests');
})->name('requests');

Route::get('/messages', function () {
    return view('messages');
})->name('messages');

Route::get('/favorites', function () {
    return view('favorites');
})->name('favorites');

Route::get('/review', function () {
    return view('review');
})->name('review');

Route::get('/credits', function () {
    return view('credits');
})->name('credits');

Route::get('/credits/status', function () {
    return view('credits-status');
})->name('credits.status');

// Wallet / Cashout / Transactions
Route::get('/wallet', function () {
    return view('wallet');
})->name('wallet');

Route::get('/cashout', function () {
    return view('cashout');
})->name('cashout');

Route::get('/transactions', function () {
    return view('transactions');
})->name('transactions');

Route::get('/profile', function () {
    return view('profile');
})->name('profile');

Route::get('/skill-details', function () {
    return view('skill-details');
})->name('skill-details');

Route::get('/session-details', function () {
    return view('session-details');
})->name('session-details');

Route::get('/request-session', function () {
    return view('request-session');
})->name('request-session');

// Admin routes (authentication handled client-side)
Route::get('/admin/dashboard', function () {
    return view('admin-dashboard');
})->name('admin.dashboard');

Route::get('/admin/wallet', function () {
    return view('admin-wallet');
})->name('admin.wallet');

Route::get('/admin/payouts', function () {
    return view('admin-payouts');
})->name('admin.payouts');

Route::get('/admin/payouts/{id}', function ($id) {
    return view('admin-payout-details', ['id' => $id]);
})->name('admin.payouts.show');

Route::get('/admin/cashout', function () {
    return view('admin-cashout');
})->name('admin.cashout');
