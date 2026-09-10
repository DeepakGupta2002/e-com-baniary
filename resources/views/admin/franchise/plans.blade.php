@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Add Franchise Plan')</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.franchise.plans.store') }}">
                        @csrf
                        <div class="form-group">
                            <label>@lang('Name')</label>
                            <input class="form-control" name="name" type="text" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('Amount')</label>
                            <div class="input-group">
                                <input class="form-control" name="amount" type="number" step="any" min="0" required>
                                <span class="input-group-text">{{ gs('cur_text') }}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>@lang('Plan Commission')</label>
                            <div class="input-group">
                                <input class="form-control" name="direct_commission" type="number" step="any" min="0" max="100" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>@lang('Sort Order')</label>
                            <input class="form-control" name="sort_order" type="number" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label>@lang('Status')</label>
                            <select class="form-control" name="status" required>
                                <option value="1">@lang('Active')</option>
                                <option value="0">@lang('Inactive')</option>
                            </select>
                        </div>
                        <button class="btn btn--primary w-100" type="submit">@lang('Save')</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--lg table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Plan Commission')</th>
                                    <th>@lang('Sort')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>
                                        <td>{{ __($plan->name) }}</td>
                                        <td>{{ showAmount($plan->amount) }}</td>
                                        <td>{{ getAmount($plan->direct_commission) }}%</td>
                                        <td>{{ $plan->sort_order }}</td>
                                        <td>
                                            @if($plan->status)
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @else
                                                <span class="badge badge--warning">@lang('Inactive')</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline--primary editBtn"
                                                data-action="{{ route('admin.franchise.plans.store', $plan->id) }}"
                                                data-name="{{ $plan->name }}"
                                                data-amount="{{ getAmount($plan->amount) }}"
                                                data-direct_commission="{{ getAmount($plan->direct_commission) }}"
                                                data-sort_order="{{ $plan->sort_order }}"
                                                data-status="{{ $plan->status }}">
                                                @lang('Edit')
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="100%" class="text-center text-muted">@lang('No data found')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($plans->hasPages())
                    <div class="card-footer">{{ paginateLinks($plans) }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Edit Franchise Plan')</h5>
                        <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Name')</label>
                            <input class="form-control" name="name" type="text" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('Amount')</label>
                            <input class="form-control" name="amount" type="number" step="any" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('Plan Commission')</label>
                            <input class="form-control" name="direct_commission" type="number" step="any" min="0" max="100" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('Sort Order')</label>
                            <input class="form-control" name="sort_order" type="number" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('Status')</label>
                            <select class="form-control" name="status" required>
                                <option value="1">@lang('Active')</option>
                                <option value="0">@lang('Inactive')</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--primary w-100" type="submit">@lang('Update')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function($) {
            'use strict';
            $('.editBtn').on('click', function() {
                const modal = $('#editModal');
                modal.find('form').attr('action', $(this).data('action'));
                modal.find('[name=name]').val($(this).data('name'));
                modal.find('[name=amount]').val($(this).data('amount'));
                modal.find('[name=direct_commission]').val($(this).data('direct_commission'));
                modal.find('[name=sort_order]').val($(this).data('sort_order'));
                modal.find('[name=status]').val($(this).data('status'));
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
