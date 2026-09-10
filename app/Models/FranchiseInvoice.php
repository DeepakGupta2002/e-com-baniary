<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FranchiseInvoice extends Model
{
    protected $fillable = [
        'franchise_profile_id',
        'invoice_no',
        'amount',
        'status',
        'title',
        'description',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function profile()
    {
        return $this->belongsTo(FranchiseProfile::class, 'franchise_profile_id');
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return match ($this->status) {
                'paid' => '<span class="badge badge--success">Paid</span>',
                'pending' => '<span class="badge badge--warning">Pending</span>',
                'cancelled' => '<span class="badge badge--danger">Cancelled</span>',
                default => '<span class="badge badge--dark">' . ucfirst($this->status) . '</span>',
            };
        });
    }
}
