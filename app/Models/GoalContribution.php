<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GoalContribution extends Model
{
    use HasUuids;

    protected $fillable = ['goal_id', 'transaction_id', 'amount', 'contributed_on'];

    protected $casts = ['amount' => 'integer', 'contributed_on' => 'date'];

    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }
}
