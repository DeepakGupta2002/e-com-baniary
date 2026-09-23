@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4 mb-4">
        <div class="col-xxl-3 col-sm-6">
            <div class="franchise-report-card">
                <i class="las la-file-signature report-bg-icon text--primary"></i>
                <div class="report-icon bg--primary">
                    <i class="las la-file-signature"></i>
                </div>
                <div class="report-content">
                    <h3>{{ $report['total_applications'] }}</h3>
                    <p>@lang('Total Applications')</p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="franchise-report-card">
                <i class="las la-hourglass-half report-bg-icon text--warning"></i>
                <div class="report-icon bg--warning">
                    <i class="las la-hourglass-half"></i>
                </div>
                <div class="report-content">
                    <h3>{{ $report['pending_applications'] }}</h3>
                    <p>@lang('Pending Verification')</p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="franchise-report-card">
                <i class="las la-check-circle report-bg-icon text--success"></i>
                <div class="report-icon bg--success">
                    <i class="las la-check-circle"></i>
                </div>
                <div class="report-content">
                    <h3>{{ $report['approved_applications'] }}</h3>
                    <p>@lang('Approved Applications')</p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="franchise-report-card">
                <i class="las la-times-circle report-bg-icon text--danger"></i>
                <div class="report-icon bg--danger">
                    <i class="las la-times-circle"></i>
                </div>
                <div class="report-content">
                    <h3>{{ $report['rejected_applications'] }}</h3>
                    <p>@lang('Rejected Applications')</p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="franchise-report-card">
                <i class="las la-receipt report-bg-icon text--info"></i>
                <div class="report-icon bg--info">
                    <i class="las la-receipt"></i>
                </div>
                <div class="report-content">
                    <h3>{{ $report['verified_payments'] }}</h3>
                    <p>@lang('Verified Payments')</p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="franchise-report-card">
                <i class="las la-store report-bg-icon text--primary"></i>
                <div class="report-icon bg--primary">
                    <i class="las la-store"></i>
                </div>
                <div class="report-content">
                    <h3>{{ $report['active_franchise_users'] }}</h3>
                    <p>@lang('Active Franchise Users')</p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="franchise-report-card">
                <i class="las la-money-bill-wave report-bg-icon text--success"></i>
                <div class="report-icon bg--success">
                    <i class="las la-money-bill-wave"></i>
                </div>
                <div class="report-content">
                    <h3>{{ showAmount($report['total_application_amount']) }}</h3>
                    <p>@lang('Total Applied Amount')</p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="franchise-report-card">
                <i class="las la-wallet report-bg-icon text--success"></i>
                <div class="report-icon bg--success">
                    <i class="las la-wallet"></i>
                </div>
                <div class="report-content">
                    <h3>{{ showAmount($report['approved_application_amount']) }}</h3>
                    <p>@lang('Approved Amount')</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0">@lang('Franchise Application Report')</h5>
            <span class="text-muted">@lang('Total Apply'): {{ $report['total_applications'] }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive--lg table-responsive">
                <table class="table--light style--two table">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('User')</th>
                            <th>@lang('Sponsor')</th>
                            <th>@lang('Plan')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Payment')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $application)
                            <tr>
                                <td>{{ showDateTime($application->created_at) }}</td>
                                <td>{{ $application->user?->username }}</td>
                                <td>{{ $application->sponsor?->username ?? 'N/A' }}</td>
                                <td>{{ $application->plan?->name ?? 'N/A' }}</td>
                                <td>{{ showAmount($application->amount) }}</td>
                                <td>
                                    <div><strong>@lang('Method'):</strong> {{ __(ucwords(str_replace('_', ' ', $application->payment_method ?? 'N/A'))) }}</div>
                                    <div><strong>@lang('Ref'):</strong> {{ $application->payment_reference ?? 'N/A' }}</div>
                                    <div class="mt-1">@php echo $application->paymentStatusBadge; @endphp</div>
                                    @if($application->payment_proof)
                                        @php
                                            $proofPath = getFilePath('franchisePaymentProof') . '/' . $application->payment_proof;
                                        @endphp
                                        @if(file_exists(public_path($proofPath)))
                                            <a href="{{ asset($proofPath) }}" target="_blank" class="btn btn-sm btn-outline--primary mt-1">@lang('View Proof')</a>
                                        @else
                                            <span class="badge badge--danger mt-1">@lang('Proof Missing')</span>
                                        @endif
                                    @else
                                        <span class="badge badge--dark mt-1">@lang('No Proof')</span>
                                    @endif
                                </td>
                                <td>@php echo $application->statusBadge; @endphp</td>
                                <td>
                                    @if($application->status === 'pending')
                                        <div class="button--group">
                                            <form method="POST" action="{{ route('admin.franchise.applications.approve', $application->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--success">@lang('Verify & Approve')</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.franchise.applications.reject', $application->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--danger">@lang('Reject')</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="100%" class="text-center text-muted">@lang('No data found')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($applications->hasPages())
            <div class="card-footer">{{ paginateLinks($applications) }}</div>
        @endif
    </div>
@endsection

@push('style')
    <style>
        .franchise-report-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16px;
            min-height: 112px;
            padding: 20px;
            overflow: hidden;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        .franchise-report-card .report-bg-icon {
            position: absolute;
            right: -16px;
            bottom: -18px;
            font-size: 76px;
            opacity: .12;
            transform: rotate(-18deg);
        }

        .franchise-report-card .report-icon {
            position: relative;
            z-index: 1;
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            flex: 0 0 58px;
            border-radius: 10px;
            color: #fff;
        }

        .franchise-report-card .report-icon i {
            font-size: 32px;
        }

        .franchise-report-card .report-content {
            position: relative;
            z-index: 1;
        }

        .franchise-report-card .report-content h3 {
            margin-bottom: 4px;
            color: #1f2937;
            font-size: 24px;
            font-weight: 700;
        }

        .franchise-report-card .report-content p {
            margin-bottom: 0;
            color: #64748b;
            font-weight: 500;
        }
    </style>
@endpush
