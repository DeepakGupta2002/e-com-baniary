<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FranchiseApplication;
use App\Models\FranchiseInvoice;
use App\Models\FranchisePlan;
use App\Models\FranchiseProfile;
use App\Models\FranchiseTransaction;
use App\Models\User;
use App\Services\FranchiseService;
use Illuminate\Http\Request;

class FranchiseController extends Controller
{
    public function publicPage()
    {
        $pageTitle = 'Franchise';
        $plans = FranchisePlan::active()->orderBy('sort_order')->orderBy('id')->get();

        return view(activeTemplate() . 'franchise', compact('pageTitle', 'plans'));
    }

    public function applyForm()
    {
        $pageTitle = 'Apply For Franchise';
        $user = auth()->user()->load('franchiseProfile');

        if (!isActivePackageUser($user) && !hasActiveFranchise($user)) {
            $notify[] = ['error', 'Please activate a plan before using the franchise module'];
            return to_route('user.plan.index')->withNotify($notify);
        }

        $latestApplication = FranchiseApplication::where('user_id', $user->id)->latest('id')->first();
        $plans = FranchisePlan::active()->orderBy('sort_order')->orderBy('id')->get();

        if (request('reference')) {
            $sponsor = User::where('username', request('reference'))
                ->orWhereHas('franchiseProfile', function ($query) {
                    $query->where('franchise_code', request('reference'));
                })->first();

            if ($sponsor && isActivePackageUser($sponsor)) {
                session()->put('franchise_reference', $sponsor->username);
            }
        }

        return view(activeTemplate() . 'user.franchise_apply', compact('pageTitle', 'user', 'latestApplication', 'plans'));
    }

    public function applyStore(Request $request)
    {
        $request->validate([
            'franchise_plan_id' => 'required|exists:franchise_plans,id',
            'sponsor' => 'nullable|string',
            'note' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user()->load('franchiseProfile');

        if (!isActivePackageUser($user) && !hasActiveFranchise($user)) {
            $notify[] = ['error', 'Please activate a plan before using the franchise module'];
            return to_route('user.plan.index')->withNotify($notify);
        }

        $plan = FranchisePlan::active()->findOrFail($request->franchise_plan_id);

        if ($user->franchiseProfile && $user->franchiseProfile->status === 'active') {
            $notify[] = ['error', 'Franchise module is already active on your account'];
            return back()->withNotify($notify);
        }

        if (FranchiseApplication::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            $notify[] = ['error', 'Your franchise application is already pending'];
            return back()->withNotify($notify);
        }

        $sponsorInput = $request->sponsor ?: session('franchise_reference');
        $sponsor = null;

        if ($sponsorInput) {
            $sponsor = User::where('username', $sponsorInput)->orWhere('email', $sponsorInput)->first();
        }

        if ($sponsorInput && !$sponsor) {
            $notify[] = ['error', 'Franchise sponsor not found'];
            return back()->withNotify($notify);
        }

        if ($sponsor && !isActivePackageUser($sponsor)) {
            $notify[] = ['error', 'Selected sponsor must be an active package user'];
            return back()->withNotify($notify);
        }

        FranchiseApplication::create([
            'user_id' => $user->id,
            'sponsor_user_id' => $sponsor?->id,
            'franchise_plan_id' => $plan->id,
            'amount' => $plan->amount,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        session()->forget('franchise_reference');

        $notify[] = ['success', 'Franchise application submitted successfully'];
        return back()->withNotify($notify);
    }

    public function dashboard()
    {
        $pageTitle = 'Franchise Dashboard';
        $profile = $this->profile();
        $transactions = $profile->transactions()->take(5)->get();
        $invoices = $profile->invoices()->take(5)->get();
        $referralLink = route('user.franchise.apply', ['reference' => $profile->franchise_code]);

        return view(activeTemplate() . 'user.franchise_dashboard', compact('pageTitle', 'profile', 'transactions', 'invoices', 'referralLink'));
    }

    public function transactions()
    {
        $pageTitle = 'Franchise Transactions';
        $profile = $this->profile();
        $transactions = FranchiseTransaction::where('franchise_profile_id', $profile->id)->latest('id')->paginate(getPaginate());

        return view(activeTemplate() . 'user.franchise_transactions', compact('pageTitle', 'profile', 'transactions'));
    }

    public function transferForm()
    {
        $pageTitle = 'Franchise Wallet Transfer';
        $profile = $this->profile();
        return view(activeTemplate() . 'user.franchise_transfer', compact('pageTitle', 'profile'));
    }

    public function transferStore(Request $request, FranchiseService $franchiseService)
    {
        $request->validate([
            'username' => 'required|string',
            'amount' => 'required|numeric|gt:0',
            'remark' => 'nullable|string|max:255',
        ]);

        $sender = $this->profile();
        $receiverUser = User::where('username', $request->username)->orWhere('email', $request->username)->first();

        if (!$receiverUser) {
            $notify[] = ['error', 'Receiver user not found'];
            return back()->withNotify($notify);
        }

        if ($receiverUser->id === $sender->user_id) {
            $notify[] = ['error', 'Franchise wallet transfer not possible in your own account'];
            return back()->withNotify($notify);
        }

        if ((float) $sender->wallet_balance < (float) $request->amount) {
            $notify[] = ['error', 'Insufficient franchise wallet balance'];
            return back()->withNotify($notify);
        }

        $trx = $franchiseService->transfer($sender, $receiverUser, (float) $request->amount, $request->remark);

        $notify[] = ['success', 'Franchise wallet transferred successfully. TRX: ' . $trx];
        return back()->withNotify($notify);
    }

    public function invoices()
    {
        $pageTitle = 'Franchise Invoices';
        $profile = $this->profile();
        $invoices = FranchiseInvoice::where('franchise_profile_id', $profile->id)->latest('id')->paginate(getPaginate());

        return view(activeTemplate() . 'user.franchise_invoices', compact('pageTitle', 'profile', 'invoices'));
    }

    private function profile(): FranchiseProfile
    {
        $profile = auth()->user()->load('franchiseProfile')->franchiseProfile;
        abort_if(!$profile || $profile->status !== 'active' || (float) $profile->application_amount <= 0, 403);

        return $profile;
    }
}
