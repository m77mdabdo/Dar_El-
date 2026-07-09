@isset($product)
    @if ($product->images->isNotEmpty())
        <div class="mb-6">
            <label class="dj-admin-label">{{ __('products.existing_images') }}</label>
            <p class="dj-admin-hint mb-2">{{ __('products.drag_to_reorder') }}</p>
            <div
                class="flex flex-wrap gap-4"
                data-image-reorder
                data-reorder-url="{{ route('admin.products.images.reorder', $product) }}"
                data-toast-success="{{ __('products.image_order_updated') }}"
                data-toast-error="{{ __('products.image_order_error') }}"
            >
                @foreach ($product->images as $image)
                    <div class="w-24 text-center cursor-grab" data-image-id="{{ $image->id }}">
                        <img src="{{ asset('storage/'.$image->path) }}" class="w-24 h-24 object-cover rounded-lg border border-[var(--dj-cream-2)] mb-1">

                        <form method="POST" action="{{ route('admin.products.images.cover', [$product, $image]) }}" class="mb-1">
                            @csrf
                            @method('PATCH')
                            <button class="dj-admin-link-muted text-xs">{{ __('products.set_as_cover') }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" onsubmit="return confirm('{{ __('products.confirm_delete_image') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endisset

<div>
    <label class="dj-admin-label">{{ __('products.gallery_images') }}</label>
    <input type="file" name="images[]" multiple accept="image/*" class="w-full text-sm">
    @error('images') <p class="dj-admin-error">{{ $message }}</p> @enderror
    @foreach ($errors->get('images.*') as $messages)
        @foreach ($messages as $message)
            <p class="dj-admin-error">{{ $message }}</p>
        @endforeach
    @endforeach
    <p class="dj-admin-hint">{{ __('products.gallery_images_hint') }}</p>
</div>
