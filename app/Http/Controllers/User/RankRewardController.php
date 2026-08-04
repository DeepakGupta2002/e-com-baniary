<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\RankRewardLog;
use App\Models\User;
use Illuminate\Http\Request;

class RankRewardController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Rank & Rewards';
        $logs = RankRewardLog::where('user_id', auth()->id())
            ->with('rank')
            ->searchable(['rank:name'])
            ->dateFilter()
            ->latest('id')
            ->paginate(getPaginate());

        return view(activeTemplate() . 'user.rank_reward', compact('pageTitle', 'logs'));
    }

    public function leaderboard()
    {
        $pageTitle = 'Leaderboard';
        $user = auth()->user();
        $activeRanks = Rank::where('status', 1)->orderBy('sort_order')->orderBy('required_team_dp')->get();
        $topLeaders = User::with('currentRank')
            ->where('total_team_dp', '>', 0)
            ->orderByDesc('total_team_dp')
            ->orderBy('id')
            ->limit(3)
            ->get();

        $userPosition = null;
        if ((float) $user->total_team_dp > 0) {
            $userPosition = User::where('total_team_dp', '>', 0)
                    ->where(function ($query) use ($user) {
                        $query->where('total_team_dp', '>', $user->total_team_dp)
                            ->orWhere(function ($query) use ($user) {
                                $query->where('total_team_dp', $user->total_team_dp)
                                    ->where('id', '<', $user->id);
                            });
                    })
                    ->count() + 1;
        }

        $leaders = User::with('currentRank')
            ->where('total_team_dp', '>', 0)
            ->orderByDesc('total_team_dp')
            ->orderBy('id')
            ->paginate(getPaginate());

        return view(activeTemplate() . 'user.leaderboard', compact('pageTitle', 'leaders', 'topLeaders', 'activeRanks', 'userPosition'));
    }
}
