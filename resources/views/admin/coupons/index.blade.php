@extends('admin.layout')

@section('title', __('coupons.title'))

@section('content')
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.coupons.create') }}" class="dj-admin-btn dj-admin-btn-primary">+ {{ __('coupons.add_coupon') }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('coupons.code') }}</th>
                    <th>{{ __('coupons.type') }}</th>
                    <th>{{ __('coupons.value') }}</th>
                    <th>{{ __('coupons.uses') }}</th>
                    <th>{{ __('coupons.expires') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($coupons as $coupon)
                    <tr>
                        <td class="font-semibold text-[var(--dj-maroon)]">{{ $coupon->code }}</td>
                        <td>{{ $coupon->type === 'percentage' ? __('coupons.percentage') : __('coupons.fixed') }}</td>
                        <td>{{ $coupon->value }}{{ $coupon->type === 'percentage' ? '%' : ' EGP' }}</td>
                        <td>{{ $coupon->used_count }}{{ $coupon->max_uses ? '/'.$coupon->max_uses : '' }}</td>
                        <td>{{ $coupon->expires_at?->format('M j, Y') ?? '-' }}</td>
                        <td><span class="dj-admin-badge {{ $coupon->is_active ? 'dj-admin-badge-success' : 'dj-admin-badge-neutral' }}">{{ $coupon->is_active ? __('general.active') : __('general.inactive') }}</span></td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="dj-admin-link">{{ __('general.edit') }}</a>
                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline" onsubmit="return confirm('{{ __('coupons.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="dj-admin-table-empty">{{ __('coupons.no_coupons') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $coupons->links() }}</div>
@endsection
