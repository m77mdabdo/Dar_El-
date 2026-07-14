@forelse ($items as $item)
    <div class="dj-cart-item">
        <div class="dj-ci-swatch dj-photo-wrap dj-tint-maroon">
            @if ($item['product']->cover_image_src)
                <img src="{{ $item['product']->cover_image_src }}" alt="">
            @endif
        </div>
        <div class="dj-ci-info">
            <h4>{{ trans_field($item['product'], 'name') }}</h4>
            <span>{{ __('Size') }} {{ $item['size'] }} · {{ number_format($item['product']->price) }} EGP</span>
            @if ($item['exceeds_stock'])
                <p class="dj-ci-warning">
                    {{ $item['stock'] > 0 ? __('Only :count left', ['count' => $item['stock']]) : __('Sold out') }}
                </p>
            @endif
            <div class="dj-qty">
                <button type="button" aria-label="{{ __('Decrease quantity') }}" onclick="djChangeCartQty('{{ route('cart.index') }}', '{{ $item['key'] }}', {{ $item['quantity'] - 1 }}, this)">-</button>
                <span>{{ $item['quantity'] }}</span>
                <button type="button" aria-label="{{ __('Increase quantity') }}" onclick="djChangeCartQty('{{ route('cart.index') }}', '{{ $item['key'] }}', {{ $item['quantity'] + 1 }}, this)">+</button>
            </div>
            <a class="dj-ci-remove" role="button" tabindex="0" onclick="djRemoveFromCart('{{ route('cart.index') }}', '{{ $item['key'] }}', this)" onkeydown="if(event.key==='Enter'){djRemoveFromCart('{{ route('cart.index') }}', '{{ $item['key'] }}', this)}">{{ __('Remove') }}</a>
        </div>
    </div>
@empty
    <div class="dj-empty-cart">
        🛍️<br>{{ __('Your cart is empty') }}<br>{{ __('Start adding your favorite pieces') }}
    </div>
@endforelse
