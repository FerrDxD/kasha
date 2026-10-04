<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Transaction::with('account:id,name', 'category:id,name,icon,color', 'tags:id,name')
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at');

        if ($request->search) {
            $query->where(fn ($q) => $q->where('payee', 'like', "%{$request->search}%")
                ->orWhere('note', 'like', "%{$request->search}%"));
        }
        if ($request->account_id) {
            $query->where('account_id', $request->account_id);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->date_from) {
            $query->where('occurred_on', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('occurred_on', '<=', $request->date_to);
        }

        $transactions = $query->paginate(30)->withQueryString();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'accounts' => Account::where('is_archived', false)->orderBy('name')->get(['id', 'name', 'type']),
            'categories' => Category::where('is_archived', false)->orderBy('name')->get(['id', 'name', 'type']),
            'filters' => $request->only(['search', 'account_id', 'category_id', 'type', 'date_from', 'date_to']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|uuid',
            'category_id' => 'nullable|uuid',
            'type' => 'required|in:income,expense',
            'amount' => 'required|integer|min:1',
            'occurred_on' => 'required|date',
            'payee' => 'nullable|string|max:200',
            'note' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $tx = Transaction::create($data);

        if ($tags) {
            $tagIds = collect($tags)->map(fn ($name) =>
                \App\Models\Tag::firstOrCreate(['user_id' => auth()->id(), 'name' => $name])->id
            );
            $tx->tags()->sync($tagIds);
        }

        return back()->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'from_account_id' => 'required|uuid',
            'to_account_id' => 'required|uuid|different:from_account_id',
            'amount' => 'required|integer|min:1',
            'occurred_on' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ]);

        $groupId = (string) Str::uuid();

        Transaction::create([
            'account_id' => $data['from_account_id'],
            'type' => 'expense',
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'note' => $data['note'] ?? null,
            'transfer_group_id' => $groupId,
            'payee' => 'Transfer keluar',
        ]);

        Transaction::create([
            'account_id' => $data['to_account_id'],
            'type' => 'income',
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'note' => $data['note'] ?? null,
            'transfer_group_id' => $groupId,
            'payee' => 'Transfer masuk',
        ]);

        return back()->with('success', 'Transfer berhasil dicatat.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'account_id' => 'required|uuid',
            'category_id' => 'nullable|uuid',
            'type' => 'required|in:income,expense',
            'amount' => 'required|integer|min:1',
            'occurred_on' => 'required|date',
            'payee' => 'nullable|string|max:200',
            'note' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $tags = $data['tags'] ?? [];
        unset($data['tags']);
        $transaction->update($data);

        if ($tags !== null) {
            $tagIds = collect($tags)->map(fn ($name) =>
                \App\Models\Tag::firstOrCreate(['user_id' => auth()->id(), 'name' => $name])->id
            );
            $transaction->tags()->sync($tagIds);
        }

        return back()->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction)
    {
        // If transfer, delete both legs
        if ($transaction->transfer_group_id) {
            Transaction::where('transfer_group_id', $transaction->transfer_group_id)->delete();
        } else {
            $transaction->delete();
        }

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }
}
