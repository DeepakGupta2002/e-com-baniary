<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LevelIncomeLog;
use Illuminate\Http\Request;

class LevelIncomeController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Level Income';
        $logs = LevelIncomeLog::where('receiver_user_id', auth()->id())
            ->with(['source', 'matchingTransaction'])
            ->searchable(['source:username,firstname,lastname'])
            ->dateFilter()
            ->orderByDesc('id');

        if ($request->level_no) {
            $logs->where('level_no', $request->level_no);
        }

        $logs = $logs->paginate(getPaginate());

        return view('Template::user.level_income', compact('pageTitle', 'logs'));
    }
}
