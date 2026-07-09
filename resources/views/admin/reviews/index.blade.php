@extends('admin.layout')

@section('title', __('reviews.title'))

@section('content')
    {{-- ===== STAT CARDS ===== --}}
    @php
        $djReviewCards = [
            ['label' => __('reviews.stat_total'), 'value' => number_format($stats['total'])],
            ['label' => __('reviews.stat_pending'), 'value' => number_format($stats['pending']), 'href' => route('admin.reviews.index', ['status' => 'pending'])],
            ['label' => __('reviews.stat_approved'), 'value' => number_format($stats['approved']), 'href' => route('admin.reviews.index', ['status' => 'approved'])],
            ['label' => __('reviews.stat_rejected'), 'value' => number_format($stats['rejected']), 'href' => route('admin.reviews.index', ['status' => 'rejected'])],
            ['label' => __('reviews.stat_average_rating'), 'value' => $stats['average_rating']],
            ['label' => __('reviews.stat_today'), 'value' => number_format($stats['today'])],
            ['label' => __('reviews.stat_this_month'), 'value' => number_format($stats['this_month'])],
        ];
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 sm:gap-4 mb-8">
        @foreach ($djReviewCards as $card)
            @php $djTag = isset($card['href']) ? 'a' : 'div'; @endphp
            <{{ $djTag }} @if(isset($card['href'])) href="{{ $card['href'] }}" @endif class="dj-admin-stat-card">
                <p class="dj-admin-stat-label truncate">{{ $card['label'] }}</p>
                <p class="dj-admin-stat-value truncate">{{ $card['value'] }}</p>
            </{{ $djTag }}>
        @endforeach
    </div>

    {{-- ===== FILTERS ===== --}}
    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('reviews.search_placeholder') }}" class="dj-admin-input w-full sm:w-auto">

        <select name="status" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('reviews.all_statuses') }}</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('reviews.status_pending') }}</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('reviews.status_approved') }}</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('reviews.status_rejected') }}</option>
        </select>

        <select name="rating" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('reviews.all_ratings') }}</option>
            @foreach ([5, 4, 3, 2, 1] as $star)
                <option value="{{ $star }}" {{ (string) request('rating') === (string) $star ? 'selected' : '' }}>{{ str_repeat('★', $star) }}</option>
            @endforeach
        </select>

        <select name="product_id" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('reviews.all_products') }}</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" {{ (string) request('product_id') === (string) $product->id ? 'selected' : '' }}>{{ $product->name_en }}</option>
            @endforeach
        </select>

        <select name="verified" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('reviews.all_statuses') }}</option>
            <option value="1" {{ request('verified') === '1' ? 'selected' : '' }}>{{ __('reviews.verified_only') }}</option>
            <option value="0" {{ request('verified') === '0' ? 'selected' : '' }}>{{ __('reviews.unverified_only') }}</option>
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}" title="{{ __('reviews.date_from') }}" class="dj-admin-input w-auto">
        <input type="date" name="date_to" value="{{ request('date_to') }}" title="{{ __('reviews.date_to') }}" class="dj-admin-input w-auto">

        <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('general.search') }}</button>
    </form>

    {{-- ===== TABLE ===== --}}
    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('reviews.customer') }}</th>
                    <th>{{ __('reviews.product') }}</th>
                    <th>{{ __('reviews.rating') }}</th>
                    <th>{{ __('reviews.comment') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th>{{ __('general.date') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reviews as $review)
                    @php
                        $djReviewBadge = match ($review->status) {
                            'approved' => 'dj-admin-badge-success',
                            'rejected' => 'dj-admin-badge-danger',
                            default => 'dj-admin-badge-gold',
                        };
                    @endphp
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">
                            {{ $review->name }}
                            @if ($review->is_verified_purchase)
                                <span class="dj-admin-badge dj-admin-badge-info">{{ __('reviews.verified_purchase') }}</span>
                            @endif
                            @if ($review->is_featured)
                                <span class="dj-admin-badge dj-admin-badge-info">{{ __('reviews.featured') }}</span>
                            @endif
                        </td>
                        <td>{{ $review->product->name_en }}</td>
                        <td class="text-[var(--dj-gold-bright)]">{{ str_repeat('★', $review->rating) }}<span class="text-[var(--dj-cream-2)]">{{ str_repeat('★', 5 - $review->rating) }}</span></td>
                        <td>{{ str($review->comment)->limit(60) }}</td>
                        <td><span class="dj-admin-badge {{ $djReviewBadge }}">{{ __('reviews.status_'.$review->status) }}</span></td>
                        <td>{{ $review->created_at->format('M j, Y') }}</td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.reviews.show', $review) }}" class="dj-admin-link">{{ __('general.view') }}</a>

                            @unless ($review->status === 'approved')
                                <form method="POST" action="{{ route('admin.reviews.approve', $review) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="dj-admin-link" style="color:#237a3f;">{{ __('reviews.approve') }}</button>
                                </form>
                            @endunless

                            @unless ($review->status === 'rejected')
                                <details class="inline-block">
                                    <summary class="dj-admin-link-muted inline cursor-pointer list-none">{{ __('reviews.reject') }}</summary>
                                    <form method="POST" action="{{ route('admin.reviews.reject', $review) }}" class="mt-2 flex gap-2">
                                        @csrf @method('PATCH')
                                        <input type="text" name="reason" placeholder="{{ __('reviews.reject_reason_placeholder') }}" class="dj-admin-input text-xs">
                                        <button class="dj-admin-btn dj-admin-btn-secondary text-xs shrink-0">{{ __('reviews.reject') }}</button>
                                    </form>
                                </details>
                            @endunless

                            <form method="POST" action="{{ route($review->is_featured ? 'admin.reviews.unfeature' : 'admin.reviews.feature', $review) }}" class="inline">
                                @csrf @method('PATCH')
                                <button class="dj-admin-link-muted">{{ $review->is_featured ? __('reviews.unfeature') : __('reviews.feature') }}</button>
                            </form>

                            <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="inline" onsubmit="return confirm('{{ __('reviews.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="dj-admin-table-empty">{{ __('reviews.no_reviews') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reviews->links() }}</div>

    {{-- ===== ANALYTICS ===== --}}
    @php
        $djReviewBrandPalette = ['#601526', '#D4A574', '#9C5064', '#7A2038', '#3C0B17', '#E8C39A'];

        $djRatingChart = [
            'type' => 'bar',
            'data' => [
                'labels' => ['5★', '4★', '3★', '2★', '1★'],
                'datasets' => [['label' => __('reviews.chart_by_rating'), 'data' => [
                    $charts['byRating'][5] ?? 0, $charts['byRating'][4] ?? 0, $charts['byRating'][3] ?? 0,
                    $charts['byRating'][2] ?? 0, $charts['byRating'][1] ?? 0,
                ], 'backgroundColor' => '#601526', 'borderRadius' => 6, 'maxBarThickness' => 30]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'grid' => ['color' => 'rgba(60,11,23,.06)']], 'x' => ['grid' => ['display' => false]]]],
        ];

        $djStatusReviewChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => [__('reviews.status_pending'), __('reviews.status_approved'), __('reviews.status_rejected')],
                'datasets' => [['data' => [
                    $charts['byStatus']['pending'] ?? 0, $charts['byStatus']['approved'] ?? 0, $charts['byStatus']['rejected'] ?? 0,
                ], 'backgroundColor' => ['#E8C39A', '#601526', '#9C5064'], 'borderColor' => '#fff', 'borderWidth' => 2]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'cutout' => '62%'],
        ];

        $djTopReviewedChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $charts['topReviewed']->map(fn ($r) => $r->product->name_en)->all(),
                'datasets' => [['label' => __('reviews.chart_top_reviewed'), 'data' => $charts['topReviewed']->pluck('count')->all(), 'backgroundColor' => '#9C5064', 'borderRadius' => 6, 'maxBarThickness' => 22]],
            ],
            'options' => ['indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['grid' => ['color' => 'rgba(60,11,23,.06)']], 'y' => ['grid' => ['display' => false]]]],
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mt-8">
        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('reviews.chart_by_rating') }}</h2>
            <div class="relative h-64">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djRatingChart)'></canvas>
            </div>
        </div>
        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('reviews.chart_by_status') }}</h2>
            <div class="relative h-64">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djStatusReviewChart)'></canvas>
            </div>
        </div>
        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('reviews.chart_top_reviewed') }}</h2>
            <div class="relative h-64">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djTopReviewedChart)'></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mt-6">
        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('reviews.highest_rated') }}</h2>
            <ol class="space-y-2 text-sm">
                @forelse ($charts['highestRated'] as $row)
                    <li class="flex justify-between border-b border-[var(--dj-cream-2)] pb-2">
                        <span>{{ $row->product->name_en }}</span>
                        <span class="text-[var(--dj-gold-bright)]">{{ round($row->avg_rating, 1) }} ★</span>
                    </li>
                @empty
                    <li class="dj-admin-table-empty">{{ __('reviews.no_reviews') }}</li>
                @endforelse
            </ol>
        </div>
        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('reviews.lowest_rated') }}</h2>
            <ol class="space-y-2 text-sm">
                @forelse ($charts['lowestRated'] as $row)
                    <li class="flex justify-between border-b border-[var(--dj-cream-2)] pb-2">
                        <span>{{ $row->product->name_en }}</span>
                        <span class="text-[var(--dj-gold-bright)]">{{ round($row->avg_rating, 1) }} ★</span>
                    </li>
                @empty
                    <li class="dj-admin-table-empty">{{ __('reviews.no_reviews') }}</li>
                @endforelse
            </ol>
        </div>
    </div>
@endsection
