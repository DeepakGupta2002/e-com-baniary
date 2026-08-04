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
                                    <th>@lang('Required Team DP')</th>
                                    <th>@lang('Reward Amount')</th>
                                    <th>@lang('Sort Order')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ranks as $rank)
                                    <tr>
                                        <td>{{ __($rank->name) }}</td>
                                        <td>{{ getAmount($rank->required_team_dp) }}</td>
                                        <td>{{ showAmount($rank->reward_amount) }}</td>
                                        <td>{{ $rank->sort_order }}</td>
                                        <td>@php echo $rank->statusBadge; @endphp</td>
                                        <td>
                                            <div class="button--group">
                                                <button class="btn btn-outline--primary cuModalBtn btn-sm" data-modal_title="@lang('Update Rank')" data-resource="{{ $rank }}">
                                                    <i class="las la-pen"></i>@lang('Edit')
                                                </button>
                                                @if ($rank->status)
                                                    <button class="btn btn-outline--danger btn-sm confirmationBtn" data-question="@lang('Are you sure to disable this rank?')" data-action="{{ route('admin.rank.status', $rank->id) }}">
                                                        <i class="las la-eye-slash"></i>@lang('Disable')
                                                    </button>
                                                @else
                                                    <button class="btn btn-outline--success btn-sm confirmationBtn" data-question="@lang('Are you sure to enable this rank?')" data-action="{{ route('admin.rank.status', $rank->id) }}">
                                                        <i class="las la-eye"></i>@lang('Enable')
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
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
            </div>
        </div>
    </div>

    <div class="modal fade" id="cuModal" role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form method="post" action="{{ route('admin.rank.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-sm-12">
                                <label class="font-weight-bold">@lang('Name')</label>
                                <input class="form-control" name="name" type="text" required>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="font-weight-bold">@lang('Required Team DP')</label>
                                <input class="form-control" name="required_team_dp" type="number" step="any" min="0" required>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="font-weight-bold">@lang('Reward Amount')</label>
                                <input class="form-control" name="reward_amount" type="number" step="any" min="0" required>
                            </div>
                            <div class="form-group col-sm-4">
                                <label class="font-weight-bold">@lang('Sort Order')</label>
                                <input class="form-control" name="sort_order" type="number" min="0" required>
                            </div>
                            <div class="form-group col-sm-12">
                                <div class="form-check">
                                    <input class="form-check-input" id="status" name="status" type="checkbox" value="1" checked>
                                    <label class="form-check-label" for="status">@lang('Active')</label>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn--primary w-100" type="submit">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form />
    <button class="btn btn-outline--primary h-45 cuModalBtn" data-modal_title="@lang('Add New Rank')">
        <i class="las la-plus"></i>@lang('Add New')
    </button>
@endpush
