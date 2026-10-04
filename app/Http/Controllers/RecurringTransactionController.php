<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecurringTransactionController extends Controller
{
    public function index(): Response
    {
        $recurring = RecurringTransaction::with('account:id,name', 'category:id,name')
            ->orderBy('next_run_on')
            ->get()
            ->map(fn ($r) => array_merge($r->toArray(), [
                'next_dates' => $r->nextDates(3),
            ]));

        return Inertia::render('Recurring/Index', [
            'recurring' => $recurring,
            'accounts' => Account::where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::where('is_archived', false)->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|uuid',
            'category_id' => 'nullable|uuid',
            'type' => 'required|in:income,expense',
            'amount' => 'required|integer|min:1',
            'payee' => 'nullable|string|max:200',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval' => 'required|integer|min:1|max:31',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'starts_on' => 'required|date',
            'ends_on' => 'nullable|date|after:starts_on',
            'next_run_on' => 'required|date',
            'mode' => 'required|in:auto,reminder',
        ]);

        RecurringTransaction::create($data);

        return back()->with('success', 'Transaksi berulang berhasil ditambahkan.');
    }

    public function update(Request $request, RecurringTransaction $recurring)
    {
        $data = $request->validate([
            'account_id' => 'required|uuid',
            'category_id' => 'nullable|uuid',
            'type' => 'required|in:income,expense',
            'amount' => 'required|integer|min:1',
            'payee' => 'nullable|string|max:200',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval' => 'required|integer|min:1|max:31',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'starts_on' => 'required|date',
            'ends_on' => 'nullable|date|after:starts_on',
            'next_run_on' => 'required|date',
            'mode' => 'required|in:auto,reminder',
            'is_active' => 'boolean',
        ]);

        $recurring->update($data);

        return back()->with('success', 'Transaksi berulang berhasil diperbarui.');
    }

    public function destroy(RecurringTransaction $recurring)
    {
        $recurring->delete();

        return back()->with('success', 'Transaksi berulang berhasil dihapus.');
    }
}
