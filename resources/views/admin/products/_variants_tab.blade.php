@php
    $djVariantCombinationCount = $product->options->reduce(function ($carry, $option) {
        $count = $option->values->where('is_active', true)->count();

        return $count > 0 ? $carry * $count : $carry;
    }, 1);
@endphp

<div class="flex items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="font-semibold text-[var(--dj-maroon-dark)]">{{ __('product_options.variants_heading') }}</h2>
        <p class="dj-admin-hint">{{ __('product_options.variants_hint') }}</p>
    </div>
    @if ($product->options->isNotEmpty())
        <form method="POST" action="{{ route('admin.products.variants.generate', $product) }}"
              onsubmit="return confirm('{{ __('product_options.confirm_generate', ['count' => $djVariantCombinationCount]) }}')"
              data-shortcut-generate>
            @csrf
            <button class="dj-admin-btn dj-admin-btn-primary shrink-0">{{ __('product_options.generate_variants') }}</button>
        </form>
    @endif
</div>

@if ($product->options->isEmpty())
    <p class="dj-admin-table-empty">{{ __('product_options.no_options_for_variants') }}</p>
@elseif ($product->variants->isEmpty())
    <p class="dj-admin-table-empty">{{ __('product_options.no_variants') }}</p>
@else
    <div
        x-data="djVariantBulkTable()"
        data-bulk-action-url="{{ route('admin.products.variants.bulk-action', $product) }}"
        data-confirm-delete="{{ __('product_options.confirm_bulk_delete_variants') }}"
        data-prompt-stock="{{ __('product_options.prompt_bulk_stock') }}"
        data-prompt-delta="{{ __('product_options.prompt_bulk_delta') }}"
        data-prompt-price="{{ __('product_options.prompt_bulk_price') }}"
        data-prompt-sale-price="{{ __('product_options.prompt_bulk_sale_price') }}"
        data-toast-success="{{ __('product_options.bulk_action_success') }}"
        data-toast-error="{{ __('product_options.bulk_action_error') }}"
    >
        <div class="dj-admin-bulk-bar mb-3" x-show="selected.length > 0" x-cloak>
            <span x-text="selected.length + ' {{ __('product_options.selected') }}'"></span>
            <button type="button" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" @click="bulkAction('set_stock')">{{ __('product_options.bulk_set_stock') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" @click="bulkAction('adjust_stock')">{{ __('product_options.bulk_adjust_stock') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" @click="bulkAction('set_price')">{{ __('product_options.bulk_set_price') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" @click="bulkAction('set_sale_price')">{{ __('product_options.bulk_set_sale_price') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" @click="bulkAction('activate')">{{ __('product_options.bulk_activate') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" @click="bulkAction('deactivate')">{{ __('product_options.bulk_deactivate') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" @click="bulkAction('generate_skus')">{{ __('product_options.bulk_generate_skus') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm" @click="bulkAction('duplicate')">{{ __('product_options.bulk_duplicate') }}</button>
            <button type="button" class="dj-admin-btn dj-admin-btn-danger dj-admin-btn-sm" @click="bulkAction('delete')">{{ __('product_options.bulk_delete') }}</button>
        </div>

    <form method="POST" action="{{ route('admin.products.variants.bulk', $product) }}">
        @csrf
        @method('PATCH')
        <div class="dj-admin-card dj-admin-table-wrap" @if ($product->variants->count() > 200) data-large-grid @endif>
            <table class="dj-admin-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" @change="toggleAll($event)"></th>
                        <th></th>
                        <th>{{ __('product_options.tab_variants') }}</th>
                        <th>{{ __('product_options.sku') }}</th>
                        <th>{{ __('product_options.price_override') }}</th>
                        <th>{{ __('product_options.sale_price') }}</th>
                        <th>{{ __('product_options.stock') }}</th>
                        <th>{{ __('product_options.low_stock_threshold') }}</th>
                        <th>{{ __('general.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($product->variants as $index => $variant)
                        <tr>
                            <td><input type="checkbox" x-model="selected" value="{{ $variant->id }}"></td>
                            <td>
                                @if ($variant->image_url)
                                    <img src="{{ $variant->image_url }}" class="w-10 h-10 rounded-lg object-cover border border-[var(--dj-cream-2)]">
                                @endif
                                <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                            </td>
                            <td class="font-medium text-[var(--dj-ink)]">{{ $variant->label() }}</td>
                            <td><input type="text" name="variants[{{ $index }}][sku]" value="{{ $variant->sku }}" class="dj-admin-input text-xs w-28"></td>
                            <td><input type="number" name="variants[{{ $index }}][price_override]" value="{{ $variant->price_override }}" min="0" class="dj-admin-input text-xs w-24"></td>
                            <td><input type="number" name="variants[{{ $index }}][sale_price]" value="{{ $variant->sale_price }}" min="0" class="dj-admin-input text-xs w-24"></td>
                            <td><input type="number" name="variants[{{ $index }}][stock]" value="{{ $variant->stock }}" min="0" required class="dj-admin-input text-xs w-20"></td>
                            <td><input type="number" name="variants[{{ $index }}][low_stock_threshold]" value="{{ $variant->low_stock_threshold }}" min="0" placeholder="{{ \App\Models\Product::LOW_STOCK_THRESHOLD }}" class="dj-admin-input text-xs w-20"></td>
                            <td>
                                <label class="dj-admin-checkbox-row">
                                    <input type="checkbox" name="variants[{{ $index }}][is_active]" value="1" {{ $variant->is_active ? 'checked' : '' }}>
                                    {{ __('general.active') }}
                                </label>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" onsubmit="return confirm('{{ __('product_options.confirm_delete_variant') }}')">
                                    @csrf @method('DELETE')
                                    <button class="dj-admin-link-muted text-xs">{{ __('general.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="submit" class="dj-admin-btn dj-admin-btn-primary mt-4" data-shortcut-save>{{ __('product_options.save_variants') }}</button>
    </form>
    </div>

    <div class="dj-admin-card p-4 mt-6">
        <p class="font-semibold text-[var(--dj-maroon-dark)] mb-3">{{ __('product_options.variant_image') }} / {{ __('product_options.barcode') }} / {{ __('product_options.weight') }}</p>
        <div class="space-y-3">
            @foreach ($product->variants as $variant)
                <form method="POST" action="{{ route('admin.products.variants.update', [$product, $variant]) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2 border-b border-[var(--dj-cream-2)] pb-3">
                    @csrf @method('PATCH')
                    <input type="hidden" name="sku" value="{{ $variant->sku }}">
                    <input type="hidden" name="stock" value="{{ $variant->stock }}">
                    <span class="text-xs font-medium text-[var(--dj-ink)] w-32 truncate">{{ $variant->label() }}</span>
                    <div>
                        <label class="dj-admin-label text-[11px]">{{ __('product_options.barcode') }}</label>
                        <input type="text" name="barcode" value="{{ $variant->barcode }}" class="dj-admin-input text-xs">
                    </div>
                    <div>
                        <label class="dj-admin-label text-[11px]">{{ __('product_options.weight') }}</label>
                        <input type="number" step="0.01" name="weight" value="{{ $variant->weight }}" min="0" class="dj-admin-input text-xs w-24">
                    </div>
                    <div>
                        <label class="dj-admin-label text-[11px]">{{ __('product_options.variant_image') }}</label>
                        <input type="file" name="image" accept="image/*" class="text-xs">
                    </div>
                    <button class="dj-admin-btn dj-admin-btn-secondary dj-admin-btn-sm shrink-0">{{ __('general.save') }}</button>
                </form>
            @endforeach
        </div>
    </div>
@endif
