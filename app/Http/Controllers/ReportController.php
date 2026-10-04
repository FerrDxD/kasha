<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        $current = Carbon::create($year, $month, 1);
        $previous = $current->copy()->subMonth();

        // Current month transactions
        $txCurrent = Transaction::with('category')
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month)
            ->get();

        // Previous month transactions
        $txPrev = Transaction::with('category')
            ->whereYear('transacted_at', $previous->year)
            ->whereMonth('transacted_at', $previous->month)
            ->get();

        $income     = $txCurrent->where('type', 'income')->sum('amount');
        $expense    = $txCurrent->where('type', 'expense')->sum('amount');
        $netFlow    = $income - $expense;
        $savingRate = $income > 0 ? round(($netFlow / $income) * 100, 1) : 0;

        $prevIncome  = $txPrev->where('type', 'income')->sum('amount');
        $prevExpense = $txPrev->where('type', 'expense')->sum('amount');
        $prevNet     = $prevIncome - $prevExpense;

        // Category breakdown for donut chart (expense)
        $categoryBreakdown = $txCurrent
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($items) use ($expense) {
                $cat   = $items->first()->category;
                $total = $items->sum('amount');
                return [
                    'category_id'   => $items->first()->category_id,
                    'category_name' => $cat?->name ?? 'Lain-lain',
                    'color'         => $cat?->color ?? '#94a3b8',
                    'total'         => $total,
                    'percent'       => $expense > 0 ? round(($total / $expense) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->take(6);

        // Top spending categories with budget comparison
        $budgets = Budget::where('year', $year)->where('month', $month)->get()->keyBy('category_id');

        $topCategories = $txCurrent
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($items, $catId) use ($budgets, $txPrev) {
                $cat      = $items->first()->category;
                $total    = $items->sum('amount');
                $budget   = $budgets->get($catId);
                $limit    = $budget?->limit ?? 0;
                $percent  = $limit > 0 ? round(($total / $limit) * 100, 1) : null;

                $prevTotal = $txPrev->where('type', 'expense')->where('category_id', $catId)->sum('amount');
                $diff      = $total - $prevTotal;

                return [
                    'category_id'   => $catId,
                    'category_name' => $cat?->name ?? 'Lain-lain',
                    'color'         => $cat?->color ?? '#94a3b8',
                    'icon'          => $cat?->icon ?? 'receipt',
                    'total'         => $total,
                    'limit'         => $limit,
                    'percent'       => $percent,
                    'vs_prev'       => $diff,
                    'status'        => $limit > 0 && $total > $limit ? 'over' : ($limit > 0 && $total >= $limit * 0.8 ? 'warning' : 'ok'),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->take(5);

        return Inertia::render('Reports/Index', [
            'year'              => $year,
            'month'             => $month,
            'kpi' => [
                'income'       => $income,
                'expense'      => $expense,
                'net_flow'     => $netFlow,
                'saving_rate'  => $savingRate,
                'prev_income'  => $prevIncome,
                'prev_expense' => $prevExpense,
                'prev_net'     => $prevNet,
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'topCategories'     => $topCategories,
        ]);
    }
}
