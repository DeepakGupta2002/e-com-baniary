@php
    $prefix = $prefix ?? '';
    $currentUser = auth()->user();
@endphp

<div class="row gy-3">
    <div class="col-md-6">
        <label class="form--label">@lang('Full Name')</label>
        <input class="form-control form--control" name="name" type="text" value="{{ old('name', $address->name ?? $currentUser->fullname) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form--label">@lang('Mobile')</label>
        <input class="form-control form--control" name="mobile" type="text" value="{{ old('mobile', $address->mobile ?? $currentUser->mobileNumber) }}" required>
    </div>
    <div class="col-12">
        <label class="form--label">@lang('Address')</label>
        <textarea class="form-control form--control" name="address" rows="3" required>{{ old('address', $address->address ?? $currentUser->address) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form--label">@lang('City')</label>
        <input class="form-control form--control" name="city" type="text" value="{{ old('city', $address->city ?? $currentUser->city) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form--label">@lang('State')</label>
        <input class="form-control form--control" name="state" type="text" value="{{ old('state', $address->state ?? $currentUser->state) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form--label">@lang('Zip Code')</label>
        <input class="form-control form--control" name="zip" type="text" value="{{ old('zip', $address->zip ?? $currentUser->zip) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form--label">@lang('Country')</label>
        <input class="form-control form--control" name="country" type="text" value="{{ old('country', $address->country ?? $currentUser->country_name) }}" required>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" id="{{ $prefix }}is_default" name="is_default" type="checkbox" value="1"
                @checked(old('is_default', $address->is_default ?? false))>
            <label class="form-check-label" for="{{ $prefix }}is_default">@lang('Set as default delivery address')</label>
        </div>
    </div>
</div>
