@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="notice"></div>
                @php
                    $kyc = getContent('kyc.content', true);
                @endphp
                @if (auth()->user()->kv == Status::KYC_UNVERIFIED && auth()->user()->kyc_rejection_reason)
                    <div class="alert alert--danger" role="alert">
                        <div class="alert__icon"><i class="fas fa-file-signature"></i></div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('KYC Documents Rejected')</span><br>
                            <small>
                                <i>
                                    {{ __(@$kyc->data_values->reject) }}
                                    <a class="link-color text--base" data-bs-toggle="modal" data-bs-target="#kycRejectionReason"
                                        href="javascript::void(0)">@lang('Click here')</a> @lang('to show the reason').
                                    <a class="link-color text--base" href="{{ route('user.kyc.form') }}">@lang('Click Here')</a>
                                    @lang('to Re-submit Documents'). <br>

                                    <a class="link-color text--base mt-2" href="{{ route('user.kyc.data') }}">@lang('See KYC Data')</a>
                                </i>
                            </small>
                        </p>
                    </div>
                @elseif (auth()->user()->kv == Status::KYC_UNVERIFIED)
                    <div class="alert alert--info" role="alert">
                        <div class="alert__icon"><i class="fas fa-file-signature"></i></div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('KYC Verification Required')</span><br>
                            <small>
                                <i>
                                    {{ __(@$kyc->data_values->required) }}
                                    <a class="link-color text--base" href="{{ route('user.kyc.form') }}">@lang('Click here')</a>
                                    @lang('to submit KYC information').
                                </i>
                            </small>
                        </p>
                    </div>
                @elseif(auth()->user()->kv == Status::KYC_PENDING)
                    <div class="alert alert--warning" role="alert">
                        <div class="alert__icon"><i class="fas fa-user-check"></i></div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('KYC Verification Pending')</span><br>
                            <small>
                                <i>
                                    {{ __(@$kyc->data_values->pending) }}
                                    <a class="link-color text--base" href="{{ route('user.kyc.data') }}">@lang('Click here')</a> @lang('to see your submitted information')
                                </i>
                            </small>
                        </p>
                    </div>
                @endif

                @if (gs('notice'))
                    <div class="col-lg-12 col-sm-6 mt-4">
                        <div class="card notice--card custom--card">
                            <div class="card-header">
                                <h5 class="pb-2">@lang('Notice')</h5>
                            </div>
                            <div class="card-body">
                                @if (gs('notice'))
                                    <p class="notice-text-inner">@php echo gs('notice') @endphp</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if (gs('free_user_notice'))
                    <div class="col-lg-12 col-sm-6 mt-4">
                        <div class="card notice--card custom--card">
                            <div class="card-header">
                                <h5 class="pb-1">@lang('Free User Notice')</h5>
                            </div>
                            <div class="card-body">
                                @if (gs('free_user_notice') != null)
                                    <p class="notice-text-inner"> @php echo gs('free_user_notice'); @endphp </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if($fastStartWindow['status'] === 'active')
                    <div class="fast-start-alert fast-start-alert--timer-only mt-4" role="button" data-bs-toggle="modal" data-bs-target="#fastStartInfoModal" aria-label="@lang('View Fast Start Bonus details')">
                        <div class="fast-start-alert__ring">
                            <div class="fast-start-alert__ring-inner">
                                <span>@lang('Left')</span>
                                <strong class="fast-start-countdown" data-seconds-left="{{ $fastStartWindow['seconds_left'] }}">
                                    @lang('Calculating...')
                                </strong>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="row justify-content-center g-3">
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Current Balance')</h6>
                                <h3 class="ammount theme-two">{{ showAmount(auth()->user()->balance) }}</h3>
                            </div>
                            <div class="right-content">
                                <div class="icon"><i class="flaticon-wallet"></i></div>
                            </div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">
                                    @lang('Current Plan')
                                </h6>
                                <h3 class="ammount">
                                    @if (auth()->user()->plan)
                                        <span>{{ auth()->user()->plan->name }}</span>
                                    @else
                                        <span class="text--danger">@lang('N/A')</span>
                                    @endif
                                </h3>
                            </div>
                            <div class="right-content">
                                <div class="icon"><i class="las la-paper-plane"></i></div>
                            </div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Total Deposit')</h6>
                                <h3 class="ammount text--base">{{ showAmount($totalDeposit) }}</h3>
                            </div>
                            <div class="icon"><i class="flaticon-save-money"></i></div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Total Withdraw')</h6>
                                <h3 class="ammount theme-one">{{ showAmount($totalWithdraw) }}</h3>
                            </div>
                            <div class="icon"><i class="flaticon-withdraw"></i></div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Complete Withdraw')</h6>
                                <h3 class="ammount theme-two">{{ getAmount($completeWithdraw) }}</h3>
                            </div>
                            <div class="right-content">
                                <div class="icon"><i class="flaticon-wallet"></i></div>
                            </div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Pending Withdraw')</h6>
                                <h3 class="ammount text--base">{{ getAmount($pendingWithdraw) }}</h3>
                            </div>
                            <div class="icon"><i class="flaticon-withdrawal"></i></div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Total Invest')</h6>
                                <h3 class="ammount theme-one">{{ showAmount(auth()->user()->total_invest) }}</h3>
                            </div>
                            <div class="icon"><i class="flaticon-tag-1"></i></div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Total Referral Commission')</h6>
                                <h3 class="ammount theme-one">{{ showAmount(auth()->user()->total_ref_com) }}</h3>
                            </div>
                            <div class="icon"><i class="flaticon-clipboards"></i></div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Total Binary Commission')</h6>
                                <h3 class="ammount theme-one">{{ showAmount(auth()->user()->total_binary_com) }}</h3>
                                <small>@lang('Left BV'): {{ getAmount($binaryMatchingStatus['left_bv']) }} | @lang('Right BV'): {{ getAmount($binaryMatchingStatus['right_bv']) }}</small><br>
                                <small>
                                    @lang('Status'):
                                    @if($binaryMatchingStatus['payout_status'] === 'active')
                                        <span class="text--success">@lang('ACTIVE')</span>
                                    @elseif($binaryMatchingStatus['payout_status'] === 'waiting_bv')
                                        <span class="text--info">@lang('WAITING BV')</span>
                                    @else
                                        <span class="text--warning">@lang('BLOCKED')</span>
                                    @endif
                                </small>
                                @if($binaryMatchingStatus['payout_status'] === 'waiting_bv')
                                    <br>
                                    <small class="text--info">@lang('Eligible, waiting for BV on both legs')</small>
                                @elseif($binaryMatchingStatus['status'] === 'blocked')
                                    <br>
                                    <small class="text--warning">@lang('Reason'): @lang('Paid direct referrals missing on both legs')</small><br>
                                    <button class="btn btn--base btn-sm mt-2 py-1 px-2" type="button" data-bs-toggle="modal" data-bs-target="#binaryMatchingHoldModal">
                                        @lang('View Details')
                                    </button>
                                @endif
                            </div>
                            <div class="icon"><i class="flaticon-money-bag"></i></div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Total Level Income')</h6>
                                <h3 class="ammount theme-one">{{ showAmount(auth()->user()->total_level_income) }}</h3>
                            </div>
                            <div class="icon"><i class="las la-layer-group"></i></div>
                        </div>
                        <div class="dashboard-item-body">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Fast Start Bonus')</h6>
                                <h3 class="ammount theme-one">{{ showAmount(auth()->user()->fast_start_bonus_amount) }}</h3>
                                @if($fastStartWindow['status'] === 'active')
                                    <small>@lang('Window Ends'): {{ showDateTime($fastStartWindow['expires_at']) }}</small><br>
                                    <small>
                                        @lang('Time Left'):
                                        <span class="fast-start-countdown text--success" data-seconds-left="{{ $fastStartWindow['seconds_left'] }}">
                                            @lang('Calculating...')
                                        </span>
                                    </small>
                                @elseif($fastStartWindow['status'] === 'claimed')
                                    <small class="text--success">@lang('Fast Start Bonus earned')</small>
                                @elseif($fastStartWindow['status'] === 'expired')
                                    <small class="text--danger">@lang('Fast Start Bonus window expired')</small><br>
                                    <small>@lang('Expired On'): {{ showDateTime($fastStartWindow['expires_at']) }}</small>
                                @else
                                    <small class="text--warning">@lang('Activate a plan to start eligibility')</small>
                                @endif
                            </div>
                            <div class="icon"><i class="las la-bolt"></i></div>
                        </div>
                        <div class="dashboard-item-body"></div>
                    </div>
                </div>
                @php
                    $leaderGrowthTarget = \App\Services\LeaderGrowthBonusService::TARGET_BUSINESS;
                    $leaderGrowthBusiness = (float) auth()->user()->leader_growth_current_business;
                @endphp
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Leader Growth Bonus')</h6>
                                <h3 class="ammount theme-one">{{ showAmount(auth()->user()->leader_growth_total_bonus) }}</h3>
                                @if($pendingLeaderGrowthBonus)
                                    <small>@lang('Pending Payout'): {{ showAmount($pendingLeaderGrowthBonus->bonus_amount) }}</small><br>
                                    <small>@lang('Payout Date'): {{ showDateTime($pendingLeaderGrowthBonus->cycle_end) }}</small>
                                @else
                                    <small>@lang('Progress'): {{ showAmount($leaderGrowthBusiness) }} / {{ showAmount($leaderGrowthTarget) }}</small>
                                @endif
                            </div>
                            <div class="icon"><i class="las la-chart-line"></i></div>
                        </div>
                        <div class="dashboard-item-body"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Repurchase Matching Income')</h6>
                                <h3 class="ammount theme-one">{{ showAmount(auth()->user()->total_repurchase_matching_income) }}</h3>
                                <small>@lang('Left BV'): {{ getAmount(auth()->user()->repurchase_left_bv) }} | @lang('Right BV'): {{ getAmount(auth()->user()->repurchase_right_bv) }}</small><br>
                                <small>@lang('Settlement'): @lang('Month End')</small>
                            </div>
                            <div class="icon"><i class="las la-sync"></i></div>
                        </div>
                        <div class="dashboard-item-body"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Current Rank')</h6>
                                <h3 class="ammount theme-one">{{ getCurrentRankName(auth()->user()) }}</h3>
                            </div>
                            <div class="icon"><i class="las la-award"></i></div>
                        </div>
                        <div class="dashboard-item-body"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Total Team BV')</h6>
                                <h3 class="ammount theme-one">{{ getAmount(auth()->user()->total_team_dp) }}</h3>
                            </div>
                            <div class="icon"><i class="las la-sitemap"></i></div>
                        </div>
                        <div class="dashboard-item-body"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                    <div class="dashboard-item">
                        <div class="dashboard-item-header">
                            <div class="header-left">
                                <h6 class="title">@lang('Total Rank Reward')</h6>
                                <h3 class="ammount theme-one">{{ showAmount(auth()->user()->total_rank_reward) }}</h3>
                            </div>
                            <div class="icon"><i class="las la-trophy"></i></div>
                        </div>
                        <div class="dashboard-item-body"></div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card custom--card">
                        <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
                            <h5 class="mb-0">@lang('Rank Reward Roadmap')</h5>
                            <span class="badge badge--info">@lang('Your Team BV'): {{ getAmount(auth()->user()->total_team_dp) }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table--responsive--md">
                                    <thead>
                                        <tr>
                                            <th>@lang('Rank')</th>
                                            <th>@lang('Required BV')</th>
                                            <th>@lang('Reward')</th>
                                            <th>@lang('Status')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $teamDp = (float) auth()->user()->total_team_dp;
                                            $currentRankShown = false;
                                        @endphp
                                        @forelse($ranks as $rank)
                                            @php
                                                $isAchieved = $teamDp >= (float) $rank->required_team_dp;
                                                $isCurrent = !$isAchieved && !$currentRankShown;
                                                if ($isCurrent) {
                                                    $currentRankShown = true;
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ __($rank->name) }}</td>
                                                <td>{{ getAmount($rank->required_team_dp) }}</td>
                                                <td>{{ showAmount($rank->reward_amount) }}</td>
                                                <td>
                                                    @if ($isAchieved)
                                                        <span class="badge badge--success">@lang('Achieved')</span>
                                                    @elseif ($isCurrent)
                                                        <span class="badge badge--warning">@lang('Current')</span>
                                                    @else
                                                        <span class="badge badge--dark">@lang('Locked')</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="100%" class="text-center text-muted">@lang('No rank found')</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@if (auth()->user()->kv == Status::KYC_UNVERIFIED && auth()->user()->kyc_rejection_reason)
    <div class="modal fade" id="kycRejectionReason">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('KYC Document Rejection Reason')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ auth()->user()->kyc_rejection_reason }}</p>
                </div>
            </div>
        </div>
    </div>
@endif

@if ($binaryMatchingStatus['status'] === 'blocked')
    <div class="modal fade" id="binaryMatchingHoldModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Matching Income On Hold')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        @lang('Matching income is currently on hold. You must have at least one paid direct referral in your left leg and one paid direct referral in your right leg.')
                    </p>
                    <p class="mb-0">
                        @lang('Your business volume (BV) is safe and will be paid once both sides are active.')
                    </p>
                </div>
            </div>
        </div>
    </div>
@endif

@if($fastStartWindow['status'] === 'active')
    <div class="modal fade" id="fastStartInfoModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content fast-start-modal">
                <div class="modal-header fast-start-modal__header">
                    <div>
                        <span class="fast-start-modal__eyebrow">@lang('Limited Time Window')</span>
                        <h5 class="modal-title mb-0">@lang('Fast Start Bonus Eligibility')</h5>
                    </div>
                    <button type="button" class="fast-start-modal__close" data-bs-dismiss="modal" aria-label="@lang('Close')">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="fast-start-modal__intro">
                        @lang('Your 15-day Fast Start Bonus window is active. Complete the required direct Premium/Royal combination before the countdown ends.')
                    </p>
                    <div class="fast-start-modal__deadline">
                        <span>@lang('Window Ends')</span>
                        <strong>{{ showDateTime($fastStartWindow['expires_at']) }}</strong>
                    </div>
                    <div class="fast-start-modal__grid">
                        <div class="fast-start-modal__combo">
                            <span>@lang('Premium + Premium')</span>
                            <strong>{{ showAmount(3000) }}</strong>
                        </div>
                        <div class="fast-start-modal__combo">
                            <span>@lang('Premium + Royal')</span>
                            <strong>{{ showAmount(3000) }}</strong>
                        </div>
                        <div class="fast-start-modal__combo is-highlight">
                            <span>@lang('Royal + Royal')</span>
                            <strong>{{ showAmount(6000) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
                    <a class="btn btn--base btn-sm" href="{{ route('user.fast.start.bonus.index') }}">@lang('View History')</a>
                </div>
            </div>
        </div>
    </div>
@endif

@push('style')
    <style>
        .fast-start-alert {
            align-items: center;
            background:
                radial-gradient(circle at 12% 16%, rgba(255, 255, 255, 0.9) 0 0, transparent 170px),
                linear-gradient(135deg, hsl(var(--main) / 0.14), rgba(255, 183, 3, 0.18));
            border: 1px solid hsl(var(--main) / 0.22);
            border-radius: 22px;
            box-shadow: 0 18px 42px rgba(13, 74, 199, 0.12);
            cursor: pointer;
            display: flex;
            gap: 22px;
            overflow: hidden;
            padding: 20px;
            position: relative;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .fast-start-alert::after {
            background: linear-gradient(135deg, transparent, rgba(255, 255, 255, .42), transparent);
            content: '';
            height: 180%;
            position: absolute;
            right: -86px;
            top: -60px;
            transform: rotate(18deg);
            width: 70px;
        }

        .fast-start-alert:hover {
            border-color: hsl(var(--main) / 0.38);
            box-shadow: 0 24px 54px rgba(13, 74, 199, 0.16);
            transform: translateY(-2px);
        }

        .fast-start-alert--timer-only {
            justify-content: center;
            margin-left: auto;
            margin-right: auto;
            max-width: 220px;
            padding: 18px;
        }

        .fast-start-alert__ring {
            align-items: center;
            background: conic-gradient(hsl(var(--main)) 0 76%, rgba(255, 255, 255, .68) 76% 100%);
            border-radius: 50%;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .7), 0 14px 30px rgba(13, 74, 199, .16);
            display: flex;
            flex: 0 0 142px;
            height: 142px;
            justify-content: center;
            padding: 8px;
            position: relative;
            z-index: 1;
        }

        .fast-start-alert__ring-inner {
            align-items: center;
            background: #fff;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: center;
            padding: 16px;
            text-align: center;
            width: 100%;
        }

        .fast-start-alert__ring-inner span {
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .fast-start-alert__ring-inner strong {
            color: hsl(var(--main));
            display: block;
            font-size: 18px;
            line-height: 1.25;
            margin-top: 6px;
        }

        .fast-start-alert__content {
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .fast-start-alert__eyebrow {
            color: hsl(var(--main));
            display: block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .fast-start-alert__title {
            margin-bottom: 5px;
        }

        .fast-start-alert__text {
            margin-bottom: 10px;
        }

        .fast-start-alert__link {
            color: hsl(var(--main));
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .fast-start-modal {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
        }

        .fast-start-modal__header {
            background:
                radial-gradient(circle at 88% 10%, rgba(255, 255, 255, .34) 0 0, transparent 115px),
                linear-gradient(135deg, hsl(var(--main)), #12215c);
            color: #fff;
            padding: 22px 24px;
        }

        .fast-start-modal__close {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            color: #fff;
            display: inline-flex;
            flex: 0 0 34px;
            font-size: 20px;
            height: 34px;
            justify-content: center;
            line-height: 1;
            transition: background .2s ease, transform .2s ease;
            width: 34px;
        }

        .fast-start-modal__close:hover {
            background: rgba(255, 255, 255, .24);
            color: #fff;
            transform: rotate(90deg);
        }

        .fast-start-modal__eyebrow {
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            margin-bottom: 6px;
            opacity: .78;
            text-transform: uppercase;
        }

        .fast-start-modal__intro {
            color: #4b5563;
            margin-bottom: 16px;
        }

        .fast-start-modal__deadline {
            background: linear-gradient(135deg, rgba(255, 183, 3, .17), hsl(var(--main) / .08));
            border: 1px solid rgba(255, 183, 3, .26);
            border-radius: 16px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 16px;
            padding: 14px 16px;
        }

        .fast-start-modal__deadline span {
            color: #6b7280;
            font-size: 13px;
        }

        .fast-start-modal__deadline strong {
            color: #111827;
            text-align: right;
        }

        .fast-start-modal__grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, 1fr);
        }

        .fast-start-modal__combo {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px;
        }

        .fast-start-modal__combo span {
            color: #6b7280;
            display: block;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .fast-start-modal__combo strong {
            color: #111827;
            display: block;
            font-size: 18px;
        }

        .fast-start-modal__combo.is-highlight {
            background: #111827;
            border-color: #111827;
        }

        .fast-start-modal__combo.is-highlight span,
        .fast-start-modal__combo.is-highlight strong {
            color: #fff;
        }

        @media (max-width: 767px) {
            .fast-start-alert {
                align-items: center;
                flex-direction: column;
                text-align: center;
            }

            .fast-start-alert__ring {
                flex-basis: 132px;
                height: 132px;
                width: 132px;
            }

            .fast-start-modal__deadline {
                flex-direction: column;
            }

            .fast-start-modal__deadline strong {
                text-align: left;
            }

            .fast-start-modal__grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function() {
            "use strict";

            const countdowns = document.querySelectorAll('.fast-start-countdown');
            if (!countdowns.length) {
                return;
            }

            const formatTimeLeft = (seconds) => {
                const days = Math.floor(seconds / 86400);
                const hours = Math.floor((seconds % 86400) / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const remainingSeconds = seconds % 60;

                return `${days}d ${hours}h ${minutes}m ${remainingSeconds}s`;
            };

            const updateCountdowns = () => {
                countdowns.forEach((countdown) => {
                    if (!countdown.dataset.startedAt) {
                        countdown.dataset.startedAt = Date.now();
                    }

                    const startedAt = Number(countdown.dataset.startedAt);
                    const initialSecondsLeft = parseInt(countdown.dataset.secondsLeft || '0', 10);
                    const elapsedSeconds = Math.floor((Date.now() - startedAt) / 1000);
                    const secondsLeft = Math.max(0, initialSecondsLeft - elapsedSeconds);

                    countdown.textContent = secondsLeft > 0 ? formatTimeLeft(secondsLeft) : '@lang('Expired')';
                    countdown.classList.toggle('text--success', secondsLeft > 0);
                    countdown.classList.toggle('text--danger', secondsLeft <= 0);
                });
            };

            updateCountdowns();
            setInterval(updateCountdowns, 1000);

            const fastStartModal = document.getElementById('fastStartInfoModal');
            if (fastStartModal && window.bootstrap) {
                let autoCloseTimer = null;

                fastStartModal.addEventListener('shown.bs.modal', () => {
                    clearTimeout(autoCloseTimer);
                    autoCloseTimer = setTimeout(() => {
                        bootstrap.Modal.getOrCreateInstance(fastStartModal).hide();
                    }, 20000);
                });

                fastStartModal.addEventListener('hidden.bs.modal', () => {
                    clearTimeout(autoCloseTimer);
                    autoCloseTimer = null;
                });
            }
        })();
    </script>
@endpush
