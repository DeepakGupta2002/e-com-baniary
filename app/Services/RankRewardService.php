<?php

namespace App\Services;

use App\Models\Rank;
use App\Models\RankRewardLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\BvLog;

class RankRewardService
{
    public function checkRankReward(User $user): void
    {
        $user = User::lockForUpdate()->findOrFail($user->id);
        $nextRank = Rank::where('status', 1)
            ->orderBy('required_team_dp')
            ->orderBy('sort_order')
            ->lockForUpdate()
            ->get()
            ->first(fn (Rank $rank) => !$this->alreadyRewarded($user->id, $rank->id));

        if (!$nextRank) {
            return;
        }

        $cycleMatchedBv = $this->currentCycleMatchedBv($user->id);

        if ($cycleMatchedBv < (float) $nextRank->required_team_dp) {
            return;
        }

        $this->creditRankReward($user, $nextRank, $cycleMatchedBv);
    }

    private function creditRankReward(User $user, Rank $rank, float $cycleMatchedBv): void
    {
        if ($this->alreadyRewarded($user->id, $rank->id)) {
            return;
        }

        $rewardAmount = getAmount($rank->reward_amount, 8);

        $user->balance += $rewardAmount;
        $user->total_rank_reward += $rewardAmount;
        $user->current_rank_id = $rank->id;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = $rewardAmount;
        $transaction->charge = 0;
        $transaction->trx_type = '+';
        $transaction->remark = 'rank_reward';
        $transaction->trx = getTrx();
        $transaction->post_balance = $user->balance;
        $transaction->details = 'Congratulations! You achieved ' . $rank->name . ' Rank. Reward ' . showAmount($rewardAmount) . ' credited.';
        $transaction->save();

        RankRewardLog::create([
            'user_id' => $user->id,
            'rank_id' => $rank->id,
            'team_dp' => getAmount($cycleMatchedBv, 8),
            'reward_amount' => $rewardAmount,
            'transaction_id' => $transaction->id,
            'status' => 'paid',
        ]);

        $user->rank_cycle_left_bv = 0;
        $user->rank_cycle_right_bv = 0;
        $user->save();

        notify($user, 'DEFAULT', [
            'subject' => 'Rank Reward Credited',
            'message' => 'Congratulations! You achieved ' . $rank->name . ' rank and earned ' . showAmount($rewardAmount, currencyFormat: false),
        ]);
    }

    private function alreadyRewarded(int $userId, int $rankId): bool
    {
        return RankRewardLog::where('user_id', $userId)
            ->where('rank_id', $rankId)
            ->exists();
    }

    public function matchedRankBv(int $userId): float
    {
        $leftBv = BvLog::where('user_id', $userId)->leftBV()->sum('amount');
        $rightBv = BvLog::where('user_id', $userId)->rightBV()->sum('amount');

        return getAmount(min((float) $leftBv, (float) $rightBv), 8);
    }

    public function currentCycleMatchedBv(int $userId): float
    {
        $user = User::find($userId, ['rank_cycle_left_bv', 'rank_cycle_right_bv']);

        if (!$user) {
            return 0;
        }

        return getAmount(min((float) $user->rank_cycle_left_bv, (float) $user->rank_cycle_right_bv), 8);
    }
}
