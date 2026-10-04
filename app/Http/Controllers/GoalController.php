<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\GoalContribution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function index(): Response
    {
        $accounts = \App\Models\Account::orderBy('name')->get(['id', 'name', 'type', 'balance']);

        $goalsData = Goal::with(['linkedAccount', 'contributions' => fn ($q) => $q->latest('contributed_on')])
            ->get();

        $today = now();

        $goals = $goalsData->map(function ($g) use ($today) {
            $contributed = $g->contributions->sum('amount');
            $target = $g->target_amount;
            $percent = $target > 0 ? min(100, round(($contributed / $target) * 100, 1)) : 0;
            $remaining = max(0, $target - $contributed);

            // Months remaining
            $monthsRemaining = null;
            $monthlyRecommendation = null;
            if ($g->target_date) {
                $targetDate = \Carbon\Carbon::parse($g->target_date);
                $diffMonths = max(1, $today->diffInMonths($targetDate, false));
                $monthsRemaining = $diffMonths > 0 ? $diffMonths : 0;
                $monthlyRecommendation = $monthsRemaining > 0 ? (int) ceil($remaining / $monthsRemaining) : $remaining;
            }

            // Determine status indicator badge
            $healthStatus = 'On Track';
            if ($percent >= 100) {
                $healthStatus = 'Tercapai';
            } elseif ($percent >= 80) {
                $healthStatus = 'Ahead of Schedule';
            } elseif ($monthsRemaining !== null && $monthsRemaining <= 2 && $percent < 50) {
                $healthStatus = 'Perlu Tambahan';
            }

            return [
                'id' => $g->id,
                'name' => $g->name,
                'target_amount' => $target,
                'target_date' => $g->target_date?->toDateString(),
                'linked_account_id' => $g->linked_account_id,
                'linked_account' => $g->linkedAccount ? [
                    'id' => $g->linkedAccount->id,
                    'name' => $g->linkedAccount->name,
                ] : null,
                'status' => $g->status,
                'contributed_amount' => $contributed,
                'progress_percent' => $percent,
                'remaining_amount' => $remaining,
                'months_remaining' => $monthsRemaining,
                'monthly_recommendation' => $monthlyRecommendation,
                'health_status' => $healthStatus,
                'contributions' => $g->contributions->take(5)->map(fn ($c) => [
                    'id' => $c->id,
                    'amount' => $c->amount,
                    'contributed_on' => $c->contributed_on?->toDateString() ?? $c->created_at?->toDateString(),
                ]),
            ];
        });

        $activeGoals = $goals->where('status', 'active');
        $totalContributed = $activeGoals->sum('contributed_amount');
        $totalTarget = $activeGoals->sum('target_amount');
        $totalMonthlyAllocation = $activeGoals->sum('monthly_recommendation');

        $kpi = [
            'total_contributed' => $totalContributed,
            'total_target' => $totalTarget,
            'overall_percent' => $totalTarget > 0 ? min(100, round(($totalContributed / $totalTarget) * 100, 1)) : 0,
            'remaining_commitment' => max(0, $totalTarget - $totalContributed),
            'active_count' => $activeGoals->count(),
            'nearing_count' => $activeGoals->filter(fn ($g) => $g['progress_percent'] >= 75 && $g['progress_percent'] < 100)->count(),
            'monthly_allocation' => $totalMonthlyAllocation,
        ];

        return Inertia::render('Goals/Index', [
            'goals' => $goals->values(),
            'accounts' => $accounts,
            'kpi' => $kpi,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'target_amount' => 'required|integer|min:1',
            'target_date' => 'nullable|date|after:today',
            'linked_account_id' => 'nullable|uuid|exists:accounts,id',
        ]);

        Goal::create($data);

        return back()->with('success', 'Goal berhasil ditambahkan.');
    }

    public function update(Request $request, Goal $goal)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'target_amount' => 'required|integer|min:1',
            'target_date' => 'nullable|date',
            'linked_account_id' => 'nullable|uuid|exists:accounts,id',
            'status' => 'in:active,completed,paused',
        ]);

        $goal->update($data);

        return back()->with('success', 'Goal berhasil diperbarui.');
    }

    public function destroy(Goal $goal)
    {
        $goal->delete();

        return back()->with('success', 'Goal berhasil dihapus.');
    }

    public function contribute(Request $request, Goal $goal)
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'contributed_on' => 'required|date',
        ]);

        $goal->contributions()->create($data);

        return back()->with('success', 'Kontribusi berhasil dicatat.');
    }
}
