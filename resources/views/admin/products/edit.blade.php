@extends('admin.layout')

@section('title', __('products.edit_product'))

@section('content')
    @if ($product->images->isNotEmpty())
        <div class="dj-admin-card p-4 sm:p-6 mb-6 max-w-2xl">
            <label class="dj-admin-label">{{ __('products.existing_images') }}</label>
            <div class="flex flex-wrap gap-4">
                @foreach ($product->images as $image)
                    <div class="w-24 text-center">
                        <img src="{{ asset('storage/'.$image->path) }}" class="w-24 h-24 object-cover rounded-lg border border-[var(--dj-cream-2)] mb-1">

                        <form method="POST" action="{{ route('admin.products.images.update', [$product, $image]) }}" class="flex gap-1 mb-1">
                            @csrf
                            @method('PATCH')
                            <input type="number" name="sort_order" value="{{ $image->sort_order }}" class="dj-admin-input text-xs">
                            <button class="dj-admin-link-muted shrink-0">{{ __('general.save') }}</button>
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

    <div class="dj-admin-card p-4 sm:p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="space-y-4">
            @method('PUT')
            @include('admin.products._form')
        </form>
    </div>
@endsection
