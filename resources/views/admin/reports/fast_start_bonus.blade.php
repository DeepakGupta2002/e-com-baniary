@extends('admin.layouts.app')

@section('panel')
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive--lg table-responsive">
                <table class="table--light style--two table">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('Username')</th>
                            <th>@lang('Sponsor')</th>
                            <th>@lang('Qualifying Type')</th>
                            <th>@lang('Bonus Amount')</th>
                            <th>@lang('Transaction ID')</th>
                            <th>@lang('Status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ showDateTime($log->created_at) }}</td>
                                <td>
                                    <span class="fw-bold">{{ $log->user?->username }}</span>
                                </td>
                                <td>{{ $log->sponsor?->username ?? 'N/A' }}</td>
                                <td>{{ __($log->qualifyingTypeText) }}</td>
                                <td>{{ showAmount($log->bonus_amount) }}</td>
                                <td>{{ $log->transaction?->trx ?? 'N/A' }}</td>
                                <td>@php echo $log->statusBadge; @endphp</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="text-center text-muted">@lang('No data found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($logs->hasPages())
            <div class="card-footer">
                {{ paginateLinks($logs) }}
            </div>
        @endif
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form dateSearch="yes" />
@endpush
