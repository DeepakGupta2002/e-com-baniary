@extends('admin.layouts.app')

@section('panel')
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">@lang('Create Franchise Invoice')</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.franchise.invoices.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>@lang('Franchise User')</label>
                            <select name="franchise_profile_id" class="form-control" required>
                                <option value="">@lang('Select User')</option>
                                @foreach($profiles as $profile)
                                    <option value="{{ $profile->id }}">{{ $profile->user?->username }} ({{ $profile->franchise_code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('Amount')</label>
                            <input type="number" name="amount" class="form-control" step="any" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>@lang('Title')</label>
                            <input type="text" name="title" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('Description')</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn--primary w-100">@lang('Create Invoice')</button>
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
                            <th>@lang('Invoice')</th>
                            <th>@lang('User')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Date')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_no }}</td>
                                <td>{{ $invoice->profile?->user?->username }}</td>
                                <td>{{ showAmount($invoice->amount) }}</td>
                                <td>@php echo $invoice->statusBadge; @endphp</td>
                                <td>{{ showDateTime($invoice->created_at) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="100%" class="text-center text-muted">@lang('No data found')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer">{{ paginateLinks($invoices) }}</div>
        @endif
    </div>
@endsection
