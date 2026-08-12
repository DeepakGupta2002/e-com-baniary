@extends('admin.layouts.app')

@section('panel')
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive--lg table-responsive">
                <table class="table--light style--two table">
                    <thead>
                        <tr>
                            <th>@lang('Position')</th>
                            <th>@lang('Username')</th>
                            <th>@lang('Current Rank')</th>
                            <th>@lang('Total Team BV')</th>
                            <th>@lang('Total Rank Reward')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaders as $leader)
                            <tr>
                                <td>{{ $leaders->firstItem() + $loop->index }}</td>
                                <td><a href="{{ route('admin.users.detail', $leader->id) }}">{{ $leader->username }}</a></td>
                                <td>{{ getCurrentRankName($leader) }}</td>
                                <td>{{ getAmount($leader->total_team_dp) }}</td>
                                <td>{{ showAmount($leader->total_rank_reward) }}</td>
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
        @if ($leaders->hasPages())
            <div class="card-footer">
                {{ paginateLinks($leaders) }}
            </div>
        @endif
    </div>
@endsection
