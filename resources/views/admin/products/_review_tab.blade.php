<div class="dj-admin-card p-4 sm:p-6">
    <h2 class="font-semibold text-[var(--dj-maroon-dark)] mb-1">{{ __('product_options.review_heading') }}</h2>
    <p class="dj-admin-hint mb-4">{{ __('product_options.review_hint') }}</p>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div>
            <dt class="dj-admin-label">{{ __('products.name_en') }}</dt>
            <dd class="text-[var(--dj-ink)]">{{ $product->name_en }}</dd>
        </div>
        <div>
            <dt class="dj-admin-label">{{ __('products.status') }}</dt>
            <dd>
                @php($badge = $product->statusBadge())
                <span class="dj-admin-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            </dd>
        </div>
        <div>
            <dt class="dj-admin-label">{{ __('product_options.tab_images') }}</dt>
            <dd class="text-[var(--dj-ink)]">{{ $product->images->count() }}</dd>
        </div>
        <div>
            <dt class="dj-admin-label">{{ __('product_options.tab_options') }}</dt>
            <dd class="text-[var(--dj-ink)]">{{ $product->options->count() }}</dd>
        </div>
        <div>
            <dt class="dj-admin-label">{{ __('product_options.tab_variants') }}</dt>
            <dd class="text-[var(--dj-ink)]">{{ $product->variants->count() }}</dd>
        </div>
        <div>
            {{-- Real (product_sizes) stock only — adding the Variants tab's
                 stock on top would blend a live number with one the
                 storefront never reads, exactly the confusion the warning
                 banner on that tab exists to prevent. --}}
            <dt class="dj-admin-label">{{ __('products.sizes_stock') }}</dt>
            <dd class="text-[var(--dj-ink)]">{{ $product->totalStock() }}</dd>
        </div>
    </dl>

    <button type="button" class="dj-admin-btn dj-admin-btn-primary" @click="publishNow()">
        {{ __('product_options.publish_now') }}
    </button>
</div>
