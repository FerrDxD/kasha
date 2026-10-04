<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Rule extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'name', 'priority', 'stop_processing', 'is_active', 'conditions', 'actions'];

    protected $casts = [
        'priority' => 'integer',
        'stop_processing' => 'boolean',
        'is_active' => 'boolean',
        'conditions' => 'array',
        'actions' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', fn (Builder $q) => $q->where('rules.user_id', Auth::id()));
        static::creating(fn ($m) => $m->user_id ??= Auth::id());
    }
}
