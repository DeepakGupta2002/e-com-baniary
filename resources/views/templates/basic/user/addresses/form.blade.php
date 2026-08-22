@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="card custom--card">
        <div class="card-body">
            <form action="{{ $address ? route('user.addresses.update', $address->id) : route('user.addresses.store') }}" method="POST">
                @csrf
                <div class="row gy-3">
                    <div class="col-12">
                        @include($activeTemplate . 'user.addresses.partials.fields', ['address' => $address])
                    </div>
                    <div class="col-12">
                        <button class="btn btn--base w-100" type="submit">@lang('Save Address')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
