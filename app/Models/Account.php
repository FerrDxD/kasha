<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['user_id', 'name', 'type', 'opening_balance', 'currency', 'color', 'icon', 'is_archived', 'include_in_total'];

    protected $casts = ['opening_balance' => 'integer', 'is_archived' => 'boolean', 'include_in_total' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('user', fn (Builder $q) => $q->where('accounts.user_id', Auth::id()));
        static::creating(fn ($m) => $m->user_id ??= Auth::id());
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /** Balance = opening + income - expense (transfer handled via paired legs) */
    public function getBalanceAttribute(): int
    {
        $income = $this->transactions()->where('type', 'income')->sum('amount');
        $expense = $this->transactions()->where('type', 'expense')->sum('amount');
        // ponytail: transfer balance via paired income/expense legs, no separate calc needed
        return (int) ($this->opening_balance + $income - $expense);
    }
}
