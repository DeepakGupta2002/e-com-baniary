@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="card custom--card">
        <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <h5 class="mb-0">@lang('Rank Reward History')</h5>
            <div class="d-flex flex-wrap gap-3">
                <span class="badge badge--primary">@lang('Current Rank'): {{ getCurrentRankName(auth()->user()) }}</span>
                <span class="badge badge--info">@lang('Total Team DP'): {{ getAmount(auth()->user()->total_team_dp) }}</span>
                <span class="badge badge--success">@lang('Total Rank Reward'): {{ showAmount(auth()->user()->total_rank_reward) }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--responsive--md">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('Rank')</th>
                            <th>@lang('Team DP')</th>
                            <th>@lang('Reward')</th>
                            <th>@lang('Status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ showDateTime($log->created_at) }}</td>
                                <td>{{ __($log->rank?->name) }}</td>
                                <td>{{ getAmount($log->team_dp) }}</td>
                                <td>{{ showAmount($log->reward_amount) }}</td>
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
