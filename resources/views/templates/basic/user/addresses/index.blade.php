@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="mb-0">@lang('Saved Delivery Addresses')</h5>
        <a class="btn btn--base btn-sm" href="{{ route('user.addresses.create') }}">@lang('Add New Address')</a>
    </div>

    <div class="row gy-3">
        @forelse($addresses as $address)
            <div class="col-md-6">
                <div class="card custom--card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2">
                            <h6 class="mb-1">{{ __($address->name) }}</h6>
                            @if ($address->is_default)
                                <span class="badge badge--success">@lang('Default')</span>
                            @endif
                        </div>
                        <p class="mb-1">{{ __($address->mobile) }}</p>
                        <p class="mb-3">{{ __($address->fullAddress()) }}</p>

                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-outline--primary btn-sm" href="{{ route('user.addresses.edit', $address->id) }}">@lang('Edit')</a>

                            @if (!$address->is_default)
                                <form action="{{ route('user.addresses.default', $address->id) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-outline--success btn-sm" type="submit">@lang('Set Default')</button>
                                </form>
                            @endif

                            <button class="btn btn-outline--danger btn-sm confirmationBtn" type="button"
                                data-action="{{ route('user.addresses.delete', $address->id) }}"
                                data-question="@lang('Are you sure to delete this address?')">
                                @lang('Delete')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card custom--card">
                    <div class="card-body text-center">
                        @lang('No delivery address added yet.')
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if ($addresses->hasPages())
        <div class="mt-4">
            {{ paginateLinks($addresses) }}
        </div>
    @endif

    <x-confirmation-modal base="true" />
@endsection
