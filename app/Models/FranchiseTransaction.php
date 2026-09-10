<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranchiseTransaction extends Model
{
    protected $fillable = [
        'franchise_profile_id',
        'related_user_id',
        'trx',
        'remark',
        'trx_type',
        'amount',
        'charge',
        'post_balance',
        'details',
    ];

    public function profile()
    {
        return $this->belongsTo(FranchiseProfile::class, 'franchise_profile_id');
    }

    public function relatedUser()
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }
}
