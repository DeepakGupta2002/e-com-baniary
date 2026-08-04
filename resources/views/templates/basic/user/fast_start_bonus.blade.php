@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="card custom--card">
        <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <h5 class="mb-0">@lang('Fast Start Bonus History')</h5>
            <span class="badge badge--success">@lang('Total Fast Start Bonus'): {{ showAmount(auth()->user()->fast_start_bonus_amount) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--responsive--md">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('Bonus Amount')</th>
                            <th>@lang('Qualifying Type')</th>
                            <th>@lang('Transaction ID')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Remark')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ showDateTime($log->created_at) }}</td>
                                <td>{{ showAmount($log->bonus_amount) }}</td>
                                <td>{{ __($log->qualifyingTypeText) }}</td>
                                <td>{{ $log->transaction?->trx ?? 'N/A' }}</td>
                                <td>@php echo $log->statusBadge; @endphp</td>
                                <td>{{ __($log->transaction?->details ?? 'Fast Start Bonus credited after qualifying within 15-day window.') }}</td>
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
