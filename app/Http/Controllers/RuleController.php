<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Rule;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RuleController extends Controller
{
    public function index(): Response
    {
        $rules = Rule::orderBy('priority')->get();
        $categories = Category::orderBy('name')->get(['id', 'name', 'type', 'icon', 'color']);
        $accounts = Account::orderBy('name')->get(['id', 'name']);

        // Transactions this month to compute matches
        $thisMonthTransactions = Transaction::with(['category', 'account'])
            ->whereMonth('transacted_at', now()->month)
            ->whereYear('transacted_at', now()->year)
            ->get();

        $recentTransactions = Transaction::with(['category', 'account'])
            ->latest('transacted_at')
            ->take(20)
            ->get(['id', 'transacted_at', 'payee', 'note', 'amount', 'type', 'category_id', 'account_id']);

        $rulesWithStats = $rules->map(function ($rule) use ($thisMonthTransactions) {
            $matchCount = $thisMonthTransactions->filter(fn ($tx) => $this->transactionMatchesRule($tx, $rule))->count();
            return array_merge($rule->toArray(), [
                'match_count' => $matchCount,
            ]);
        });

        // Compute overall automation efficiency
        $totalThisMonth = $thisMonthTransactions->count();
        $automatedCount = $thisMonthTransactions->filter(function ($tx) use ($rules) {
            foreach ($rules->where('is_active', true) as $rule) {
                if ($this->transactionMatchesRule($tx, $rule)) return true;
            }
            return false;
        })->count();

        $efficiencyPercent = $totalThisMonth > 0 ? round(($automatedCount / $totalThisMonth) * 100, 1) : 0;

        return Inertia::render('Rules/Index', [
            'rules' => $rulesWithStats,
            'categories' => $categories,
            'accounts' => $accounts,
            'recentTransactions' => $recentTransactions,
            'stats' => [
                'total_rules' => $rules->count(),
                'active_rules' => $rules->where('is_active', true)->count(),
                'total_transactions_month' => $totalThisMonth,
                'automated_transactions' => $automatedCount,
                'efficiency_percent' => $efficiencyPercent,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'priority' => 'required|integer|min:0',
            'stop_processing' => 'boolean',
            'is_active' => 'boolean',
            'conditions' => 'required|array',
            'conditions.match' => 'nullable|in:all,any',
            'conditions.conditions' => 'required|array|min:1',
            'actions' => 'required|array|min:1',
        ]);

        if (!isset($data['conditions']['match'])) {
            $data['conditions']['match'] = 'all';
        }

        Rule::create($data);

        return back()->with('success', 'Aturan berhasil dibuat.');
    }

    public function update(Request $request, Rule $rule)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'priority' => 'required|integer|min:0',
            'stop_processing' => 'boolean',
            'is_active' => 'boolean',
            'conditions' => 'required|array',
            'actions' => 'required|array|min:1',
        ]);

        $rule->update($data);

        return back()->with('success', 'Aturan berhasil diperbarui.');
    }

    public function destroy(Rule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Aturan berhasil dihapus.');
    }

    public function apply()
    {
        $activeRules = Rule::where('is_active', true)->orderBy('priority')->get();
        $transactions = Transaction::latest('transacted_at')->take(500)->get();

        $updatedCount = 0;

        foreach ($transactions as $tx) {
            foreach ($activeRules as $rule) {
                if ($this->transactionMatchesRule($tx, $rule)) {
                    $actions = $rule->actions ?? [];
                    $dirty = false;
                    foreach ($actions as $action) {
                        if (($action['type'] ?? '') === 'set_category' && !empty($action['value'])) {
                            $tx->category_id = $action['value'];
                            $dirty = true;
                        }
                    }
                    if ($dirty) {
                        $tx->save();
                        $updatedCount++;
                    }
                    if ($rule->stop_processing) {
                        break;
                    }
                }
            }
        }

        return back()->with('success', "Berhasil menerapkan aturan ke {$updatedCount} transaksi.");
    }

    private function transactionMatchesRule($tx, $rule): bool
    {
        $conds = $rule->conditions['conditions'] ?? [];
        if (empty($conds)) return false;

        $matchType = $rule->conditions['match'] ?? 'all';
        $results = [];

        foreach ($conds as $c) {
            $field = $c['field'] ?? 'payee';
            $op = $c['op'] ?? ($c['operator'] ?? 'contains');
            $val = strtolower((string) ($c['value'] ?? ''));

            $targetVal = '';
            if ($field === 'payee' || $field === 'description' || $field === 'merchant') {
                $targetVal = strtolower(($tx->payee ?? '') . ' ' . ($tx->note ?? ''));
            } elseif ($field === 'amount') {
                $txAmt = (float) $tx->amount;
                $valAmt = (float) $val;
                if ($op === 'lt' || $op === '<') $results[] = $txAmt < $valAmt;
                elseif ($op === 'gt' || $op === '>') $results[] = $txAmt > $valAmt;
                elseif ($op === 'lte' || $op === '<=') $results[] = $txAmt <= $valAmt;
                elseif ($op === 'gte' || $op === '>=') $results[] = $txAmt >= $valAmt;
                else $results[] = $txAmt == $valAmt;
                continue;
            } elseif ($field === 'account_id') {
                $results[] = (string) $tx->account_id === (string) $val;
                continue;
            } elseif ($field === 'type') {
                $results[] = (string) $tx->type === (string) $val;
                continue;
            }

            if ($op === 'contains') {
                $results[] = str_contains($targetVal, $val);
            } elseif ($op === 'equals') {
                $results[] = trim($targetVal) === trim($val);
            } elseif ($op === 'starts_with') {
                $results[] = str_starts_with(trim($targetVal), $val);
            } elseif ($op === 'not_contains') {
                $results[] = !str_contains($targetVal, $val);
            } else {
                $results[] = str_contains($targetVal, $val);
            }
        }

        if ($matchType === 'all') {
            return !in_array(false, $results, true);
        } else {
            return in_array(true, $results, true);
        }
    }
}
