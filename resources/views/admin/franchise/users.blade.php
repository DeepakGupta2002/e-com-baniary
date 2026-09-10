@extends('admin.layouts.app')

@section('panel')
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive--lg table-responsive">
                <table class="table--light style--two table">
                    <thead>
                        <tr>
                            <th>@lang('User')</th>
                            <th>@lang('Franchise Code')</th>
                            <th>@lang('Plan')</th>
                            <th>@lang('Wallet')</th>
                            <th>@lang('Commission')</th>
                            <th>@lang('Direct Referrals')</th>
                            <th>@lang('Status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($profiles as $profile)
                            <tr>
                                <td>{{ $profile->user?->username }}</td>
                                <td>{{ $profile->franchise_code }}</td>
                                <td>{{ $profile->plan?->name ?? 'N/A' }}</td>
                                <td>{{ showAmount($profile->wallet_balance) }}</td>
                                <td>{{ showAmount($profile->total_commission) }}</td>
                                <td>{{ $profile->total_direct_referrals }}</td>
                                <td>@php echo $profile->statusBadge; @endphp</td>
                            </tr>
                        @empty
                            <tr><td colspan="100%" class="text-center text-muted">@lang('No data found')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($profiles->hasPages())
            <div class="card-footer">{{ paginateLinks($profiles) }}</div>
        @endif
    </div>
@endsection
