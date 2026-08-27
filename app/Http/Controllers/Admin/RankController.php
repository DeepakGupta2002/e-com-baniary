<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\RankRewardLog;
use App\Models\User;
use App\Services\RankRewardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RankController extends Controller
{
    public function index()
    {
        $pageTitle = 'Rank Management';
        $ranks = Rank::orderBy('sort_order')->orderBy('required_team_dp')->searchable(['name'])->get();
        return view('admin.rank.index', compact('pageTitle', 'ranks'));
    }

    public function store(Request $request, $id = 0)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'required_team_dp' => 'required|numeric|min:0',
            'reward_amount' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
        ]);

        $rank = $id ? Rank::findOrFail($id) : new Rank();
        $rank->name = $request->name;
        $rank->required_team_dp = $request->required_team_dp;
        $rank->reward_amount = $request->reward_amount;
        $rank->sort_order = $request->sort_order;
        $rank->status = $request->boolean('status', true);
        $rank->save();

        $notify[] = ['success', $id ? 'Rank updated successfully' : 'Rank added successfully'];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return Rank::changeStatus($id);
    }

    public function logs(Request $request)
    {
        $pageTitle = 'Rank Reward Logs';
        $logs = RankRewardLog::with(['user', 'rank', 'transaction'])
            ->searchable(['user:username,firstname,lastname', 'rank:name'])
            ->dateFilter()
            ->latest('id')
            ->paginate(getPaginate());

        return view('admin.rank.logs', compact('pageTitle', 'logs'));
    }

    public function leaderboard()
    {
        $pageTitle = 'Rank Leaderboard';
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

        $perPage = getPaginate();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $leaders = new LengthAwarePaginator(
            $rankedLeaders->forPage($page, $perPage)->values(),
            $rankedLeaders->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.rank.leaderboard', compact('pageTitle', 'leaders'));
    }
}
