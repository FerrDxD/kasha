<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RecurringTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'account_id', 'category_id', 'type', 'amount', 'payee',
        'frequency', 'interval', 'day_of_month', 'starts_on', 'ends_on',
        'next_run_on', 'mode', 'is_active',
    ];

    protected $casts = [
        'amount' => 'integer',
        'interval' => 'integer',
        'day_of_month' => 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'next_run_on' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', fn (Builder $q) => $q->where('recurring_transactions.user_id', Auth::id()));
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

    /** Returns the next N due dates for UI preview */
    public function nextDates(int $count = 3): array
    {
        $dates = [];
        $next = $this->next_run_on->copy();
        for ($i = 0; $i < $count; $i++) {
            $dates[] = $next->toDateString();
            $next = match ($this->frequency) {
                'daily'   => $next->addDays($this->interval),
                'weekly'  => $next->addWeeks($this->interval),
                'monthly' => $next->addMonthsNoOverflow($this->interval),
                'yearly'  => $next->addYears($this->interval),
            };
        }
        return $dates;
    }
}
