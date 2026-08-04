<?php

namespace App\Services;

use App\Models\LeaderGrowthBonusLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class LeaderGrowthBonusService
{
    public const TARGET_BUSINESS = 300000.0;
    public const BONUS_AMOUNT = 30000.0;
    public const WINDOW_DAYS = 30;

    public function handleMatchingPaid(User $matchedUser, Transaction $matchingTransaction, float $freshMatchingBusiness): void
    {
        if ($freshMatchingBusiness <= 0 || $matchingTransaction->remark !== 'binary_commission') {
            return;
        }

        try {
            DB::transaction(function () use ($matchedUser, $matchingTransaction, $freshMatchingBusiness) {
                if ($this->hasProcessedMatching($matchingTransaction->id)) {
                    return;
                }

                $user = User::lockForUpdate()->find($matchedUser->id);
                if (!$user) {
                    return;
                }

                $now = now();
                $cycleStart = $user->leader_growth_cycle_start_at ?: $now;
                $baseBusiness = $this->isCycleExpired($cycleStart, $now) ? 0 : (float) $user->leader_growth_current_business;
                if ($baseBusiness <= 0 && $this->isCycleExpired($cycleStart, $now)) {
                    $cycleStart = $now;
                }

                $currentBusiness = getAmount($baseBusiness + $freshMatchingBusiness, 8);
                $cycleNumber = ((int) $user->leader_growth_bonus_count) + 1;

                if ($currentBusiness >= self::TARGET_BUSINESS) {
                    $this->creditBonus($user, $matchingTransaction, $cycleNumber, $cycleStart, $now, $currentBusiness);
                    return;
                }

                $user->leader_growth_cycle_start_at = $cycleStart;
                $user->leader_growth_current_business = $currentBusiness;
                $user->save();

                LeaderGrowthBonusLog::create([
                    'user_id' => $user->id,
                    'cycle_number' => $cycleNumber,
                    'cycle_start' => $cycleStart,
                    'cycle_end' => null,
                    'required_business' => self::TARGET_BUSINESS,
                    'achieved_business' => $currentBusiness,
                    'bonus_amount' => 0,
                    'matching_transaction_id' => $matchingTransaction->id,
                    'wallet_transaction_id' => null,
                    'status' => 'processed',
                ]);
            });
        } catch (QueryException $exception) {
            if (!$this->isDuplicateMatchingException($exception)) {
                throw $exception;
            }
        }
    }

    private function creditBonus(
        User $user,
        Transaction $matchingTransaction,
        int $cycleNumber,
        $cycleStart,
        $cycleEnd,
        float $achievedBusiness
    ): void {
        $user->balance += self::BONUS_AMOUNT;
        $user->leader_growth_total_bonus += self::BONUS_AMOUNT;
        $user->leader_growth_bonus_count = $cycleNumber;
        $user->leader_growth_last_bonus_at = $cycleEnd;
        $user->leader_growth_current_business = 0;
        $user->leader_growth_cycle_start_at = $cycleEnd;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = self::BONUS_AMOUNT;
        $transaction->charge = 0;
        $transaction->trx_type = '+';
        $transaction->remark = 'leader_growth_bonus';
        $transaction->trx = getTrx();
        $transaction->post_balance = $user->balance;
        $transaction->details = 'Leader Growth Bonus Fresh Matching Business Target Achieved 300000';
        $transaction->save();

        LeaderGrowthBonusLog::create([
            'user_id' => $user->id,
            'cycle_number' => $cycleNumber,
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
            'required_business' => self::TARGET_BUSINESS,
            'achieved_business' => $achievedBusiness,
            'bonus_amount' => self::BONUS_AMOUNT,
            'matching_transaction_id' => $matchingTransaction->id,
            'wallet_transaction_id' => $transaction->id,
            'status' => 'paid',
        ]);

        notify($user, 'DEFAULT', [
            'subject' => 'Leader Growth Bonus Credited',
            'message' => 'Leader Growth Bonus of ' . showAmount(self::BONUS_AMOUNT, currencyFormat: false) . ' credited after fresh matching business target achieved.',
        ]);
    }

    private function hasProcessedMatching(int $matchingTransactionId): bool
    {
        return LeaderGrowthBonusLog::where('matching_transaction_id', $matchingTransactionId)
            ->lockForUpdate()
            ->exists();
    }

    private function isCycleExpired($cycleStart, $now): bool
    {
        return $cycleStart && $now->greaterThan($cycleStart->copy()->addDays(self::WINDOW_DAYS));
    }

    private function isDuplicateMatchingException(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', 23000], true)
            && str_contains($exception->getMessage(), 'leader_growth_matching_transaction_unique');
    }
}
