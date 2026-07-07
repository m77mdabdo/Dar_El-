@extends('admin.layout')

@section('title', 'Products')

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between gap-3 mb-4">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." class="w-full sm:w-auto rounded border-stone-300 text-sm">
            <select name="stock_status" onchange="this.form.submit()" class="rounded border-stone-300 text-sm">
                <option value="">All stock levels</option>
                <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
            <button class="bg-stone-800 text-white text-sm px-4 py-2 rounded shrink-0">Search</button>
        </form>
        <a href="{{ route('admin.products.create') }}" class="bg-rose-700 hover:bg-rose-800 text-white text-sm px-4 py-2 rounded text-center">Add Product</a>
    </div>

    <div class="bg-white border border-stone-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Stock</th>
                    <th class="px-4 py-3">Active</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($products as $product)
                    @php $status = $product->stockStatus((int) $product->total_stock); @endphp
                    <tr>
                        <td class="px-4 py-3">{{ $product->name_en }}</td>
                        <td class="px-4 py-3">{{ $product->category->name_en }}</td>
                        <td class="px-4 py-3">{{ number_format($product->price) }} EGP</td>
                        <td class="px-4 py-3">
                            {{ $product->total_stock }}
                            <span class="ms-1 inline-block px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $status['status'] === 'out_of_stock' ? 'bg-red-100 text-red-700' : ($status['status'] === 'low_stock' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                                {{ $status['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $product->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-rose-700 underline">Edit</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('Delete this product?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-stone-500 underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
