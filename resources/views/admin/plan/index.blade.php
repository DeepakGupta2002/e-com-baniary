@extends('admin.layouts.app')
@section('panel')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--lg table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Business Volume (BV)')</th>
                                    <th>@lang('Referral Commission (%)')</th>
                                    <th>@lang('Matching Income (%)')</th>
                                    <th>@lang('Daily Capping')</th>
                                    <th>@lang('Monthly Capping')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>

                                        <td>{{ __($plan->name) }}</td>
                                        <td>{{ showAmount($plan->price) }}</td>
                                        <td>{{ $plan->bv }}</td>
                                        <td> {{ getAmount($plan->ref_com) }}%</td>
                                        <td> {{ getAmount($plan->tree_com) }}%</td>
                                        <td> {{ showAmount($plan->daily_capping) }}</td>
                                        <td> {{ showAmount($plan->monthly_capping) }}</td>
                                        <td>@php echo $plan->statusBadge @endphp </td>
                                        <td>
                                            <div class="button--group">
                                                <button class="btn btn-outline--primary cuModalBtn btn-sm" data-modal_title="@lang('Update Plan')"
                                                    data-resource="{{ $plan }}">
                                                    <i class="las la-pen"></i>@lang('Edit')
                                                </button>
                                                @if ($plan->status == Status::ENABLE)
                                                    <button class="btn btn-outline--danger btn-sm confirmationBtn" data-question="@lang('Are you sure to disable this plan?')"
                                                        data-action="{{ route('admin.plan.status', $plan->id) }}">
                                                        <i class="las la-eye-slash"></i>@lang('Disable')
                                                    </button>
                                                @else
                                                    <button class="btn btn-outline--success confirmationBtn btn-sm" data-question="@lang('Are you sure to enable this plan?')"
                                                        data-action="{{ route('admin.plan.status', $plan->id) }}">
                                                        <i class="las la-eye"></i>@lang('Enable')
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Edit modal --}}
    <div class="modal fade" id="cuModal" role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form method="post" action="{{ route('admin.plan.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-sm-12">
                                <label class="font-weight-bold"> @lang('Name')</label>
                                <input class="form-control" name="name" type="text" required>
                            </div>
                            <div class="form-group col-sm-6">
                                <label class="font-weight-bold"> @lang('Price') </label>
                                <div class="input-group">
                                    <div class="input-group">
                                        <span class="input-group-text">{{ gs('cur_sym') }} </span>
                                        <input class="form-control" name="price" type="number" step="any" required value="{{ old('price') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-sm-6">
                                <label class="font-weight-bold"> @lang('Business Volume (BV)')
                                    <span data-bs-toggle="tooltip" data-bs-title="@lang('If a user who subscribed to this plan, refers someone and if the referred user buys a plan, then he will get this amount')">
                                        <i class="las la-exclamation-circle"></i>
                                    </span>
                                </label>
                                <input class="form-control" name="bv" type="number" min="0" required value="{{ old('bv') }}">
                            </div>
                            <div class="form-group col-sm-6">
                                <label class="font-weight-bold"> @lang('Referral Commission (%)')
                                    <span data-bs-toggle="tooltip" data-bs-title="@lang('This percentage is applied to the purchased package amount and paid only to the direct sponsor.')">
                                        <i class="las la-exclamation-circle"></i>
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input class="form-control" name="ref_com" type="number" step="any" min="0" required value="{{ old('ref_com') }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="form-group col-sm-6">
                                <label class="font-weight-bold"> @lang('Matching Income (%)')
                                    <span data-bs-toggle="tooltip" data-bs-title="@lang('This percentage is used to calculate binary matching income from the matched BV of the active user.')">
                                        <i class="las la-exclamation-circle"></i>
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input class="form-control" name="tree_com" type="number" step="any" min="0" required value="{{ old('tree_com') }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="form-group col-sm-6">
                                <label class="font-weight-bold"> @lang('Daily Capping') </label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ gs('cur_sym') }} </span>
                                    <input class="form-control" name="daily_capping" type="number" step="any" min="0" required value="{{ old('daily_capping', 0) }}">
                                </div>
                            </div>
                            <div class="form-group col-sm-6">
                                <label class="font-weight-bold"> @lang('Monthly Capping') </label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ gs('cur_sym') }} </span>
                                    <input class="form-control" name="monthly_capping" type="number" step="any" min="0" required value="{{ old('monthly_capping', 0) }}">
                                </div>
                            </div>
                        </div>
                        <button class="btn-block btn btn--primary h-45 w-100" type="submit">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form />
    <button class="btn btn-outline--primary h-45 cuModalBtn" data-modal_title="@lang('Add New Plan')">
        <i class="las la-plus"></i>@lang('Add New')
    </button>
@endpush
