<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\RankRewardLog;
use App\Models\User;
use App\Services\RankRewardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $rankMatchedBv = app(RankRewardService::class)->matchedRankBv(auth()->id());

        return view(activeTemplate() . 'user.rank_reward', compact('pageTitle', 'logs', 'rankMatchedBv'));
    }

    public function leaderboard()
    {
        $pageTitle = 'Leaderboard';
        $user = auth()->user();
        $activeRanks = Rank::where('status', 1)->orderBy('sort_order')->orderBy('required_team_dp')->get();
        $rankRewardService = app(RankRewardService::class);
        $rankedLeaders = User::with('currentRank')
            ->where('total_team_dp', '>', 0)
            ->get()
            ->map(function (User $leader) use ($rankRewardService) {
                $leader->rank_matched_bv = $rankRewardService->matchedRankBv($leader->id);
                return $leader;
            })
            ->filter(fn (User $leader) => (float) $leader->rank_matched_bv > 0)
            ->sortBy([
                ['rank_matched_bv', 'desc'],
                ['id', 'asc'],
            ])
            ->values();
        $topLeaders = $rankedLeaders->take(3);

        $userPosition = null;
        $rankedLeaders->each(function (User $leader, int $index) use ($user, &$userPosition) {
            if ((int) $leader->id === (int) $user->id) {
                $userPosition = $index + 1;
            }
        });

        $perPage = getPaginate();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $leaders = new LengthAwarePaginator(
            $rankedLeaders->forPage($page, $perPage)->values(),
            $rankedLeaders->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        if ($leaders->isEmpty() && $page > 1) {
            $page = 1;
            $leaders = new LengthAwarePaginator(
                $rankedLeaders->forPage($page, $perPage)->values(),
                $rankedLeaders->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        return view(activeTemplate() . 'user.leaderboard', compact('pageTitle', 'leaders', 'topLeaders', 'activeRanks', 'userPosition'));
    }
}
