<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FranchiseApplication;
use App\Models\FranchiseInvoice;
use App\Models\FranchisePlan;
use App\Models\FranchiseProfile;
use App\Services\FranchiseService;
use Illuminate\Http\Request;

class FranchiseController extends Controller
{
    public function plans()
    {
        $pageTitle = 'Franchise Plans';
        $plans = FranchisePlan::orderBy('sort_order')->orderBy('id')->paginate(getPaginate());

        return view('admin.franchise.plans', compact('pageTitle', 'plans'));
    }

    public function planStore(Request $request, $id = 0)
    {
        $request->validate([
            'name' => 'required|string|max:80',
            'amount' => 'required|numeric|gt:0',
            'direct_commission' => 'required|numeric|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:0,1',
        ]);

        $plan = $id ? FranchisePlan::findOrFail($id) : new FranchisePlan();
        $plan->name = $request->name;
        $plan->amount = $request->amount;
        $plan->direct_commission = $request->direct_commission;
        $plan->sort_order = $request->sort_order ?? 0;
        $plan->status = $request->status;
        $plan->save();

        $notify[] = ['success', 'Franchise plan saved successfully'];
        return back()->withNotify($notify);
    }

    public function applications()
    {
        $pageTitle = 'Franchise Applications';
        $applications = FranchiseApplication::with(['user', 'sponsor', 'plan'])->latest('id')->paginate(getPaginate());

        return view('admin.franchise.applications', compact('pageTitle', 'applications'));
    }

    public function approve($id, FranchiseService $franchiseService)
    {
        $franchiseService->approveApplication(FranchiseApplication::findOrFail($id));

        $notify[] = ['success', 'Franchise application approved successfully'];
        return back()->withNotify($notify);
    }

    public function reject(Request $request, $id, FranchiseService $franchiseService)
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $franchiseService->rejectApplication(FranchiseApplication::findOrFail($id), $request->note);

        $notify[] = ['success', 'Franchise application rejected successfully'];
        return back()->withNotify($notify);
    }

    public function users()
    {
        $pageTitle = 'Franchise Users';
        $profiles = FranchiseProfile::with(['user', 'sponsor', 'plan'])->latest('id')->paginate(getPaginate());

        return view('admin.franchise.users', compact('pageTitle', 'profiles'));
    }

    public function invoices()
    {
        $pageTitle = 'Franchise Invoices';
        $profiles = FranchiseProfile::with('user')->where('status', 'active')->get();
        $invoices = FranchiseInvoice::with('profile.user')->latest('id')->paginate(getPaginate());

        return view('admin.franchise.invoices', compact('pageTitle', 'profiles', 'invoices'));
    }

    public function invoiceStore(Request $request)
    {
        $request->validate([
            'franchise_profile_id' => 'required|exists:franchise_profiles,id',
            'amount' => 'required|numeric|gt:0',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        FranchiseInvoice::create([
            'franchise_profile_id' => $request->franchise_profile_id,
            'invoice_no' => 'FRINV' . str_pad((string) ((FranchiseInvoice::max('id') ?? 0) + 1), 6, '0', STR_PAD_LEFT),
            'amount' => $request->amount,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        $notify[] = ['success', 'Franchise invoice created successfully'];
        return back()->withNotify($notify);
    }
}
