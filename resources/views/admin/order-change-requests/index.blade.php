@extends('admin.layout')

@section('title', __('order_change_requests.admin_title'))

@section('content')
    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('order_change_requests.admin_order') }}</th>
                    <th>{{ __('order_change_requests.admin_customer') }}</th>
                    <th>{{ __('order_change_requests.admin_type') }}</th>
                    <th>{{ __('order_change_requests.admin_reason') }}</th>
                    <th>{{ __('order_change_requests.admin_status') }}</th>
                    <th>{{ __('order_change_requests.admin_date') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $changeRequest)
                    @php
                        $djStatusBadge = [
                            'pending' => 'dj-admin-badge-gold',
                            'contacted' => 'dj-admin-badge-neutral',
                            'resolved' => 'dj-admin-badge-success',
                        ][$changeRequest->status] ?? 'dj-admin-badge-neutral';
                    @endphp
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">
                            @if ($changeRequest->order)
                                <a href="{{ route('admin.orders.show', $changeRequest->order) }}" class="dj-admin-link">{{ $changeRequest->order->order_number }}</a>
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td>{{ $changeRequest->order?->user?->name ?? $changeRequest->order?->customer_name ?? '—' }}</td>
                        <td>{{ __('order_change_requests.type_'.$changeRequest->type) }}</td>
                        <td>{{ __('order_change_requests.reason_'.$changeRequest->reason) }}</td>
                        <td><span class="dj-admin-badge {{ $djStatusBadge }}">{{ __('order_change_requests.status_'.$changeRequest->status) }}</span></td>
                        <td>{{ $changeRequest->created_at->translatedFormat('M j, Y H:i') }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.order-change-requests.status', $changeRequest) }}">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="dj-admin-input w-auto">
                                    @foreach (\App\Models\OrderChangeRequest::STATUSES as $djStatusOption)
                                        <option value="{{ $djStatusOption }}" {{ $changeRequest->status === $djStatusOption ? 'selected' : '' }}>
                                            {{ __('order_change_requests.status_'.$djStatusOption) }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="dj-admin-table-empty">{{ __('order_change_requests.admin_no_requests') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
