<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FranchiseApplication extends Model
{
    protected $fillable = [
        'user_id',
        'sponsor_user_id',
        'franchise_plan_id',
        'amount',
        'status',
        'note',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    public function plan()
    {
        return $this->belongsTo(FranchisePlan::class, 'franchise_plan_id');
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return match ($this->status) {
                'pending' => '<span class="badge badge--warning">Pending</span>',
                'approved' => '<span class="badge badge--success">Approved</span>',
                'rejected' => '<span class="badge badge--danger">Rejected</span>',
                default => '<span class="badge badge--dark">' . ucfirst($this->status) . '</span>',
            };
        });
    }
}
