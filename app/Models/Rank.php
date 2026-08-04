<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Rank extends Model
{
    use GlobalStatus;

    protected $fillable = [
        'name',
        'required_team_dp',
        'reward_amount',
        'sort_order',
        'status',
    ];

    public function logs()
    {
        return $this->hasMany(RankRewardLog::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'current_rank_id');
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            return $this->status
                ? '<span class="badge badge--success">Active</span>'
                : '<span class="badge badge--danger">Inactive</span>';
        });
    }
}
