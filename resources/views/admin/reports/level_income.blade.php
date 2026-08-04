@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="show-filter mb-3 text-end">
                <button class="btn btn-outline--primary showFilterBtn btn-sm" type="button"><i class="las la-filter"></i> @lang('Filter')</button>
            </div>
            <div class="card responsive-filter-card mb-4">
                <div class="card-body">
                    <form>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="flex-grow-1">
                                <label>@lang('Receiver/Source')</label>
                                <input class="form-control" name="search" type="search" value="{{ request()->search }}" placeholder="@lang('Search username')">
                            </div>
                            <div class="flex-grow-1">
                                <label>@lang('Level')</label>
                                <select class="form-control select2" name="level_no" data-minimum-results-for-search="-1">
                                    <option value="">@lang('All')</option>
                                    @for ($i = 1; $i <= 10; $i++)
                                        <option value="{{ $i }}" @selected(request()->level_no == $i)>@lang('Level') {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <label>@lang('Date')</label>
                                <input class="datepicker-here form-control bg--white date-range pe-2" name="date" type="search"
                                    value="{{ request()->date }}" placeholder="@lang('Start Date - End Date')" autocomplete="off">
                            </div>
                            <div class="flex-grow-1 align-self-end">
                                <button class="btn btn--primary w-100 h-45"><i class="fas fa-filter"></i> @lang('Filter')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--lg table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Receiver')</th>
                                    <th>@lang('Source User')</th>
                                    <th>@lang('Level')</th>
                                    <th>@lang('Matching Income')</th>
                                    <th>@lang('Percentage')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Matching TRX')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>{{ showDateTime($log->created_at) }}<br>{{ diffForHumans($log->created_at) }}</td>
                                        <td>
                                            <span class="fw-bold">{{ $log->receiver->fullname ?? 'N/A' }}</span><br>
                                            <span class="small">{{ '@' . ($log->receiver->username ?? 'N/A') }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $log->source->fullname ?? 'N/A' }}</span><br>
                                            <span class="small">{{ '@' . ($log->source->username ?? 'N/A') }}</span>
                                        </td>
                                        <td>@lang('Level') {{ $log->level_no }}</td>
                                        <td>{{ showAmount($log->matching_income) }}</td>
                                        <td>{{ getAmount($log->percentage) }}%</td>
                                        <td>{{ showAmount($log->amount) }}</td>
                                        <td>@php echo $log->status_badge; @endphp</td>
                                        <td>{{ $log->matchingTransaction->trx ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __('No data found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($logs->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($logs) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
@endpush

@push('style-lib')
    <link type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}" rel="stylesheet">
@endpush

@push('script')
    <script>
        (function($) {
            "use strict"

            $('.date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                },
                showDropdowns: true,
                maxDate: moment()
            });

            $('.date-range').on('apply.daterangepicker', function(event, picker) {
                $(event.target).val(picker.startDate.format('MMMM DD, YYYY') + ' - ' + picker.endDate.format('MMMM DD, YYYY'));
            });

            if ($('.date-range').val()) {
                let dateRange = $('.date-range').val().split(' - ');
                $('.date-range').data('daterangepicker').setStartDate(new Date(dateRange[0]));
                $('.date-range').data('daterangepicker').setEndDate(new Date(dateRange[1]));
            }
        })(jQuery)
    </script>
@endpush
