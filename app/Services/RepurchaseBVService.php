<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RepurchaseBvLog;
use App\Models\User;

class RepurchaseBVService
{
    public function processOrder(Order $order): void
    {
        $order->loadMissing(['product', 'user']);

        $bv = (float) ($order->product?->bv ?? 0) * (int) $order->quantity;
        if ($bv <= 0 || !$order->user) {
            return;
        }

        $currentUserId = $order->user_id;

        while ($currentUserId) {
            $currentUser = User::find($currentUserId);
            if (!$currentUser || !$currentUser->pos_id) {
                break;
            }

            $upline = User::lockForUpdate()->find($currentUser->pos_id);
            if (!$upline) {
                break;
            }

            if ((int) $upline->plan_id > 0) {
                $this->addRepurchaseBv($upline, $order, $currentUser, $bv);
            }

            $currentUserId = $upline->id;
        }
    }

    private function addRepurchaseBv(User $upline, Order $order, User $fromUser, float $bv): void
    {
        $side = (int) $fromUser->position === 1 ? 'left' : 'right';

        if ($side === 'left') {
            $upline->repurchase_left_bv = getAmount((float) $upline->repurchase_left_bv + $bv, 8);
        } else {
            $upline->repurchase_right_bv = getAmount((float) $upline->repurchase_right_bv + $bv, 8);
        }

        $upline->save();

        RepurchaseBvLog::create([
            'user_id' => $upline->id,
            'from_user_id' => $order->user_id,
            'order_id' => $order->id,
            'side' => $side,
            'bv' => $bv,
            'status' => 'processed',
            'created_at' => now(),
        ]);
    }
}
