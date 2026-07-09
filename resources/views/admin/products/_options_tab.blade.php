<div class="space-y-2">
    <h2 class="font-semibold text-[var(--dj-maroon-dark)]">{{ __('product_options.options_heading') }}</h2>
    <p class="dj-admin-hint">{{ __('product_options.options_hint') }}</p>
</div>

<div class="space-y-4 mt-4">
    @forelse ($product->options as $option)
        <div class="dj-admin-card p-4" x-data="{ open: true }">
            <div class="flex items-center justify-between gap-3 cursor-pointer" @click="open = !open">
                <span class="font-semibold text-[var(--dj-ink)]">{{ $option->name_en }} <span class="text-[var(--dj-rose-dust)] font-normal">/ {{ $option->name_ar }}</span></span>
                <form method="POST" action="{{ route('admin.products.options.destroy', [$product, $option]) }}" onsubmit="event.stopPropagation(); return confirm('{{ __('product_options.confirm_delete_option') }}')" @click.stop>
                    @csrf @method('DELETE')
                    <button class="dj-admin-link-muted text-xs">{{ __('general.delete') }}</button>
                </form>
            </div>

            <div x-show="open" x-cloak class="mt-4 space-y-3">
                <p class="text-xs font-semibold text-[var(--dj-rose-dust)] uppercase">{{ __('product_options.values_heading') }}</p>

                @forelse ($option->values as $value)
                    <div class="border border-[var(--dj-cream-2)] rounded-lg p-3 flex flex-wrap items-center gap-3">
                        @if ($value->hex_color)
                            <span class="w-6 h-6 rounded-full border border-[var(--dj-cream-2)] shrink-0" style="background:{{ $value->hex_color }};"></span>
                        @endif
                        @if ($value->swatch_image_url)
                            <img src="{{ $value->swatch_image_url }}" class="w-8 h-8 rounded-lg object-cover border border-[var(--dj-cream-2)]">
                        @endif
                        <span class="text-sm font-medium text-[var(--dj-ink)]">{{ $value->name_en }} <span class="text-[var(--dj-rose-dust)]">/ {{ $value->name_ar }}</span></span>
                        <span class="dj-admin-badge {{ $value->is_active ? 'dj-admin-badge-success' : 'dj-admin-badge-neutral' }}">{{ $value->is_active ? __('general.active') : __('general.inactive') }}</span>

                        <div class="flex flex-wrap gap-2 ms-auto">
                            @foreach ($value->images as $image)
                                <div class="relative">
                                    <img src="{{ asset('storage/'.$image->path) }}" class="w-10 h-10 rounded-lg object-cover border border-[var(--dj-cream-2)]">
                                    <form method="POST" action="{{ route('admin.products.options.values.images.destroy', [$product, $option, $value, $image]) }}" onsubmit="return confirm('{{ __('product_options.confirm_delete_value') }}')">
                                        @csrf @method('DELETE')
                                        <button class="text-[10px] text-[#b42318] underline">{{ __('general.delete') }}</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('admin.products.options.values.destroy', [$product, $option, $value]) }}" onsubmit="return confirm('{{ __('product_options.confirm_delete_value') }}')">
                            @csrf @method('DELETE')
                            <button class="dj-admin-link-muted text-xs shrink-0">{{ __('general.delete') }}</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('admin.products.options.values.images.store', [$product, $option, $value]) }}" enctype="multipart/form-data" class="flex items-center gap-2 ps-3">
                        @csrf
                        <input type="file" name="images[]" multiple accept="image/*" class="text-xs">
                        <button class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm shrink-0">{{ __('product_options.gallery_images') }}</button>
                    </form>
                @empty
                    <p class="dj-admin-table-empty">{{ __('product_options.no_values') }}</p>
                @endforelse

                <form method="POST" action="{{ route('admin.products.options.values.store', [$product, $option]) }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-5 gap-2 items-end pt-3 border-t border-[var(--dj-cream-2)]">
                    @csrf
                    <div>
                        <label class="dj-admin-label text-[11px]">{{ __('product_options.value_name_en') }}</label>
                        <input type="text" name="name_en" required class="dj-admin-input">
                    </div>
                    <div>
                        <label class="dj-admin-label text-[11px]">{{ __('product_options.value_name_ar') }}</label>
                        <input type="text" name="name_ar" dir="rtl" required class="dj-admin-input">
                    </div>
                    <div>
                        <label class="dj-admin-label text-[11px]">{{ __('product_options.hex_color') }}</label>
                        <input type="color" name="hex_color" value="#601526" class="dj-admin-input h-[38px] p-1">
                    </div>
                    <div>
                        <label class="dj-admin-label text-[11px]">{{ __('product_options.swatch_image') }}</label>
                        <input type="file" name="swatch_image" accept="image/*" class="text-xs">
                    </div>
                    <button class="dj-admin-btn dj-admin-btn-primary shrink-0">{{ __('product_options.add_value') }}</button>
                </form>
            </div>
        </div>
    @empty
        <p class="dj-admin-table-empty">{{ __('product_options.no_options') }}</p>
    @endforelse
</div>

<div class="dj-admin-card p-4 mt-4">
    <p class="font-semibold text-[var(--dj-maroon-dark)] mb-3">{{ __('product_options.add_option') }}</p>
    <form method="POST" action="{{ route('admin.products.options.store', $product) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-end">
        @csrf
        <div>
            <label class="dj-admin-label text-[11px]">{{ __('product_options.option_name_en') }}</label>
            <input type="text" name="name_en" required class="dj-admin-input" placeholder="Color">
        </div>
        <div>
            <label class="dj-admin-label text-[11px]">{{ __('product_options.option_name_ar') }}</label>
            <input type="text" name="name_ar" dir="rtl" required class="dj-admin-input">
        </div>
        <button class="dj-admin-btn dj-admin-btn-primary shrink-0">{{ __('product_options.add_option') }}</button>
    </form>
</div>
