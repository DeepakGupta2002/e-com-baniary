@extends('admin.layouts.app')

@section('panel')
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive--lg table-responsive">
                <table class="table--light style--two table">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('User')</th>
                            <th>@lang('Rank')</th>
                            <th>@lang('Team DP')</th>
                            <th>@lang('Reward')</th>
                            <th>@lang('Transaction')</th>
                            <th>@lang('Status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ showDateTime($log->created_at) }}</td>
                                <td>{{ $log->user?->username }}</td>
                                <td>{{ __($log->rank?->name) }}</td>
                                <td>{{ getAmount($log->team_dp) }}</td>
                                <td>{{ showAmount($log->reward_amount) }}</td>
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
    <x-search-form />
@endpush
