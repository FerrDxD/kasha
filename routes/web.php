<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('accounts', AccountController::class)->except(['show', 'create', 'edit']);
    Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);

    // Transfer must be registered before resource to avoid route conflict
    Route::post('transactions/transfer', [TransactionController::class, 'transfer'])->name('transactions.transfer');
    Route::resource('transactions', TransactionController::class)->except(['show', 'create', 'edit']);

    Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::put('budgets', [BudgetController::class, 'upsert'])->name('budgets.upsert');

    Route::resource('recurring', RecurringTransactionController::class)->except(['show', 'create', 'edit']);
    Route::post('rules/apply', [RuleController::class, 'apply'])->name('rules.apply');
    Route::resource('rules', RuleController::class)->except(['show', 'create', 'edit']);

    Route::post('goals/{goal}/contribute', [GoalController::class, 'contribute'])->name('goals.contribute');
    Route::resource('goals', GoalController::class)->except(['show', 'create', 'edit']);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('cash-flow', [CashFlowController::class, 'index'])->name('cash-flow.index');
});

require __DIR__.'/settings.php';
