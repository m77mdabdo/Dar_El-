<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->latest()->get();

        return view('account.addresses.index', compact('addresses'));
    }

    public function create()
    {
        return view('account.addresses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $address = $request->user()->addresses()->create($validated);

        if ($address->is_default) {
            $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }

        return redirect()->route('account.addresses.index')->with('status', __('Address added.'));
    }

    public function edit(Request $request, Address $address)
    {
        $this->authorize('update', $address);

        return view('account.addresses.edit', compact('address'));
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $address->update($this->validated($request));

        if ($address->is_default) {
            $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }

        return redirect()->route('account.addresses.index')->with('status', __('Address updated.'));
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorize('delete', $address);

        $address->delete();

        return redirect()->route('account.addresses.index')->with('status', __('Address removed.'));
    }

    protected function validated(Request $request): array
    {
        $request->merge(['is_default' => $request->boolean('is_default')]);

        return $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'governorate' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'is_default' => ['boolean'],
        ]);
    }
}
