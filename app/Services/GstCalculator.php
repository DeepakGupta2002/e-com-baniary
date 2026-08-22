<?php

namespace App\Services;

class GstCalculator
{
    public function calculate(float $unitPrice, int $quantity): array
    {
        $lineTotal = getAmount($unitPrice * $quantity, 8);
        $general = gs();
        $gstEnabled = (bool) ($general->gst_status ?? false);
        $gstPercent = $gstEnabled ? (float) ($general->gst_percent ?? 0) : 0;
        $gstType = strtolower((string) ($general->gst_type ?? 'exclusive'));

        if (!$gstEnabled || $gstPercent <= 0) {
            return [
                'subtotal' => $lineTotal,
                'gst_status' => false,
                'gst_type' => null,
                'gst_percent' => 0,
                'gst_amount' => 0,
                'total' => $lineTotal,
            ];
        }

        if ($gstType === 'inclusive') {
            $subtotal = getAmount($lineTotal / (1 + ($gstPercent / 100)), 8);
            $gstAmount = getAmount($lineTotal - $subtotal, 8);

            return [
                'subtotal' => $subtotal,
                'gst_status' => true,
                'gst_type' => 'inclusive',
                'gst_percent' => $gstPercent,
                'gst_amount' => $gstAmount,
                'total' => $lineTotal,
            ];
        }

        $gstAmount = getAmount($lineTotal * ($gstPercent / 100), 8);

        return [
            'subtotal' => $lineTotal,
            'gst_status' => true,
            'gst_type' => 'exclusive',
            'gst_percent' => $gstPercent,
            'gst_amount' => $gstAmount,
            'total' => getAmount($lineTotal + $gstAmount, 8),
        ];
    }
}
