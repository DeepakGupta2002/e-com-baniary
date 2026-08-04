<?php

namespace App\Services;

use App\Models\FastStartBonusLog;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class FastStartBonusService
{
    private const PREMIUM_CODE = 'premium_pack';
    private const ROYAL_CODE = 'royal_pack';
    private const WINDOW_DAYS = 15;

    public function handlePlanActivation(User $activatedUser): void
    {
        $activatedUser->loadMissing('plan');

        if (!$this->isQualifyingPlan($activatedUser->plan) || !$activatedUser->ref_by) {
            return;
        }

        $this->evaluateSponsor((int) $activatedUser->ref_by);
    }

    public function evaluateSponsor(int $sponsorId): void
    {
        try {
            DB::transaction(function () use ($sponsorId) {
                $sponsor = User::lockForUpdate()->find($sponsorId);

                if (!$sponsor || $sponsor->fast_start_bonus_claimed || !$sponsor->plan_activated_at) {
                    return;
                }

                if (FastStartBonusLog::where('user_id', $sponsor->id)->lockForUpdate()->exists()) {
                    $sponsor->fast_start_bonus_claimed = true;
                    $sponsor->save();
                    return;
                }

                $activationDate = Carbon::parse($sponsor->plan_activated_at);
                $windowEnd = $activationDate->copy()->addDays(self::WINDOW_DAYS)->endOfDay();

                if (now()->greaterThan($windowEnd)) {
                    return;
                }

                $directReferrals = User::with('plan')
                    ->where('ref_by', $sponsor->id)
                    ->whereNotNull('plan_activated_at')
                    ->whereBetween('plan_activated_at', [$activationDate, $windowEnd])
                    ->lockForUpdate()
                    ->get();

                $premiumCount = $directReferrals->filter(fn ($user) => $this->isPremiumPlan($user->plan))->count();
                $royalCount = $directReferrals->filter(fn ($user) => $this->isRoyalPlan($user->plan))->count();
                $qualification = $this->resolveQualification($premiumCount, $royalCount);

                if (!$qualification) {
                    return;
                }

                $this->creditBonus($sponsor, $qualification['type'], $qualification['amount']);
            });
        } catch (QueryException $exception) {
            if (!$this->isDuplicateBonusException($exception)) {
                throw $exception;
            }
        }
    }

    private function creditBonus(User $sponsor, string $qualifyingType, float $bonusAmount): void
    {
        if ($sponsor->fast_start_bonus_claimed) {
            return;
        }

        $sponsor->balance += $bonusAmount;
        $sponsor->fast_start_bonus_claimed = true;
        $sponsor->fast_start_bonus_amount = $bonusAmount;
        $sponsor->fast_start_bonus_date = now();
        $sponsor->save();

        $transaction = new Transaction();
        $transaction->user_id = $sponsor->id;
        $transaction->amount = $bonusAmount;
        $transaction->charge = 0;
        $transaction->trx_type = '+';
        $transaction->remark = 'fast_start_bonus';
        $transaction->trx = getTrx();
        $transaction->post_balance = $sponsor->balance;
        $transaction->details = 'Fast Start Bonus credited after qualifying within 15-day window.';
        $transaction->save();

        FastStartBonusLog::create([
            'user_id' => $sponsor->id,
            'sponsor_id' => $sponsor->ref_by ?: $sponsor->id,
            'qualifying_type' => $qualifyingType,
            'bonus_amount' => $bonusAmount,
            'transaction_id' => $transaction->id,
            'status' => 'paid',
        ]);

        notify($sponsor, 'DEFAULT', [
            'subject' => 'Fast Start Bonus Credited',
            'message' => 'Fast Start Bonus of ' . showAmount($bonusAmount, currencyFormat: false) . ' credited after qualifying within 15-day window.',
        ]);
    }

    private function resolveQualification(int $premiumCount, int $royalCount): ?array
    {
        if ($royalCount >= 2) {
            return ['type' => 'royal_royal', 'amount' => 6000.0];
        }

        if ($premiumCount >= 1 && $royalCount >= 1) {
            return ['type' => 'premium_royal', 'amount' => 3000.0];
        }

        if ($premiumCount >= 2) {
            return ['type' => 'premium_premium', 'amount' => 3000.0];
        }

        return null;
    }

    private function isQualifyingPlan(?Plan $plan): bool
    {
        return $this->isPremiumPlan($plan) || $this->isRoyalPlan($plan);
    }

    private function isPremiumPlan(?Plan $plan): bool
    {
        return $this->normalizedPlanCode($plan) === self::PREMIUM_CODE;
    }

    private function isRoyalPlan(?Plan $plan): bool
    {
        return $this->normalizedPlanCode($plan) === self::ROYAL_CODE;
    }

    private function normalizedPlanCode(?Plan $plan): ?string
    {
        if (!$plan) {
            return null;
        }

        return strtolower(trim((string) $plan->plan_code));
    }

    private function isDuplicateBonusException(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', 23000], true)
            && str_contains($exception->getMessage(), 'fast_start_bonus_logs_user_unique');
    }
}
