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
        'payment_method',
        'payment_reference',
        'payment_proof',
        'payment_status',
        'payment_verified_at',
        'payment_verified_by',
        'status',
        'note',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'payment_verified_at' => 'datetime',
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
                'pending' => '<span class="badge badge--warning">Payment Verification Pending</span>',
                'approved' => '<span class="badge badge--success">Approved</span>',
                'rejected' => '<span class="badge badge--danger">Rejected</span>',
                default => '<span class="badge badge--dark">' . ucfirst($this->status) . '</span>',
            };
        });
    }

    public function paymentStatusBadge(): Attribute
    {
        return new Attribute(function () {
            return match ($this->payment_status) {
                'verified' => '<span class="badge badge--success">Verified</span>',
                'rejected' => '<span class="badge badge--danger">Rejected</span>',
                default => '<span class="badge badge--warning">Pending Verification</span>',
            };
        });
    }
}
