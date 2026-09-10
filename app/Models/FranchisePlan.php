<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranchisePlan extends Model
{
    protected $fillable = [
        'name',
        'amount',
        'direct_commission',
        'sort_order',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
