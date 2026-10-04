<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['user_id', 'parent_id', 'name', 'type', 'icon', 'color', 'is_archived'];

    protected $casts = ['is_archived' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('user', fn (Builder $q) => $q->where('categories.user_id', Auth::id()));
        static::creating(fn ($m) => $m->user_id ??= Auth::id());
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
