@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="card custom--card">
        <div class="card-header">
            <h5 class="card-title mb-0">@lang('Repurchase Matching History')</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--responsive--md">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('Period')</th>
                            <th>@lang('Left BV')</th>
                            <th>@lang('Right BV')</th>
                            <th>@lang('Matched BV')</th>
                            <th>@lang('Income')</th>
                            <th>@lang('Carry Forward')</th>
                            <th>@lang('Transaction ID')</th>
                            <th>@lang('Status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ showDateTime($log->created_at) }}</td>
                                <td>
                                    @if($log->period_start && $log->period_end)
                                        {{ showDateTime($log->period_start, 'M Y') }}
                                    @else
                                        {{ $log->order_id }}
                                    @endif
                                </td>
                                <td>{{ getAmount($log->left_bv) }}</td>
                                <td>{{ getAmount($log->right_bv) }}</td>
                                <td>{{ getAmount($log->matched_bv) }}</td>
                                <td>{{ showAmount($log->income) }}</td>
                                <td>@lang('No')</td>
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
