@extends($activeTemplate . 'layouts.franchise_master')

@section('content')
    <div class="card custom--card">
        <div class="card-header">
            <h5 class="mb-0">@lang('Franchise Invoices')</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--responsive--md">
                    <thead>
                        <tr>
                            <th>@lang('Invoice')</th>
                            <th>@lang('Title')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Date')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_no }}</td>
                                <td>{{ $invoice->title ?: 'Franchise Invoice' }}</td>
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
