<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class RepurchaseBvLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'from_user_id',
        'order_id',
        'side',
        'bv',
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

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return $this->status === 'processed'
                ? '<span class="badge badge--success">' . trans('Processed') . '</span>'
                : '<span class="badge badge--warning">' . trans(ucfirst($this->status)) . '</span>';
        });
    }
}
