<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $now = Carbon::now();
        $periodStart = $now->copy()->startOfMonth();
        $periodEnd = $now->copy()->endOfMonth();
        $period = $now->format('Y-m');

        // Accounts with balances
        $accounts = Account::where('is_archived', false)->get()->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'type' => $a->type,
            'color' => $a->color,
            'icon' => $a->icon,
            'balance' => $a->balance,
            'include_in_total' => $a->include_in_total,
        ]);

        $totalBalance = $accounts->where('include_in_total', true)->sum('balance');

        // Income vs expense this month
        $monthIncome = Transaction::where('type', 'income')
            ->whereBetween('occurred_on', [$periodStart, $periodEnd])
            ->sum('amount');
        $monthExpense = Transaction::where('type', 'expense')
            ->whereBetween('occurred_on', [$periodStart, $periodEnd])
            ->sum('amount');

        // Budget progress
        $budgets = Budget::where('period', $period)
            ->with('category:id,name,icon,color')
            ->get()
            ->map(function ($b) use ($periodStart, $periodEnd) {
                $spent = Transaction::where('category_id', $b->category_id)
                    ->where('type', 'expense')
                    ->whereBetween('occurred_on', [$periodStart, $periodEnd])
                    ->sum('amount');
                $pct = $b->amount > 0 ? min(100, round($spent / $b->amount * 100)) : 0;
                return [
                    'id' => $b->id,
                    'category' => $b->category,
                    'amount' => $b->amount,
                    'spent' => $spent,
                    'percent' => $pct,
                ];
            });

        // Upcoming recurring (next 7 days)
        $upcoming = RecurringTransaction::where('is_active', true)
            ->whereBetween('next_run_on', [$now->toDateString(), $now->copy()->addDays(7)->toDateString()])
            ->with('account:id,name', 'category:id,name')
            ->orderBy('next_run_on')
            ->get();

        // Goals
        $goals = Goal::where('status', 'active')->get()->map(fn ($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'target_amount' => $g->target_amount,
            'target_date' => $g->target_date?->toDateString(),
            'contributed_amount' => $g->contributed_amount,
            'progress_percent' => $g->progress_percent,
        ]);

        // Recent transactions
        $recent = Transaction::with('account:id,name', 'category:id,name,icon,color')
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Previous month net for comparison
        $prevStart = $periodStart->copy()->subMonth()->startOfMonth();
        $prevEnd = $periodStart->copy()->subMonth()->endOfMonth();
        $prevIncome = Transaction::where('type', 'income')->whereBetween('occurred_on', [$prevStart, $prevEnd])->sum('amount');
        $prevExpense = Transaction::where('type', 'expense')->whereBetween('occurred_on', [$prevStart, $prevEnd])->sum('amount');

        return Inertia::render('Dashboard', [
            'total_balance' => $totalBalance,
            'accounts' => $accounts,
            'month_income' => $monthIncome,
            'month_expense' => $monthExpense,
            'month_net' => $monthIncome - $monthExpense,
            'prev_net' => $prevIncome - $prevExpense,
            'budgets' => $budgets,
            'upcoming' => $upcoming,
            'goals' => $goals,
            'recent_transactions' => $recent,
            'period' => $period,
        ]);
    }
}
