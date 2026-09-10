@extends($activeTemplate . 'layouts.franchise_master')

@section('content')
    <div class="card custom--card">
        <div class="card-header">
            <h5 class="mb-0">@lang('Franchise Wallet Transfer')</h5>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <strong>@lang('Available Franchise Wallet'):</strong> {{ showAmount($profile->wallet_balance) }}
            </div>
            <form method="POST" action="{{ route('user.franchise.transfer.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('User ID / Email')</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('Amount')</label>
                            <input type="number" name="amount" step="any" min="0" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('Remark')</label>
                            <input type="text" name="remark" class="form-control">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn--base w-100">@lang('Transfer Now')</button>
            </form>
        </div>
    </div>
@endsection
