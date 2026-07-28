@extends('admin.layout')

@section('title', __('reports.products_title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('reports.products_subtitle') }}</p>

    <form method="GET" class="flex flex-wrap items-end gap-2 mb-6">
        <div>
            <label class="dj-admin-label">{{ __('reports.date_from') }}</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="dj-admin-input w-auto">
        </div>
        <div>
            <label class="dj-admin-label">{{ __('reports.date_to') }}</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="dj-admin-input w-auto">
        </div>
        <button class="dj-admin-btn dj-admin-btn-secondary">{{ __('reports.apply') }}</button>
    </form>

    @foreach ([
        ['title' => __('reports.products_top_by_quantity'), 'rows' => $topByQuantity, 'valueKey' => 'total_quantity', 'valueLabel' => __('reports.products_quantity_sold')],
        ['title' => __('reports.products_top_by_revenue'), 'rows' => $topByRevenue, 'valueKey' => 'total_revenue', 'valueLabel' => __('reports.products_revenue'), 'currency' => true],
        ['title' => __('reports.products_worst_by_quantity'), 'rows' => $worstByQuantity, 'valueKey' => 'total_quantity', 'valueLabel' => __('reports.products_quantity_sold'), 'hint' => __('reports.products_worst_hint')],
    ] as $djSection)
        <div class="dj-admin-card dj-admin-table-wrap mb-6">
            <div class="dj-admin-card-header"><span>{{ $djSection['title'] }}</span></div>
            @isset($djSection['hint'])
                <p class="dj-admin-hint px-4 pt-3">{{ $djSection['hint'] }}</p>
            @endisset
            <table class="dj-admin-table">
                <thead>
                    <tr>
                        <th>{{ __('reports.products_product') }}</th>
                        <th>{{ $djSection['valueLabel'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($djSection['rows'] as $djRow)
                        <tr>
                            <td class="font-medium text-[var(--dj-ink)]">{{ $djRow->product_name }}</td>
                            <td>
                                @if ($djSection['currency'] ?? false)
                                    {{ number_format($djRow->{$djSection['valueKey']}) }} EGP
                                @else
                                    {{ number_format($djRow->{$djSection['valueKey']}) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="dj-admin-table-empty">{{ __('reports.products_no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
@endsection
