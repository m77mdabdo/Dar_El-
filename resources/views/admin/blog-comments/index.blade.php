@extends('admin.layout')

@section('title', __('blog_comments.title'))

@section('content')
    {{-- ===== STAT CARDS ===== --}}
    @php
        $djCommentCards = [
            ['label' => __('blog_comments.stat_total'), 'value' => number_format($stats['total'])],
            ['label' => __('blog_comments.stat_pending'), 'value' => number_format($stats['pending']), 'href' => route('admin.blog-comments.index', ['status' => 'pending'])],
            ['label' => __('blog_comments.stat_approved'), 'value' => number_format($stats['approved']), 'href' => route('admin.blog-comments.index', ['status' => 'approved'])],
            ['label' => __('blog_comments.stat_rejected'), 'value' => number_format($stats['rejected']), 'href' => route('admin.blog-comments.index', ['status' => 'rejected'])],
            ['label' => __('blog_comments.stat_today'), 'value' => number_format($stats['today'])],
            ['label' => __('blog_comments.stat_this_month'), 'value' => number_format($stats['this_month'])],
        ];
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 mb-8">
        @foreach ($djCommentCards as $card)
            @php $djTag = isset($card['href']) ? 'a' : 'div'; @endphp
            <{{ $djTag }} @if(isset($card['href'])) href="{{ $card['href'] }}" @endif class="dj-admin-stat-card">
                <p class="dj-admin-stat-label truncate">{{ $card['label'] }}</p>
                <p class="dj-admin-stat-value truncate">{{ $card['value'] }}</p>
            </{{ $djTag }}>
        @endforeach
    </div>

    {{-- ===== FILTERS ===== --}}
    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('blog_comments.search_placeholder') }}" class="dj-admin-input w-full sm:w-auto">

        <select name="status" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('blog_comments.all_statuses') }}</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('blog_comments.status_pending') }}</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('blog_comments.status_approved') }}</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('blog_comments.status_rejected') }}</option>
        </select>

        <select name="blog_post_id" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('blog_comments.all_posts') }}</option>
            @foreach ($posts as $post)
                <option value="{{ $post->id }}" {{ (string) request('blog_post_id') === (string) $post->id ? 'selected' : '' }}>{{ trans_field($post, 'title') }}</option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}" title="{{ __('blog_comments.date_from') }}" class="dj-admin-input w-auto">
        <input type="date" name="date_to" value="{{ request('date_to') }}" title="{{ __('blog_comments.date_to') }}" class="dj-admin-input w-auto">

        <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('general.search') }}</button>
    </form>

    {{-- ===== TABLE ===== --}}
    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('blog_comments.commenter') }}</th>
                    <th>{{ __('blog_comments.blog_post') }}</th>
                    <th>{{ __('blog_comments.comment') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th>{{ __('general.date') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($comments as $comment)
                    @php
                        $djCommentBadge = match ($comment->status) {
                            'approved' => 'dj-admin-badge-success',
                            'rejected' => 'dj-admin-badge-danger',
                            default => 'dj-admin-badge-gold',
                        };
                    @endphp
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">{{ $comment->name }}</td>
                        <td>{{ trans_field($comment->blogPost, 'title') }}</td>
                        <td>{{ str($comment->comment)->limit(60) }}</td>
                        <td><span class="dj-admin-badge {{ $djCommentBadge }}">{{ __('blog_comments.status_'.$comment->status) }}</span></td>
                        <td>{{ $comment->created_at->translatedFormat('M j, Y') }}</td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.blog-comments.show', $comment) }}" class="dj-admin-link">{{ __('general.view') }}</a>

                            @unless ($comment->status === 'approved')
                                <form method="POST" action="{{ route('admin.blog-comments.approve', $comment) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="dj-admin-link" style="color:#237a3f;">{{ __('reviews.approve') }}</button>
                                </form>
                            @endunless

                            @unless ($comment->status === 'rejected')
                                <details class="inline-block">
                                    <summary class="dj-admin-link-muted inline cursor-pointer list-none">{{ __('reviews.reject') }}</summary>
                                    <form method="POST" action="{{ route('admin.blog-comments.reject', $comment) }}" class="mt-2 flex gap-2">
                                        @csrf @method('PATCH')
                                        <input type="text" name="reason" placeholder="{{ __('blog_comments.reject_reason_placeholder') }}" class="dj-admin-input text-xs">
                                        <button class="dj-admin-btn dj-admin-btn-secondary text-xs shrink-0">{{ __('reviews.reject') }}</button>
                                    </form>
                                </details>
                            @endunless

                            <form method="POST" action="{{ route('admin.blog-comments.destroy', $comment) }}" class="inline" onsubmit="return confirm('{{ __('blog_comments.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="dj-admin-table-empty">{{ __('blog_comments.no_comments') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $comments->links() }}</div>

    {{-- ===== ANALYTICS ===== --}}
    @php
        $djCommentStatusChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => [__('blog_comments.status_pending'), __('blog_comments.status_approved'), __('blog_comments.status_rejected')],
                'datasets' => [['data' => [
                    $charts['byStatus']['pending'] ?? 0, $charts['byStatus']['approved'] ?? 0, $charts['byStatus']['rejected'] ?? 0,
                ], 'backgroundColor' => ['#E8C39A', '#601526', '#9C5064'], 'borderColor' => '#fff', 'borderWidth' => 2]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'cutout' => '62%'],
        ];

        $djCommentPerDayChart = [
            'type' => 'line',
            'data' => [
                'labels' => $charts['labels'],
                'datasets' => [['label' => __('blog_comments.chart_per_day'), 'data' => $charts['series'], 'borderColor' => '#601526', 'backgroundColor' => 'rgba(96,21,38,0.08)', 'pointBackgroundColor' => '#601526', 'pointRadius' => 2, 'tension' => 0.35, 'fill' => true]],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'grid' => ['color' => 'rgba(60,11,23,.06)']], 'x' => ['grid' => ['display' => false]]]],
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mt-8">
        <div class="dj-admin-card p-4">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('blog_comments.chart_by_status') }}</h2>
            <div class="relative h-64">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djCommentStatusChart)'></canvas>
            </div>
        </div>
        <div class="dj-admin-card p-4 lg:col-span-2">
            <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('blog_comments.chart_per_day') }}</h2>
            <div class="relative h-64">
                <canvas class="dj-admin-chart w-full h-full" data-config='@json($djCommentPerDayChart)'></canvas>
            </div>
        </div>
    </div>

    <div class="dj-admin-card p-4 mt-6">
        <h2 class="font-semibold mb-3 text-sm sm:text-base text-[var(--dj-maroon-dark)]">{{ __('blog_comments.chart_top_commented') }}</h2>
        <ol class="space-y-2 text-sm">
            @forelse ($charts['topCommented'] as $row)
                <li class="flex justify-between border-b border-[var(--dj-cream-2)] pb-2">
                    <span>{{ trans_field($row->blogPost, 'title') }}</span>
                    <span class="text-[var(--dj-rose-dust)]">{{ $row->count }}</span>
                </li>
            @empty
                <li class="dj-admin-table-empty">{{ __('blog_comments.no_comments') }}</li>
            @endforelse
        </ol>
    </div>
@endsection
