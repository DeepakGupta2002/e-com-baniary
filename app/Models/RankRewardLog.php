<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class RankRewardLog extends Model
{
    protected $fillable = [
        'user_id',
        'rank_id',
        'team_dp',
        'reward_amount',
        'transaction_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return match ($this->status) {
                'paid' => '<span class="badge badge--success">Paid</span>',
                'skipped_duplicate' => '<span class="badge badge--warning">Skipped Duplicate</span>',
                default => '<span class="badge badge--dark">' . ucfirst(str_replace('_', ' ', $this->status)) . '</span>',
            };
        });
    }
}
