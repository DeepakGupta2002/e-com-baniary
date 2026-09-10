@extends($activeTemplate . 'layouts.master')

@section('content')
    @php
        $selectedPlanId = old('franchise_plan_id', request('plan', $plans->first()?->id));
        $selectedPlan = $plans->firstWhere('id', (int) $selectedPlanId) ?: $plans->first();
        $franchiseApplyAmount = $selectedPlan?->amount ?? gs('franchise_apply_amount');
        $franchiseDirectCommission = $selectedPlan?->direct_commission ?? gs('franchise_direct_commission');
    @endphp

    <div class="card custom--card">
        <div class="card-header">
            <h5 class="mb-0">@lang('Franchise Application')</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Application Amount')</h6>
                                <h3 class="ammount theme-two">{{ showAmount($franchiseApplyAmount) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Franchise Status')</h6>
                                <h3 class="ammount">{{ hasActiveFranchise($user) ? 'Active' : 'Inactive' }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Latest Application')</h6>
                                <h3 class="ammount">{{ ucfirst($latestApplication->status ?? 'none') }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card custom--card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">@lang('Commission Type')</h6>
                            <h3 class="text--base mb-2">{{ getAmount($franchiseDirectCommission) }}%</h3>
                            <p class="mb-0">@lang('Sponsor commission is fixed from settings; selected plan commission is credited to applicant franchise wallet.')</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card custom--card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">@lang('Wallet Separation')</h6>
                            <h3 class="text--base mb-2">@lang('Separate')</h3>
                            <p class="mb-0">@lang('Franchise wallet, transfers and transactions remain completely independent from MLM wallet records.')</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card custom--card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">@lang('Approval Flow')</h6>
                            <h3 class="text--base mb-2">@lang('Admin Approval')</h3>
                            <p class="mb-0">@lang('Application is submitted first, then the dedicated franchise panel becomes active after approval.')</p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('user.franchise.apply.store') }}">
                @csrf
                <div class="row g-3 mb-4">
                    @forelse($plans as $plan)
                        <div class="col-md-4">
                            <label class="card custom--card h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <h5 class="mb-0">{{ __($plan->name) }}</h5>
                                        <input type="radio" name="franchise_plan_id" value="{{ $plan->id }}" @checked((int) $selectedPlanId === $plan->id) required>
                                    </div>
                                    <h3 class="text--base mb-2">{{ showAmount($plan->amount) }}</h3>
                                    <p class="mb-0">@lang('Plan commission'): <strong>{{ getAmount($plan->direct_commission) }}%</strong></p>
                                </div>
                            </label>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">@lang('No active franchise plan found. Please contact admin.')</div>
                        </div>
                    @endforelse
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('Franchise Sponsor') (@lang('Optional'))</label>
                            <input type="text" name="sponsor" class="form-control" value="{{ old('sponsor', session('franchise_reference')) }}" placeholder="@lang('Sponsor username or email')">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('Note')</label>
                            <textarea name="note" rows="4" class="form-control" placeholder="@lang('Add any note for admin')">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn--base w-100">@lang('Submit Franchise Application')</button>
            </form>
        </div>
    </div>
@endsection
