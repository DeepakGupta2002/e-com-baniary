@php
    $franchisePlans = \App\Models\FranchisePlan::active()->orderBy('sort_order')->orderBy('id')->get();
    $lowestFranchiseAmount = $franchisePlans->min('amount') ?? gs('franchise_apply_amount');
@endphp

<section class="padding-bottom">
    <style>
        .franchise-offer-card {
            height: 100%;
            border: 1px solid rgba(var(--base-rgb), 0.18);
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(var(--base-rgb), 0.08) 0%, rgba(255, 255, 255, 1) 100%);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.06);
        }

        .franchise-offer-card .card-body {
            padding: 30px;
        }

        .franchise-offer-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            background: rgba(var(--base-rgb), 0.14);
            color: var(--base);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .franchise-offer-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-top: 24px;
        }

        .franchise-mini-card {
            padding: 22px 20px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid rgba(var(--base-rgb), 0.12);
            height: 100%;
        }

        .franchise-mini-card h5 {
            margin-bottom: 10px;
        }

        .franchise-mini-card .amount {
            color: var(--base);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .franchise-mini-card p:last-child {
            margin-bottom: 0;
        }

        @media (max-width: 991px) {
            .franchise-offer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card franchise-offer-card">
                    <div class="card-body">
                        <div class="section-header text-center mb-4">
                            <span class="subtitle">@lang('Franchise Opportunity')</span>
                            <h2 class="title">@lang('Apply For ORIVA Franchise')</h2>
                        </div>
                        <div class="row gy-4 align-items-center">
                            <div class="col-lg-7">
                                <span class="franchise-offer-badge"><i class="las la-layer-group"></i>@lang('Single Login, Separate Franchise Module')</span>
                                <ul class="plan-info mb-0">
                                    <li class="active"><i class="las la-check-circle"></i>@lang('Single login with separate MLM and Franchise panels')</li>
                                    <li class="active"><i class="las la-check-circle"></i>@lang('Dedicated franchise wallet and separate franchise transactions')</li>
                                    <li class="active"><i class="las la-check-circle"></i>@lang('Franchise application amount is managed separately from MLM plan purchase')</li>
                                    <li class="active"><i class="las la-check-circle"></i>@lang('Sponsor receives fixed franchise referral commission')</li>
                                </ul>
                            </div>
                            <div class="col-lg-5">
                                <div class="card custom--card">
                                    <div class="card-body text-center">
                                        <h5 class="mb-2">@lang('Franchise Application')</h5>
                                        <h2 class="text--base mb-2">{{ showAmount($lowestFranchiseAmount) }}</h2>
                                        <p class="mb-3">@lang('Starting franchise onboarding amount')</p>
                                        @auth
                                            <a href="{{ route('user.franchise.apply') }}" class="cmn--btn-2 btn--md active"><span>@lang('Apply Now')</span></a>
                                        @else
                                            <a href="{{ route('user.login') }}" class="cmn--btn-2 btn--md active"><span>@lang('Login To Apply')</span></a>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="franchise-offer-grid">
                            @forelse($franchisePlans as $plan)
                                <div class="franchise-mini-card">
                                    <h5>{{ __($plan->name) }}</h5>
                                    <div class="amount">{{ showAmount($plan->amount) }}</div>
                                    <p class="mb-2"><strong>{{ getAmount($plan->direct_commission) }}%</strong> @lang('plan commission on activation')</p>
                                    <p>@lang('Amount plus commission is credited to separate franchise wallet after approval.')</p>
                                </div>
                            @empty
                                <div class="franchise-mini-card">
                                    <h5>@lang('Franchise Plan')</h5>
                                    <div class="amount">{{ showAmount($lowestFranchiseAmount) }}</div>
                                    <p>@lang('Admin can add franchise plans from the franchise module.')</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
