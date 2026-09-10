<?php

namespace App\Services;

use App\Models\FranchiseApplication;
use App\Models\FranchisePlan;
use App\Models\FranchiseProfile;
use App\Models\FranchiseTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FranchiseService
{
    public function approveApplication(FranchiseApplication $application): FranchiseProfile
    {
        return DB::transaction(function () use ($application) {
            $application = FranchiseApplication::with(['user', 'sponsor.franchiseProfile', 'plan'])->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== 'pending') {
                return FranchiseProfile::where('user_id', $application->user_id)->firstOrFail();
            }

            $profile = FranchiseProfile::firstOrCreate(
                ['user_id' => $application->user_id],
                [
                    'sponsor_user_id' => $application->sponsor_user_id,
                    'franchise_plan_id' => $application->franchise_plan_id,
                    'franchise_code' => $this->generateCode($application->user),
                    'application_amount' => $application->amount,
                    'activated_at' => now(),
                    'status' => 'active',
                ]
            );

            $profile->sponsor_user_id = $application->sponsor_user_id;
            $profile->franchise_plan_id = $application->franchise_plan_id;
            $profile->application_amount = $application->amount;
            $profile->activated_at = $profile->activated_at ?: now();
            $profile->status = 'active';
            $profile->save();

            $application->status = 'approved';
            $application->approved_at = now();
            $application->save();

            $this->creditApplicantWallet($profile, $application->plan, (float) $application->amount);

            if ($application->sponsor_user_id) {
                $this->creditSponsorCommission($application->sponsor_user_id, $application->user_id, $application->plan, (float) $application->amount);
            }

            return $profile;
        });
    }

    public function rejectApplication(FranchiseApplication $application, ?string $note = null): void
    {
        $application->status = 'rejected';
        $application->note = $note;
        $application->save();
    }

    public function transfer(FranchiseProfile $sender, User $receiver, float $amount, ?string $remark = null): string
    {
        return DB::transaction(function () use ($sender, $receiver, $amount, $remark) {
            $sender = FranchiseProfile::with('user')->lockForUpdate()->findOrFail($sender->id);
            $receiver = User::lockForUpdate()->findOrFail($receiver->id);

            if ($amount <= 0 || $sender->user_id === $receiver->id || $sender->wallet_balance < $amount) {
                throw new \RuntimeException('Invalid franchise transfer request');
            }

            $trx = getTrx();

            $sender->wallet_balance -= $amount;
            $sender->total_transfer_sent += $amount;
            $sender->save();

            FranchiseTransaction::create([
                'franchise_profile_id' => $sender->id,
                'related_user_id' => $receiver->id,
                'trx' => $trx,
                'remark' => 'franchise_transfer_send',
                'trx_type' => '-',
                'amount' => $amount,
                'charge' => 0,
                'post_balance' => $sender->wallet_balance,
                'details' => 'Franchise wallet transferred to ' . $receiver->username . ($remark ? ' - ' . $remark : ''),
            ]);

            $receiver->balance += $amount;
            $receiver->save();

            $this->createUserTransaction(
                $receiver,
                $trx,
                'franchise_transfer_receive',
                '+',
                $amount,
                'Franchise wallet received from ' . $sender->user->username . ($remark ? ' - ' . $remark : '')
            );

            return $trx;
        });
    }

    public function createTransaction(FranchiseProfile $profile, ?int $relatedUserId, string $trx, string $remark, string $trxType, float $amount, float $postBalance, string $details): void
    {
        FranchiseTransaction::create([
            'franchise_profile_id' => $profile->id,
            'related_user_id' => $relatedUserId,
            'trx' => $trx,
            'remark' => $remark,
            'trx_type' => $trxType,
            'amount' => $amount,
            'charge' => 0,
            'post_balance' => $postBalance,
            'details' => $details,
        ]);
    }

    private function createUserTransaction(User $user, string $trx, string $remark, string $trxType, float $amount, string $details): void
    {
        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = $amount;
        $transaction->charge = 0;
        $transaction->trx_type = $trxType;
        $transaction->details = $details;
        $transaction->remark = $remark;
        $transaction->trx = $trx;
        $transaction->post_balance = $user->balance;
        $transaction->save();
    }

    private function creditApplicantWallet(FranchiseProfile $profile, ?FranchisePlan $plan, float $baseAmount): void
    {
        $commissionPercent = (float) ($plan?->direct_commission ?? 0);
        $commissionAmount = getAmount($baseAmount * ($commissionPercent / 100), 8);
        $creditAmount = getAmount($baseAmount + $commissionAmount, 8);

        if ($creditAmount <= 0) {
            return;
        }

        $trx = getTrx();
        $profile->wallet_balance += $creditAmount;
        $profile->total_commission += $commissionAmount;
        $profile->save();

        $this->createTransaction(
            $profile,
            $profile->sponsor_user_id,
            $trx,
            'franchise_plan_credit',
            '+',
            $creditAmount,
            $profile->wallet_balance,
            'Franchise plan amount credited with ' . getAmount($commissionPercent) . '% commission'
        );
    }

    private function creditSponsorCommission(int $sponsorUserId, int $joiningUserId, ?FranchisePlan $plan, float $baseAmount): void
    {
        $sponsor = User::with('franchiseProfile')->find($sponsorUserId);
        if (!$sponsor || !isActivePackageUser($sponsor)) {
            return;
        }

        $commissionPercent = (float) gs('franchise_direct_commission');
        $amount = getAmount($baseAmount * ($commissionPercent / 100), 8);
        if ($amount <= 0) {
            return;
        }

        $trx = getTrx();
        $joiningUser = User::find($joiningUserId);

        if (hasActiveFranchise($sponsor)) {
            $sponsorProfile = FranchiseProfile::lockForUpdate()->where('user_id', $sponsorUserId)->first();
            $sponsorProfile->wallet_balance += $amount;
            $sponsorProfile->total_commission += $amount;
            $sponsorProfile->total_direct_referrals += 1;
            $sponsorProfile->save();

            $this->createTransaction(
                $sponsorProfile,
                $joiningUserId,
                $trx,
                'franchise_referral_commission',
                '+',
                $amount,
                $sponsorProfile->wallet_balance,
                ($joiningUser?->username ?? 'User') . ' franchise application approved. Sponsor commission credited at ' . getAmount($commissionPercent) . '%'
            );

            return;
        }

        $sponsor = User::lockForUpdate()->find($sponsorUserId);
        $sponsor->balance += $amount;
        $sponsor->total_franchise_com += $amount;
        $sponsor->save();

        $this->createUserTransaction(
            $sponsor,
            $trx,
            'franchise_referral_commission',
            '+',
            $amount,
            ($joiningUser?->username ?? 'User') . ' franchise application approved. Sponsor commission credited at ' . getAmount($commissionPercent) . '%'
        );
    }

    private function generateCode(User $user): string
    {
        return 'FRN' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);
    }
}
