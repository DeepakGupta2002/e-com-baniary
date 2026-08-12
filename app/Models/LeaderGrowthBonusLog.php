<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LeaderGrowthBonusLog extends Model
{
    protected $fillable = [
        'user_id',
        'cycle_number',
        'cycle_start',
        'cycle_end',
        'required_business',
        'achieved_business',
        'bonus_amount',
        'matching_transaction_id',
        'wallet_transaction_id',
        'status',
    ];

    protected $casts = [
        'cycle_start' => 'datetime',
        'cycle_end' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function matchingTransaction()
    {
        return $this->belongsTo(Transaction::class, 'matching_transaction_id');
    }

    public function walletTransaction()
    {
        return $this->belongsTo(Transaction::class, 'wallet_transaction_id');
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return match ($this->status) {
                'paid' => '<span class="badge badge--success">' . __('Paid') . '</span>',
                'pending' => '<span class="badge badge--warning">' . __('Pending Payout') . '</span>',
                'processed' => '<span class="badge badge--info">' . __('Processed') . '</span>',
                default => '<span class="badge badge--dark">' . __(keyToTitle($this->status)) . '</span>',
            };
        });
    }
}
