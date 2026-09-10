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
                            <th>@lang('Sponsor')</th>
                            <th>@lang('Plan')</th>
                            <th>@lang('Amount')</th>
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
                                <td>@php echo $application->statusBadge; @endphp</td>
                                <td>
                                    @if($application->status === 'pending')
                                        <div class="button--group">
                                            <form method="POST" action="{{ route('admin.franchise.applications.approve', $application->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--success">@lang('Approve')</button>
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
