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
        $user = User::with('currentRank')->lockForUpdate()->findOrFail($user->id);
        $matchedRankBv = $this->matchedRankBv($user->id);

        $eligibleRanks = Rank::where('status', 1)
            ->where('required_team_dp', '<=', $matchedRankBv)
            ->orderBy('required_team_dp')
            ->orderBy('sort_order')
            ->lockForUpdate()
            ->get();

        $currentRankId = null;
        foreach ($eligibleRanks as $rank) {
            $currentRankId = $rank->id;

            if ($this->alreadyRewarded($user->id, $rank->id)) {
                $user->current_rank_id = $rank->id;
                $user->save();
                continue;
            }

            $this->creditRankReward($user, $rank);
            $user->refresh();
        }

        if ($user->current_rank_id !== $currentRankId) {
            $user->current_rank_id = $currentRankId;
            $user->save();
        }
    }

    private function creditRankReward(User $user, Rank $rank): void
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
            'team_dp' => getAmount($this->matchedRankBv($user->id), 8),
            'reward_amount' => $rewardAmount,
            'transaction_id' => $transaction->id,
            'status' => 'paid',
        ]);

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
}
