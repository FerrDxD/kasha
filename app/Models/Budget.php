<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Budget extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'category_id', 'period', 'amount', 'rollover'];

    protected $casts = ['amount' => 'integer', 'rollover' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('user', fn (Builder $q) => $q->where('budgets.user_id', Auth::id()));
        static::creating(fn ($m) => $m->user_id ??= Auth::id());
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withoutGlobalScopes();
    }
}
