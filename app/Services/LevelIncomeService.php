<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\LevelIncomeLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class LevelIncomeService
{
    private const LEVEL_PERCENTAGES = [
        1 => 10,
        2 => 4,
        3 => 3,
        4 => 3,
        5 => 2,
        6 => 2,
        7 => 2,
        8 => 2,
        9 => 1,
        10 => 1,
    ];

    public function distribute(User $sourceUser, Transaction $matchingTransaction, float $matchingIncome): void
    {
        if ($matchingIncome <= 0) {
            return;
        }

        foreach ($this->findEligibleSponsors($sourceUser) as $entry) {
            $receiver = $entry['user'];
            $level = $entry['level'];
            $percentage = self::LEVEL_PERCENTAGES[$level];

            if ($this->hasExistingLevelLog($receiver->id, $matchingTransaction->id, $level)) {
                continue;
            }

            if (!$entry['eligible']) {
                $this->saveLog($receiver->id, $sourceUser->id, $matchingTransaction->id, $level, $matchingIncome, $percentage, 0, 'skipped_inactive');
                continue;
            }

            $this->creditLevelIncome($receiver->id, $sourceUser, $matchingTransaction, $level, $matchingIncome, $percentage);
        }
    }

    public function findEligibleSponsors(User $sourceUser): array
    {
        $sponsors = [];
        $currentId = $sourceUser->ref_by;

        for ($level = 1; $level <= 10 && $currentId; $level++) {
            $upline = User::find($currentId);

            if (!$upline) {
                break;
            }

            $sponsors[] = [
                'user' => $upline,
                'level' => $level,
                'eligible' => $this->isActiveId($upline),
            ];

            $currentId = $upline->ref_by;
        }

        return $sponsors;
    }

    public function creditLevelIncome(
        int $receiverUserId,
        User $sourceUser,
        Transaction $matchingTransaction,
        int $level,
        float $matchingIncome,
        float $percentage
    ): void {
        $creditAmount = 0;
        $receiver = null;

        try {
            DB::transaction(function () use (
                $receiverUserId,
                $sourceUser,
                $matchingTransaction,
                $level,
                $matchingIncome,
                $percentage,
                &$creditAmount,
                &$receiver
            ) {
                $receiver = User::with('plan')->lockForUpdate()->findOrFail($receiverUserId);

                if ($this->hasExistingLevelLog($receiver->id, $matchingTransaction->id, $level)) {
                    return;
                }

                if (!$this->isActiveId($receiver)) {
                    $this->saveLog($receiver->id, $sourceUser->id, $matchingTransaction->id, $level, $matchingIncome, $percentage, 0, 'skipped_inactive', false);
                    return;
                }

                $calculatedAmount = getAmount($matchingIncome * ($percentage / 100), 8);
                $creditAmount = getCommissionCreditAmountByCapping($receiver, $calculatedAmount);

                if ($creditAmount <= 0) {
                    $this->saveLog($receiver->id, $sourceUser->id, $matchingTransaction->id, $level, $matchingIncome, $percentage, 0, 'capped_out', false);
                    return;
                }

                $receiver->balance += $creditAmount;
                $receiver->total_level_income += $creditAmount;
                $receiver->save();

                $transaction = new Transaction();
                $transaction->user_id = $receiver->id;
                $transaction->amount = $creditAmount;
                $transaction->charge = 0;
                $transaction->trx_type = '+';
                $transaction->remark = 'level_income';
                $transaction->trx = getTrx();
                $transaction->post_balance = $receiver->balance;
                $transaction->details = sprintf(
                    'Level %d income from %s on matching income %s at %s%%. Earned %s.',
                    $level,
                    $sourceUser->username,
                    showAmount($matchingIncome, currencyFormat: false),
                    getAmount($percentage),
                    showAmount($creditAmount)
                );
                $transaction->save();

                $this->saveLog($receiver->id, $sourceUser->id, $matchingTransaction->id, $level, $matchingIncome, $percentage, $creditAmount, 'paid', false);
            });
        } catch (QueryException $exception) {
            if (!$this->isDuplicateLevelLogException($exception)) {
                throw $exception;
            }

            return;
        }

        if ($creditAmount <= 0 || !$receiver) {
            return;
        }

        notify($receiver, 'DEFAULT', [
            'subject' => 'Level Income Credited',
            'message' => 'Level ' . $level . ' income of ' . showAmount($creditAmount, currencyFormat: false) . ' credited from ' . $sourceUser->username,
        ]);
    }

    public function saveLog(
        int $receiverUserId,
        int $sourceUserId,
        int $matchingTransactionId,
        int $level,
        float $matchingIncome,
        float $percentage,
        float $amount,
        string $status,
        bool $ignoreDuplicate = true
    ): void {
        try {
            $log = new LevelIncomeLog();
            $log->receiver_user_id = $receiverUserId;
            $log->source_user_id = $sourceUserId;
            $log->matching_transaction_id = $matchingTransactionId;
            $log->level_no = $level;
            $log->matching_income = getAmount($matchingIncome, 8);
            $log->percentage = getAmount($percentage, 2);
            $log->amount = getAmount($amount, 8);
            $log->status = $status;
            $log->save();
        } catch (QueryException $exception) {
            if (!$ignoreDuplicate || !$this->isDuplicateLevelLogException($exception)) {
                throw $exception;
            }
        }
    }

    private function isActiveId(User $user): bool
    {
        return $user->status == Status::USER_ACTIVE
            && $user->ev == Status::VERIFIED
            && $user->sv == Status::VERIFIED
            && (int) $user->plan_id > 0;
    }

    private function hasExistingLevelLog(int $receiverUserId, int $matchingTransactionId, int $level): bool
    {
        return LevelIncomeLog::where('receiver_user_id', $receiverUserId)
            ->where('matching_transaction_id', $matchingTransactionId)
            ->where('level_no', $level)
            ->exists();
    }

    private function isDuplicateLevelLogException(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', 23000], true)
            && str_contains($exception->getMessage(), 'level_income_logs_receiver_matching_level_unique');
    }
}
