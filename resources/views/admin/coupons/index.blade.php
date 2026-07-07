@extends('admin.layout')

@section('title', 'Coupons')

@section('content')
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.coupons.create') }}" class="bg-rose-700 hover:bg-rose-800 text-white text-sm px-4 py-2 rounded">Add Coupon</a>
    </div>

    <div class="bg-white border border-stone-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Value</th>
                    <th class="px-4 py-3">Uses</th>
                    <th class="px-4 py-3">Expires</th>
                    <th class="px-4 py-3">Active</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($coupons as $coupon)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $coupon->code }}</td>
                        <td class="px-4 py-3">{{ ucfirst($coupon->type) }}</td>
                        <td class="px-4 py-3">{{ $coupon->value }}{{ $coupon->type === 'percentage' ? '%' : ' EGP' }}</td>
                        <td class="px-4 py-3">{{ $coupon->used_count }}{{ $coupon->max_uses ? '/'.$coupon->max_uses : '' }}</td>
                        <td class="px-4 py-3">{{ $coupon->expires_at?->format('M j, Y') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $coupon->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-rose-700 underline">Edit</a>
                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline" onsubmit="return confirm('Delete this coupon?')">
                                @csrf @method('DELETE')
                                <button class="text-stone-500 underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $coupons->links() }}</div>
@endsection
