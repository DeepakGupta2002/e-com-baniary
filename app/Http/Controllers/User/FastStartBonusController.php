<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FastStartBonusLog;
use Illuminate\Http\Request;

class FastStartBonusController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Fast Start Bonus History';
        $logs = FastStartBonusLog::where('user_id', auth()->id())
            ->with('transaction')
            ->dateFilter()
            ->latest('id')
            ->paginate(getPaginate());

        return view(activeTemplate() . 'user.fast_start_bonus', compact('pageTitle', 'logs'));
    }
}
