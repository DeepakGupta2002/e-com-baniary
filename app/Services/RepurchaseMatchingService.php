<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RepurchaseMatchingLog;
use App\Models\Transaction;
use App\Models\User;

class RepurchaseMatchingService
{
    public const PERCENTAGE = 12.0;

    public function process(User $user, Order $order): ?RepurchaseMatchingLog
    {
        $existingLog = RepurchaseMatchingLog::where('user_id', $user->id)
            ->where('order_id', $order->id)
            ->first();

        if ($existingLog) {
            return $existingLog;
        }

        $leftCarry = (float) $user->repurchase_left_carry;
        $rightCarry = (float) $user->repurchase_right_carry;
        $matchedBv = getAmount(min($leftCarry, $rightCarry), 8);

        if ($matchedBv <= 0) {
            return null;
        }

        $income = getAmount($matchedBv * (self::PERCENTAGE / 100), 8);
        $carryLeft = getAmount($leftCarry - $matchedBv, 8);
        $carryRight = getAmount($rightCarry - $matchedBv, 8);

        $user->balance = getAmount((float) $user->balance + $income, 8);
        $user->total_repurchase_matching_income = getAmount((float) $user->total_repurchase_matching_income + $income, 8);
        $user->repurchase_left_carry = $carryLeft;
        $user->repurchase_right_carry = $carryRight;
        $user->save();

        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = $income;
        $transaction->charge = 0;
        $transaction->trx_type = '+';
        $transaction->remark = 'repurchase_matching_income';
        $transaction->details = 'Repurchase Matching Income (12%)';
        $transaction->trx = getTrx();
        $transaction->post_balance = $user->balance;
        $transaction->save();

        return RepurchaseMatchingLog::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'left_bv' => $leftCarry,
            'right_bv' => $rightCarry,
            'matched_bv' => $matchedBv,
            'percentage' => self::PERCENTAGE,
            'income' => $income,
            'carry_left' => $carryLeft,
            'carry_right' => $carryRight,
            'transaction_id' => $transaction->id,
            'status' => 'paid',
            'created_at' => now(),
        ]);
    }
}
