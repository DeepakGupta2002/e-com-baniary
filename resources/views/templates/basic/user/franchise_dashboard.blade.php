@extends($activeTemplate . 'layouts.franchise_master')

@section('content')
    <div class="franchise-dashboard-summary row justify-content-center g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="dashboard-item">
                <div class="dashboard-item-header">
                    <div class="header-left">
                        <h6 class="title">@lang('Franchise Wallet')</h6>
                        <h3 class="ammount theme-two">{{ showAmount($profile->wallet_balance) }}</h3>
                    </div>
                    <div class="icon"><i class="flaticon-wallet"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="dashboard-item">
                <div class="dashboard-item-header">
                    <div class="header-left">
                        <h6 class="title">@lang('Direct Referrals')</h6>
                        <h3 class="ammount theme-one">{{ $profile->total_direct_referrals }}</h3>
                    </div>
                    <div class="icon"><i class="las la-user-friends"></i></div>
                </div>
            </div>
        </div>
        {{-- <div class="col-sm-6 col-xl-3">
            <div class="dashboard-item">
                <div class="dashboard-item-header">
                    <div class="header-left">
                        <h6 class="title">@lang('Referral Commission')</h6>
                        <h3 class="ammount text--base">{{ showAmount($profile->total_commission) }}</h3>
                    </div>
                    <div class="icon"><i class="las la-hand-holding-usd"></i></div>
                </div>
            </div>
        </div> --}}
        {{-- <div class="col-sm-6 col-xl-3">
            <div class="dashboard-item">
                <div class="dashboard-item-header">
                    <div class="header-left">
                        <h6 class="title">@lang('Total Invoices')</h6>
                        <h3 class="ammount">{{ $profile->invoices()->count() }}</h3>
                    </div>
                    <div class="icon"><i class="las la-file-invoice"></i></div>
                </div>
            </div>
        </div> --}}
    </div>

    <div class="row g-4">
        {{-- <div class="col-lg-6">
            <div class="card custom--card h-100">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Referral Link')</h5>
                </div>
                <div class="card-body">
                    <input type="text" class="form-control mb-3" readonly value="{{ $referralLink }}">
                    <p class="mb-0">@lang('Share this franchise link to invite new franchise applicants under your account.')</p>
                </div>
            </div>
        </div> --}}
        {{-- <div class="col-lg-6">
            <div class="card custom--card h-100">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Franchise Account')</h5>
                </div>
                <div class="card-body">
                    <p><strong>@lang('Franchise Code'):</strong> {{ $profile->franchise_code }}</p>
                    <p><strong>@lang('Franchise Plan'):</strong> {{ $profile->plan?->name ?? 'N/A' }}</p>
                    <p><strong>@lang('Application Amount'):</strong> {{ showAmount($profile->application_amount) }}</p>
                    <p><strong>@lang('Activated At'):</strong> {{ showDateTime($profile->activated_at) }}</p>
                    <p><strong>@lang('Status'):</strong> @php echo $profile->statusBadge; @endphp</p>
                </div>
            </div>
        </div> --}}
        {{-- <div class="col-lg-6">
            <div class="card custom--card h-100">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Recent Invoices')</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>@lang('Invoice')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_no }}</td>
                                        <td>{{ showAmount($invoice->amount) }}</td>
                                        <td>@php echo $invoice->statusBadge; @endphp</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">@lang('No invoices found')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> --}}
        {{-- <div class="col-lg-6">
            <div class="card custom--card h-100">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Recent Transactions')</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Type')</th>
                                    <th>@lang('Amount')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>{{ showDateTime($transaction->created_at) }}</td>
                                        <td>{{ $transaction->remark }}</td>
                                        <td>{{ $transaction->trx_type }} {{ showAmount($transaction->amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">@lang('No transactions found')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>
@endsection

@push('style')
    <style>
        .franchise-dashboard-summary .dashboard-item {
            height: 100%;
            min-height: 142px;
        }

        .franchise-dashboard-summary .dashboard-item-header {
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .franchise-dashboard-summary .header-left {
            min-width: 0;
        }

        .franchise-dashboard-summary .title {
            line-height: 1.25;
        }

        .franchise-dashboard-summary .ammount {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .franchise-dashboard-summary .icon {
            flex: 0 0 auto;
        }

        @media (max-width: 575px) {
            .franchise-dashboard-summary {
                --bs-gutter-y: 12px;
            }

            .franchise-dashboard-summary .dashboard-item {
                min-height: 118px;
            }

            .franchise-dashboard-summary .dashboard-item-header {
                align-items: flex-start;
            }

            .franchise-dashboard-summary .ammount {
                font-size: 24px;
                line-height: 1.25;
            }

            .franchise-dashboard-summary .icon {
                height: 46px;
                width: 46px;
            }
        }
    </style>
@endpush
