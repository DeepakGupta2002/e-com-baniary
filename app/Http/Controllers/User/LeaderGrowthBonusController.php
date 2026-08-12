<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LeaderGrowthBonusLog;

class LeaderGrowthBonusController extends Controller
{
    public function index()
    {
        $pageTitle = 'Leader Growth Bonus History';
        $logs = LeaderGrowthBonusLog::with(['matchingTransaction', 'walletTransaction'])
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'paid'])
            ->orderByDesc('id')
            ->paginate(getPaginate());

        return view(activeTemplate() . 'user.leader_growth_bonus', compact('pageTitle', 'logs'));
    }
}
