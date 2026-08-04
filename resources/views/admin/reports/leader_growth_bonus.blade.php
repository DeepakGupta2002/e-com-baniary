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
                            <th>@lang('Cycle No')</th>
                            <th>@lang('Cycle Start')</th>
                            <th>@lang('Cycle End')</th>
                            <th>@lang('Business')</th>
                            <th>@lang('Reward')</th>
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
                                <td>{{ $log->cycle_number }}</td>
                                <td>{{ $log->cycle_start ? showDateTime($log->cycle_start) : 'N/A' }}</td>
                                <td>{{ $log->cycle_end ? showDateTime($log->cycle_end) : 'N/A' }}</td>
                                <td>{{ showAmount($log->achieved_business) }}</td>
                                <td>{{ showAmount($log->bonus_amount) }}</td>
                                <td>{{ $log->walletTransaction?->trx ?? 'N/A' }}</td>
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
