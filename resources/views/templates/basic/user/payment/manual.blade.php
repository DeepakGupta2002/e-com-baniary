@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="card custom--card">
        <div class="card-body">
            <form class="disableSubmission" action="{{ route('user.deposit.manual.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="alert alert-primary">
                    <p class="mb-0"><i class="las la-info-circle"></i> @lang('You are requesting') <b>{{ showAmount($data['amount']) }}</b> @lang('to deposit.') @lang('Please pay')
                        <b>{{ showAmount($data['final_amount'], currencyFormat: false) . ' ' . $data['method_currency'] }} </b> @lang('for successful payment.')
                    </p>
                </div>

                <div class="text-center mb-4">
                    <p class="fw-bold mb-2">@lang('Scan QR code to make payment')</p>
                    <img src="{{ asset('assets/image.png') }}" alt="@lang('Payment QR code')"
                        class="img-fluid rounded border p-2" style="max-width: 420px; width: 100%;">
                </div>

                <div class="mb-3">@php echo  $data->gateway->description @endphp</div>

                <x-viser-form identifier="id" identifierValue="{{ $gateway->form_id }}" />
                    
                <button class="btn btn--base w-100" type="submit">@lang('Pay Now')</button>

            </form>
        </div>
    </div>
@endsection
