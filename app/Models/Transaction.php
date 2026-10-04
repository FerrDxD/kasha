<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Transaction extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id', 'account_id', 'category_id', 'type', 'amount',
        'occurred_on', 'payee', 'note', 'transfer_group_id',
        'recurring_id', 'import_batch_id', 'external_hash', 'is_reconciled',
    ];

    protected $casts = [
        'amount' => 'integer',
        'occurred_on' => 'date',
        'is_reconciled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', fn (Builder $q) => $q->where('transactions.user_id', Auth::id()));
        static::creating(fn ($m) => $m->user_id ??= Auth::id());
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->withoutGlobalScopes();
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withoutGlobalScopes();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'transaction_tag')->withoutGlobalScopes();
    }
}
