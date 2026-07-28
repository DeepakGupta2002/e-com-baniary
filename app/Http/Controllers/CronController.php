<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\CronJob;
use App\Lib\CurlRequest;
use App\Constants\Status;
use App\Models\UserExtra;
use App\Models\CronJobLog;
use App\Models\Transaction;

class CronController extends Controller
{
    public function cron()
    {
        $general            = gs();
        $general->last_cron = now();
        $general->save();
        
        $crons = CronJob::with('schedule');

        if (request()->alias) {
            $crons->where('alias', request()->alias);
        } else {
            $crons->where('next_run', '<', now())->where('is_running', Status::YES);
        }
        $crons = $crons->get();
        foreach ($crons as $cron) {
            $cronLog              = new CronJobLog();
            $cronLog->cron_job_id = $cron->id;
            $cronLog->start_at    = now();
            if ($cron->is_default) {
                $controller = new $cron->action[0];
                try {
                    $method = $cron->action[1];
                    $controller->$method();
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            } else {
                try {
                    CurlRequest::curlContent($cron->url);
                } catch (\Exception $e) {
                    $cronLog->error = $e->getMessage();
                }
            }
            $cron->last_run = now();
            $cron->next_run = now()->addSeconds($cron->schedule->interval);
            $cron->save();

            $cronLog->end_at = $cron->last_run;

            $startTime         = Carbon::parse($cronLog->start_at);
            $endTime           = Carbon::parse($cronLog->end_at);
            $diffInSeconds     = $startTime->diffInSeconds($endTime);
            $cronLog->duration = $diffInSeconds;
            $cronLog->save();
        }
        if (request()->target == 'all') {
            $notify[] = ['success', 'Cron executed successfully'];
            return back()->withNotify($notify);
        }
        if (request()->alias) {
            $notify[] = ['success', keyToTitle(request()->alias) . ' executed successfully'];
            return back()->withNotify($notify);
        }
    }


    private function matchingBound()
    { 
        $generalSetting = gs();
        if ($generalSetting->matching_bonus_time == 'daily') {
            $day = Date('H');
            if (strtolower($day) != $generalSetting->matching_when) {
                return '1';
            }
        }
      

        if ($generalSetting->matching_bonus_time == 'weekly') {
            $day = Date('D');
            if (strtolower($day) != $generalSetting->matching_when) {
                return '2';
            }
        }

        if ($generalSetting->matching_bonus_time == 'monthly') {
            $day = Date('d');
            if (strtolower($day) != $generalSetting->matching_when) {
                return '3';
            }
        }
       
     
        if (Carbon::now()->toDateString() > Carbon::parse($generalSetting->last_paid)->toDateString()) {
            $generalSetting->last_paid = Carbon::now()->toDateString();
            $generalSetting->save();

            $eligibleUsers = UserExtra::where('bv_left', '>', 0)->where('bv_right', '>', 0)->get();
            foreach ($eligibleUsers as $uex) {
                $weak = $uex->bv_left < $uex->bv_right ? $uex->bv_left : $uex->bv_right;
                // ORIVA matches directly on the full weak-leg business with no pair-unit rounding.
                $paidbv = $weak;

                $payment = User::find($uex->user_id);
                $currentPlan = $payment?->plan;

                if (!$payment || !$currentPlan) {
                    continue;
                }

                $matchingPercentage = (float) ($currentPlan->tree_com ?? 0);
                $bonus = getAmount($paidbv * ($matchingPercentage / 100), 8);
                $creditAmount = getCommissionCreditAmountByCapping($payment, $bonus);
                $isCapped = $creditAmount < $bonus;

                if ($bonus > 0 && $paidbv > 0) {
                    $consumedBv = getAmount(($creditAmount / $bonus) * $paidbv, 8);
                } else {
                    $consumedBv = 0;
                }

                if ($creditAmount <= 0) {
                    continue;
                }

                $payment->balance += $creditAmount;
                $payment->total_binary_com += $creditAmount;
                $payment->save();

                $user = $payment;

                $trx = new Transaction();
                $trx->user_id = $payment->id;
                $trx->amount = $creditAmount;
                $trx->charge = 0;
                $trx->trx_type = '+';
                $trx->post_balance = $payment->balance;
                $trx->remark = 'binary_commission';
                $trx->trx = getTrx();
                $trx->details = 'Paid ' . showAmount($creditAmount) . ' For ' . $consumedBv . ' matched BV.' . ($isCapped ? ' Remaining matching BV carried forward due to capping.' : '');
                $trx->save();

                notify($user, 'MATCHING_BONUS', [
                    'amount' => showAmount($creditAmount,currencyFormat:false),
                    'paid_bv' => $consumedBv,
                    'post_balance' => showAmount($payment->balance,currencyFormat:false),
                    'trx' =>  $trx->trx,
                ]);

                if ($isCapped) {
                    // ORIVA 4th time capping carries unpaid matching BV into the next cycle.
                    $uex->bv_left = max(0, getAmount($uex->bv_left - $consumedBv, 8));
                    $uex->bv_right = max(0, getAmount($uex->bv_right - $consumedBv, 8));
                    $uex->save();
                    if ($consumedBv != 0) {
                    createBVLog($user->id, 1, $consumedBv, 'Paid ' . $creditAmount . ' ' . $generalSetting->cur_text . ' for ' . $consumedBv . ' matched BV after capping.');
                        createBVLog($user->id, 2, $consumedBv, 'Paid ' . $creditAmount . ' ' . $generalSetting->cur_text . ' for ' . $consumedBv . ' matched BV after capping.');
                    }
                    continue;
                }

                if ($generalSetting->cary_flash == 0) {
                    $bv['setl'] = $uex->bv_left - $paidbv;
                    $bv['setr'] = $uex->bv_right - $paidbv;
                    $bv['paid'] = $paidbv;
                    $bv['lostl'] = 0;
                    $bv['lostr'] = 0;
                }
                if ($generalSetting->cary_flash == 1) {
                    $bv['setl'] = $uex->bv_left - $weak;
                    $bv['setr'] = $uex->bv_right - $weak;
                    $bv['paid'] = $paidbv;
                    $bv['lostl'] = $weak - $paidbv;
                    $bv['lostr'] = $weak - $paidbv;
                }
                if ($generalSetting->cary_flash == 2) {
                    $bv['setl'] = 0;
                    $bv['setr'] = 0;
                    $bv['paid'] = $paidbv;
                    $bv['lostl'] = $uex->bv_left - $paidbv;
                    $bv['lostr'] = $uex->bv_right - $paidbv;
                }
                $uex->bv_left = $bv['setl'];
                $uex->bv_right = $bv['setr'];
                $uex->save();


                if ($bv['paid'] != 0) {
                    createBVLog($user->id, 1, $bv['paid'], 'Paid ' . $bonus . ' ' . $generalSetting->cur_text . ' For ' . $paidbv . ' matched BV.');
                    createBVLog($user->id, 2, $bv['paid'], 'Paid ' . $bonus . ' ' . $generalSetting->cur_text . ' For ' . $paidbv . ' matched BV.');
                }
                if ($bv['lostl'] != 0) {
                    createBVLog($user->id, 1, $bv['lostl'], 'Flush ' . $bv['lostl'] . ' BV after Paid ' . $bonus . ' ' . $generalSetting->cur_text . ' For ' . $paidbv . ' matched BV.');
                }
                if ($bv['lostr'] != 0) {
                    createBVLog($user->id, 2, $bv['lostr'], 'Flush ' . $bv['lostr'] . ' BV after Paid ' . $bonus . ' ' . $generalSetting->cur_text . ' For ' . $paidbv . ' matched BV.');
                }
            }
            return '---';
        }
    }
}
