<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FranchiseProfile extends Model
{
    protected $fillable = [
        'user_id',
        'sponsor_user_id',
        'franchise_plan_id',
        'franchise_code',
        'wallet_balance',
        'total_commission',
        'total_transfer_sent',
        'total_transfer_received',
        'total_direct_referrals',
        'application_amount',
        'activated_at',
        'status',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
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

    public function transactions()
    {
        return $this->hasMany(FranchiseTransaction::class)->latest('id');
    }

    public function invoices()
    {
        return $this->hasMany(FranchiseInvoice::class)->latest('id');
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return match ($this->status) {
                'active' => '<span class="badge badge--success">Active</span>',
                'inactive' => '<span class="badge badge--warning">Inactive</span>',
                default => '<span class="badge badge--dark">' . ucfirst($this->status) . '</span>',
            };
        });
    }
}
