@extends('admin.layout')

@section('title', 'Edit Product')

@section('content')
    @if ($product->images->isNotEmpty())
        <div class="mb-6 max-w-2xl">
            <label class="block text-sm font-medium mb-2">Existing Images</label>
            <div class="flex flex-wrap gap-4">
                @foreach ($product->images as $image)
                    <div class="w-24 text-center">
                        <img src="{{ asset('storage/'.$image->path) }}" class="w-24 h-24 object-cover rounded border border-stone-200 mb-1">

                        <form method="POST" action="{{ route('admin.products.images.update', [$product, $image]) }}" class="flex gap-1 mb-1">
                            @csrf
                            @method('PATCH')
                            <input type="number" name="sort_order" value="{{ $image->sort_order }}" class="w-full rounded border-stone-300 text-xs">
                            <button class="text-xs text-stone-500 underline shrink-0">Save</button>
                        </form>

                        <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" onsubmit="return confirm('Delete this image?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs text-red-600 underline">Delete</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="space-y-4 max-w-2xl">
        @method('PUT')
        @include('admin.products._form')
    </form>
@endsection
