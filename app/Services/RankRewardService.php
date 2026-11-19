<?php

namespace App\Services;

use App\Models\Rank;
use App\Models\RankRewardLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\BvLog;
use Illuminate\Support\Facades\DB;

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

        $consumedBv = getAmount($rank->required_team_dp, 8);

        $rewardLog = RankRewardLog::where('user_id', $user->id)
            ->where('rank_id', $rank->id)
            ->first();

        if ($rewardLog) {
            $rewardLog->update([
                'team_dp' => $consumedBv,
                'reward_amount' => $rewardAmount,
                'transaction_id' => $transaction->id,
                'status' => 'paid',
            ]);
        } else {
            RankRewardLog::create([
            'user_id' => $user->id,
            'rank_id' => $rank->id,
            'team_dp' => $consumedBv,
            'reward_amount' => $rewardAmount,
            'transaction_id' => $transaction->id,
            'status' => 'paid',
            ]);
        }

        $user->rank_cycle_left_bv = max(0, getAmount((float) $user->rank_cycle_left_bv - $consumedBv, 8));
        $user->rank_cycle_right_bv = max(0, getAmount((float) $user->rank_cycle_right_bv - $consumedBv, 8));
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
            ->where('status', 'paid')
            ->exists();
    }

    public function revokeRankReward(User $user, RankRewardLog $rewardLog): void
    {
        DB::transaction(function () use ($user, $rewardLog) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $rewardLog = RankRewardLog::lockForUpdate()->findOrFail($rewardLog->id);

            if ($rewardLog->user_id !== $user->id || $rewardLog->status !== 'paid') {
                throw new \RuntimeException('Only a paid rank reward belonging to this user can be revoked.');
            }

            $rewardAmount = getAmount($rewardLog->reward_amount, 8);
            $restoredBv = getAmount($rewardLog->team_dp, 8);

            $user->balance = getAmount((float) $user->balance - $rewardAmount, 8);
            $user->total_rank_reward = max(0, getAmount((float) $user->total_rank_reward - $rewardAmount, 8));
            $user->rank_cycle_left_bv = getAmount((float) $user->rank_cycle_left_bv + $restoredBv, 8);
            $user->rank_cycle_right_bv = getAmount((float) $user->rank_cycle_right_bv + $restoredBv, 8);

            $latestPaidRankId = RankRewardLog::where('user_id', $user->id)
                ->where('status', 'paid')
                ->where('id', '!=', $rewardLog->id)
                ->latest('id')
                ->value('rank_id');
            $user->current_rank_id = $latestPaidRankId;
            $user->save();

            $reversal = new Transaction();
            $reversal->user_id = $user->id;
            $reversal->amount = $rewardAmount;
            $reversal->charge = 0;
            $reversal->trx_type = '-';
            $reversal->remark = 'rank_reward_reversal';
            $reversal->trx = getTrx();
            $reversal->post_balance = $user->balance;
            $reversal->details = 'Rank reward revoked for ' . optional($rewardLog->rank)->name . '. Reversed reward ' . showAmount($rewardAmount) . ' and restored ' . showAmount($restoredBv) . ' BV to the rank cycle.';
            $reversal->save();

            $rewardLog->status = 'revoked';
            $rewardLog->save();
        });
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
