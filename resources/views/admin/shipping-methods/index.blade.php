@extends('admin.layout')

@section('title', __('shipping_methods.title'))

@section('content')
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.shipping-methods.create') }}" class="dj-admin-btn dj-admin-btn-primary">+ {{ __('shipping_methods.add_shipping_method') }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('general.name') }}</th>
                    <th>{{ __('shipping_methods.fee') }}</th>
                    <th>{{ __('shipping_methods.delivery_estimate') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shippingMethods as $method)
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">
                            {{ trans_field($method, 'name') }}
                            @if ($method->code === \App\Models\ShippingMethod::DEFAULT_CODE)
                                <span class="dj-admin-badge dj-admin-badge-gold" title="{{ __('shipping_methods.code_hint') }}">{{ __('shipping_methods.fallback_badge') }}</span>
                            @endif
                        </td>
                        <td>{{ number_format($method->fee) }} EGP</td>
                        <td>{{ $method->deliveryEstimateLabel() }}</td>
                        <td>
                            <span class="dj-admin-badge {{ $method->is_active ? 'dj-admin-badge-success' : 'dj-admin-badge-neutral' }}">
                                {{ $method->is_active ? __('general.active') : __('general.inactive') }}
                            </span>
                            <form method="POST" action="{{ route('admin.shipping-methods.toggle-active', $method) }}" class="inline" onsubmit="return confirm('{{ __('shipping_methods.confirm_toggle_active') }}')">
                                @csrf
                                @method('PATCH')
                                <button class="dj-admin-link-muted text-xs">{{ $method->is_active ? __('general.inactive') : __('general.active') }}</button>
                            </form>
                        </td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.shipping-methods.edit', $method) }}" class="dj-admin-link">{{ __('general.edit') }}</a>
                            <form method="POST" action="{{ route('admin.shipping-methods.destroy', $method) }}" class="inline" onsubmit="return confirm('{{ __('shipping_methods.confirm_delete') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('shipping_methods.no_shipping_methods') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
