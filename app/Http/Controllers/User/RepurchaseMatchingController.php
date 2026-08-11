<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RepurchaseMatchingLog;

class RepurchaseMatchingController extends Controller
{
    public function index()
    {
        $pageTitle = 'Repurchase Matching History';
        $logs = RepurchaseMatchingLog::with(['order', 'transaction'])
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(getPaginate());

        return view('Template::user.repurchase_matching', compact('pageTitle', 'logs'));
    }
}
