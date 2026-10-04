<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $request->get('period', now()->format('Y-m'));
        [$year, $month] = explode('-', $period);
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $categories = Category::where('type', 'expense')
            ->where('is_archived', false)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        $budgets = Budget::where('period', $period)
            ->with('category:id,name')
            ->get()
            ->keyBy('category_id');

        $result = $categories->map(function ($cat) use ($budgets, $start, $end, $period) {
            $budget = $budgets[$cat->id] ?? null;
            // Include child category spending
            $catIds = $cat->children->pluck('id')->push($cat->id);
            $spent = Transaction::where('type', 'expense')
                ->whereIn('category_id', $catIds)
                ->whereBetween('occurred_on', [$start, $end])
                ->sum('amount');
            $amount = $budget?->amount ?? 0;
            return [
                'category_id' => $cat->id,
                'category_name' => $cat->name,
                'period' => $period,
                'amount' => $amount,
                'spent' => $spent,
                'percent' => $amount > 0 ? min(100, round($spent / $amount * 100)) : 0,
                'rollover' => $budget?->rollover ?? false,
            ];
        });

        return Inertia::render('Budgets/Index', [
            'budgets' => $result,
            'period' => $period,
        ]);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'budgets' => 'required|array',
            'budgets.*.category_id' => 'required|uuid',
            'budgets.*.period' => 'required|date_format:Y-m',
            'budgets.*.amount' => 'required|integer|min:0',
            'budgets.*.rollover' => 'boolean',
        ]);

        foreach ($data['budgets'] as $b) {
            Budget::updateOrCreate(
                ['user_id' => auth()->id(), 'category_id' => $b['category_id'], 'period' => $b['period']],
                ['amount' => $b['amount'], 'rollover' => $b['rollover'] ?? false]
            );
        }

        return back()->with('success', 'Budget berhasil disimpan.');
    }
}
