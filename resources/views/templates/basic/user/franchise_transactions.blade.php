@extends($activeTemplate . 'layouts.franchise_master')

@section('content')
    <div class="card custom--card">
        <div class="card-header">
            <h5 class="mb-0">@lang('Franchise Transactions')</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--responsive--md">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('TRX')</th>
                            <th>@lang('Remark')</th>
                            <th>@lang('Details')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Balance')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ showDateTime($transaction->created_at) }}</td>
                                <td>{{ $transaction->trx }}</td>
                                <td>{{ $transaction->remark }}</td>
                                <td>{{ $transaction->details }}</td>
                                <td>{{ $transaction->trx_type }} {{ showAmount($transaction->amount) }}</td>
                                <td>{{ showAmount($transaction->post_balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="100%" class="text-center text-muted">@lang('No data found')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer">{{ paginateLinks($transactions) }}</div>
        @endif
    </div>
@endsection
