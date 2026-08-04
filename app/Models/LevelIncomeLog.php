<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LevelIncomeLog extends Model
{
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function source()
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function matchingTransaction()
    {
        return $this->belongsTo(Transaction::class, 'matching_transaction_id');
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return match ($this->status) {
                'paid' => '<span class="badge badge--success">Paid</span>',
                'skipped_inactive' => '<span class="badge badge--warning">Skipped Inactive</span>',
                'capped_out' => '<span class="badge badge--danger">Capped Out</span>',
                default => '<span class="badge badge--dark">' . ucfirst(str_replace('_', ' ', $this->status)) . '</span>',
            };
        });
    }
}
