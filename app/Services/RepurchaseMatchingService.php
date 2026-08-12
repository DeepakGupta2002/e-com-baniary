<?php

namespace App\Services;

use App\Models\RepurchaseBvLog;
use App\Models\RepurchaseMatchingLog;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RepurchaseMatchingService
{
    public const PERCENTAGE = 12.0;

    public function settleDueMonth(?Carbon $now = null): int
    {
        $now = $now ?: now();
        if (!$now->isLastOfMonth()) {
            return 0;
        }

        return $this->settleMonth((int) $now->year, (int) $now->month, $now);
    }

    public function settleMonth(int $year, int $month, ?Carbon $settledAt = null): int
    {
        $settledAt = $settledAt ?: now();
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth()->endOfDay();

        return DB::transaction(function () use ($year, $month, $periodStart, $periodEnd, $settledAt) {
            $userIds = RepurchaseBvLog::whereBetween('created_at', [$periodStart, $periodEnd])
                ->where('status', 'processed')
                ->select('user_id')
                ->distinct()
                ->pluck('user_id');

            $settledCount = 0;

            foreach ($userIds as $userId) {
                if ($this->hasMonthlySettlement((int) $userId, $year, $month)) {
                    continue;
                }

                $alreadyPaidOrderIds = RepurchaseMatchingLog::where('user_id', $userId)
                    ->whereNull('period_year')
                    ->pluck('order_id');
                $latestLegacySettlementAt = RepurchaseMatchingLog::where('user_id', $userId)
                    ->whereNull('period_year')
                    ->max('created_at');

                $logs = RepurchaseBvLog::where('user_id', $userId)
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->where('status', 'processed')
                    ->whereNotIn('order_id', $alreadyPaidOrderIds);

                if ($latestLegacySettlementAt) {
                    $logs->where('created_at', '>', $latestLegacySettlementAt);
                }

                $leftBv = getAmount((float) (clone $logs)->where('side', 'left')->sum('bv'), 8);
                $rightBv = getAmount((float) (clone $logs)->where('side', 'right')->sum('bv'), 8);
                $matchedBv = getAmount(min($leftBv, $rightBv), 8);

                if ($matchedBv <= 0) {
                    continue;
                }

                $user = User::lockForUpdate()->find($userId);
                if (!$user || (int) $user->plan_id <= 0) {
                    continue;
                }

                if ($this->hasMonthlySettlement((int) $userId, $year, $month)) {
                    continue;
                }

                $income = getAmount($matchedBv * (self::PERCENTAGE / 100), 8);
                $orderId = (clone $logs)->orderBy('order_id')->value('order_id');

                $user->balance = getAmount((float) $user->balance + $income, 8);
                $user->total_repurchase_matching_income = getAmount((float) $user->total_repurchase_matching_income + $income, 8);
                $user->repurchase_left_carry = 0;
                $user->repurchase_right_carry = 0;
                $user->save();

                $transaction = new Transaction();
                $transaction->user_id = $user->id;
                $transaction->amount = $income;
                $transaction->charge = 0;
                $transaction->trx_type = '+';
                $transaction->remark = 'repurchase_matching_income';
                $transaction->details = sprintf(
                    'Monthly Repurchase Matching Income (12%%) for %s. Left BV: %s, Right BV: %s. No carry forward.',
                    $periodStart->format('F Y'),
                    getAmount($leftBv),
                    getAmount($rightBv)
                );
                $transaction->trx = getTrx();
                $transaction->post_balance = $user->balance;
                $transaction->save();

                RepurchaseMatchingLog::create([
                    'user_id' => $user->id,
                    'order_id' => $orderId,
                    'period_year' => $year,
                    'period_month' => $month,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'left_bv' => $leftBv,
                    'right_bv' => $rightBv,
                    'matched_bv' => $matchedBv,
                    'percentage' => self::PERCENTAGE,
                    'income' => $income,
                    'carry_left' => 0,
                    'carry_right' => 0,
                    'transaction_id' => $transaction->id,
                    'status' => 'paid',
                    'settled_at' => $settledAt,
                    'created_at' => $settledAt,
                ]);

                $settledCount++;
            }

            return $settledCount;
        });
    }

    private function hasMonthlySettlement(int $userId, int $year, int $month): bool
    {
        return RepurchaseMatchingLog::where('user_id', $userId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->exists();
    }
}
