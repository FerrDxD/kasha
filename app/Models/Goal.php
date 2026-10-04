<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Goal extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'name', 'target_amount', 'target_date', 'linked_account_id', 'status'];

    protected $casts = [
        'target_amount' => 'integer',
        'target_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', fn (Builder $q) => $q->where('goals.user_id', Auth::id()));
        static::creating(fn ($m) => $m->user_id ??= Auth::id());
    }

    public function contributions()
    {
        return $this->hasMany(GoalContribution::class);
    }

    public function linkedAccount()
    {
        return $this->belongsTo(Account::class, 'linked_account_id')->withoutGlobalScopes();
    }

    public function getContributedAmountAttribute(): int
    {
        return (int) $this->contributions()->sum('amount');
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->target_amount <= 0) return 0;
        return (int) min(100, round($this->contributed_amount / $this->target_amount * 100));
    }
}
