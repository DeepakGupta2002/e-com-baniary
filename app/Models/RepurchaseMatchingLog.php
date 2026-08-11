<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class RepurchaseMatchingLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'order_id',
        'left_bv',
        'right_bv',
        'matched_bv',
        'percentage',
        'income',
        'carry_left',
        'carry_right',
        'transaction_id',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return $this->status === 'paid'
                ? '<span class="badge badge--success">' . trans('Paid') . '</span>'
                : '<span class="badge badge--warning">' . trans(ucfirst($this->status)) . '</span>';
        });
    }
}
