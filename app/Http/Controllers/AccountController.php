<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(): Response
    {
        $accounts = Account::orderBy('name')->get()->map(fn ($a) => array_merge($a->toArray(), [
            'balance' => $a->balance,
        ]));

        return Inertia::render('Accounts/Index', ['accounts' => $accounts]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:cash,bank,ewallet,credit,savings',
            'opening_balance' => 'required|integer',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'include_in_total' => 'boolean',
        ]);

        Account::create($data);

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }

    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:cash,bank,ewallet,credit,savings',
            'opening_balance' => 'required|integer',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'is_archived' => 'boolean',
            'include_in_total' => 'boolean',
        ]);

        $account->update($data);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account)
    {
        $account->delete();

        return back()->with('success', 'Akun berhasil dihapus.');
    }
}
