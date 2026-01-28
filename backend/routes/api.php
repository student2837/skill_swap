<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\DepositController;




Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::get('/user', [UserController::class, 'getUser']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);
    Route::delete('/user/account', [UserController::class, 'deleteOwnAccount']);
});

// Webhooks (public)
Route::post('/webhooks/paypal', [WebhookController::class, 'paypal']);
Route::post('/webhooks/whish', [WebhookController::class, 'whish']);


Route::middleware('auth:sanctum')->group(function () {
    // Student actions
    Route::post('/skills/{skillId}/request', [RequestController::class, 'createRequest']);
    Route::post('/requests/{id}/cancel', [RequestController::class, 'cancelRequest']);

    // Teacher actions
    Route::put('/requests/{id}/accept', [RequestController::class, 'acceptRequest']);
    Route::put('/requests/{id}/reject', [RequestController::class, 'rejectRequest']);
    Route::put('/requests/{id}/complete', [RequestController::class, 'completeRequest']);

    // Get requests
    Route::get('/requests/teaching', [RequestController::class, 'getTeachingRequests']);
    Route::get('/requests/learning', [RequestController::class, 'getLearningRequests']);

    // Purge cancelled requests (used by Requests page refresh)
    Route::delete('/requests/purge-cancelled/teaching', [RequestController::class, 'purgeCancelledTeachingRequests']);
    Route::delete('/requests/purge-cancelled/learning', [RequestController::class, 'purgeCancelledLearningRequests']);

    // Extra: teacher/student skill views
    Route::get('/user/teaching-skills', [UserController::class, 'getTeachingSkills']);
    Route::get('/user/learning-skills', [UserController::class, 'getLearningSkills']);
});

// Deposits (buy credits)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/deposits/whish/collect', [DepositController::class, 'createWhishCollect']);
    Route::post('/deposits/paypal/order', [DepositController::class, 'createPayPalOrder']);
});


// Public endpoints for browsing skills
Route::get('/skills', [SkillController::class, 'listAllSkills']);
Route::get('/skills/search', [SkillController::class, 'searchSkill']); // Must be before /skills/{id} to avoid route conflict
Route::get('/skills/by-category', [SkillController::class, 'getSkillsByCategory']); // Must be before /skills/{id} to avoid route conflict
Route::get('/skills/{id}', [SkillController::class, 'getSkill']);
Route::get('/statistics', [SkillController::class, 'getStatistics']); // Public statistics endpoint

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/skills', [SkillController::class, 'createSkill']);
    Route::put('/skills/{id}', [SkillController::class, 'updateSkill']);
    Route::patch('/skills/{id}/status', [SkillController::class, 'changeStatus']);
    Route::delete('/skills/{id}', [SkillController::class, 'deleteSkill']);
    Route::post('/skills/{skillId}/categories', [SkillController::class, 'addCategoriesToSkill']);
    Route::get('/skills/{skillId}/categories', [SkillController::class, 'getSkillCategories']);
    Route::get('/skills/{skillId}/students', [SkillController::class, 'getSkillStudents']);
});

// Public endpoints for browsing categories
Route::get('/categories', [CategoryController::class, 'listCategories']);
Route::get('/categories/{categoryId}/skills', [CategoryController::class, 'getCategorySkills']);

// Admin-only category management
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'createCategory']);
    Route::delete('/categories/{id}', [CategoryController::class, 'deleteCategory']);
});



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/favorites/add', [FavoriteController::class, 'addFavorite']);
    Route::delete('/favorites/remove', [FavoriteController::class, 'removeFavorite']);
    Route::get('/favorites', [FavoriteController::class, 'listFavorites']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'createReview']);
    Route::put('/reviews/{id}', [ReviewController::class, 'updateReview']);
    Route::get('/reviews/user/{userId}', [ReviewController::class, 'getReviewsForUser']);
    Route::get('/reviews/user/{userId}/average', [ReviewController::class, 'getAverageRating']);
    Route::get('/reviews/reviewable', [ReviewController::class, 'getReviewableRequests']);
    Route::get('/reviews/skill-performance', [ReviewController::class, 'getSkillPerformance']);
});

// Public endpoint for skill reviews
Route::get('/reviews/skill/{skillId}', [ReviewController::class, 'getReviewsForSkill']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/transactions', [TransactionController::class, 'createTransaction']);
    Route::get('/transactions', [TransactionController::class, 'getUserTransactions']);
    Route::put('/transactions/{id}/status', [TransactionController::class, 'updateStatus']);
});


Route::middleware('auth:sanctum')->group(function () {
    // User payout actions
    Route::post('/payouts', [PayoutController::class, 'requestPayout']);
    Route::get('/payouts', [PayoutController::class, 'getUserPayouts']);

    // Admin-only payout management
    Route::middleware('admin')->group(function () {
        Route::get('/payouts/all', [PayoutController::class, 'getAllPayouts']);
    Route::post('/payouts/{id}/approve', [PayoutController::class, 'approvePayout']);
    Route::post('/payouts/{id}/reject', [PayoutController::class, 'rejectPayout']);
    Route::post('/payouts/{id}/paid', [PayoutController::class, 'markAsPaid']);
});
});

// Messages - Conversation-based
Route::middleware('auth:sanctum')->group(function () {
    // Conversation endpoints
    Route::get('/conversations', [\App\Http\Controllers\ConversationController::class, 'getUserConversations']);
    Route::get('/conversations/{conversationId}', [\App\Http\Controllers\ConversationController::class, 'getConversation']);
    Route::post('/conversations', [\App\Http\Controllers\ConversationController::class, 'createConversation']);
    
    // Message endpoints
    Route::get('/conversations/{conversationId}/messages', [\App\Http\Controllers\MessageController::class, 'getConversationMessages']);
    Route::post('/conversations/{conversationId}/messages', [\App\Http\Controllers\MessageController::class, 'sendMessage']);
    Route::put('/conversations/{conversationId}/read', [\App\Http\Controllers\MessageController::class, 'markAsRead']);
    
    // Backward compatibility - request-based messages
    Route::post('/messages/conversation/{requestId}', [\App\Http\Controllers\MessageController::class, 'sendMessageToRequest']);
});

// Admin-only endpoints
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::put('/users/{id}/verify', [UserController::class, 'setUserVerified']);
    Route::delete('/users/{id}', [UserController::class, 'deleteUser']);
    Route::get('/transactions', [TransactionController::class, 'getAllTransactions']);
    // Note: /payouts/all is already defined in the payouts routes above
});


