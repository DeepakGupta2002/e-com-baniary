<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index()
    {
        $pageTitle = 'My Addresses';
        $addresses = UserAddress::where('user_id', auth()->id())->latest('is_default')->latest('id')->paginate(getPaginate());

        return view('Template::user.addresses.index', compact('pageTitle', 'addresses'));
    }

    public function create()
    {
        $pageTitle = 'Add Address';
        $address = null;

        return view('Template::user.addresses.form', compact('pageTitle', 'address'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedAddress($request);

        DB::transaction(function () use ($data) {
            $data['user_id'] = auth()->id();
            $data['is_default'] = $data['is_default'] ?? !UserAddress::where('user_id', auth()->id())->exists();

            if ($data['is_default']) {
                UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
            }

            UserAddress::create($data);
        });

        $notify[] = ['success', 'Address added successfully'];
        if ($request->filled('redirect_to')) {
            return back()->withNotify($notify);
        }

        return to_route('user.addresses.index')->withNotify($notify);
    }

    public function edit($id)
    {
        $pageTitle = 'Edit Address';
        $address = UserAddress::where('user_id', auth()->id())->findOrFail($id);

        return view('Template::user.addresses.form', compact('pageTitle', 'address'));
    }

    public function update(Request $request, $id)
    {
        $address = UserAddress::where('user_id', auth()->id())->findOrFail($id);
        $data = $this->validatedAddress($request);

        DB::transaction(function () use ($address, $data) {
            if (!empty($data['is_default'])) {
                UserAddress::where('user_id', auth()->id())->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update($data);

            if (!UserAddress::where('user_id', auth()->id())->where('is_default', true)->exists()) {
                $address->update(['is_default' => true]);
            }
        });

        $notify[] = ['success', 'Address updated successfully'];
        if ($request->filled('redirect_to')) {
            return back()->withNotify($notify);
        }

        return to_route('user.addresses.index')->withNotify($notify);
    }

    public function destroy($id)
    {
        $address = UserAddress::where('user_id', auth()->id())->findOrFail($id);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextDefault = UserAddress::where('user_id', auth()->id())->latest('id')->first();
            $nextDefault?->update(['is_default' => true]);
        }

        $notify[] = ['success', 'Address deleted successfully'];
        return back()->withNotify($notify);
    }

    public function setDefault($id)
    {
        $address = UserAddress::where('user_id', auth()->id())->findOrFail($id);

        DB::transaction(function () use ($address) {
            UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        $notify[] = ['success', 'Default address updated successfully'];
        return back()->withNotify($notify);
    }

    private function validatedAddress(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'mobile' => 'required|string|max:40',
            'address' => 'required|string|max:1000',
            'city' => 'required|string|max:120',
            'state' => 'required|string|max:120',
            'zip' => 'required|string|max:40',
            'country' => 'required|string|max:120',
            'is_default' => 'nullable|boolean',
        ]);

        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        return $data;
    }
}
