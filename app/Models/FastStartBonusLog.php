<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class FastStartBonusLog extends Model
{
    protected $fillable = [
        'user_id',
        'sponsor_id',
        'qualifying_type',
        'bonus_amount',
        'transaction_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
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
                default => '<span class="badge badge--dark">' . ucfirst(str_replace('_', ' ', $this->status)) . '</span>',
            };
        });
    }

    public function qualifyingTypeText(): Attribute
    {
        return new Attribute(function () {
            return match ($this->qualifying_type) {
                'premium_premium' => 'Premium + Premium',
                'premium_royal' => 'Premium + Royal',
                'royal_royal' => 'Royal + Royal',
                default => ucfirst(str_replace('_', ' ', $this->qualifying_type)),
            };
        });
    }
}
