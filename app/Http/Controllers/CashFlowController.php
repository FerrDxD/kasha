<?php

namespace App\Http\Controllers;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CashFlowController extends Controller
{
    public function index(Request $request): Response
    {
        $range = (int) str_replace('d', '', $request->get('range', '90d'));
        $range = in_array($range, [30, 60, 90]) ? $range : 90;

        $start = now()->subDays($range)->startOfDay();
        $end = now()->endOfDay();

        // Historical: group by date
        $historical = Transaction::whereIn('type', ['income', 'expense'])
            ->whereBetween('occurred_on', [$start, $end])
            ->selectRaw('occurred_on, type, SUM(amount) as total')
            ->groupBy('occurred_on', 'type')
            ->orderBy('occurred_on')
            ->get()
            ->groupBy('occurred_on');

        // Projected: upcoming recurring for next 30 days
        $projStart = now()->toDateString();
        $projEnd = now()->addDays(30)->toDateString();
        $projected = RecurringTransaction::where('is_active', true)
            ->where('next_run_on', '<=', $projEnd)
            ->get()
            ->flatMap(fn ($r) => collect($r->nextDates(5))
                ->filter(fn ($d) => $d >= $projStart && $d <= $projEnd)
                ->map(fn ($d) => ['date' => $d, 'type' => $r->type, 'amount' => $r->amount, 'payee' => $r->payee])
            );

        return Inertia::render('CashFlow/Index', [
            'historical' => $historical,
            'projected' => $projected->values(),
            'range' => $range,
        ]);
    }
}
