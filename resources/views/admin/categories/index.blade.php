@extends('admin.layout')

@section('title', 'Categories')

@section('content')
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.categories.create') }}" class="bg-rose-700 hover:bg-rose-800 text-white text-sm px-4 py-2 rounded">Add Category</a>
    </div>

    <div class="bg-white border border-stone-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Products</th>
                    <th class="px-4 py-3">Active</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($categories as $category)
                    <tr>
                        <td class="px-4 py-3">{{ $category->name_en }}</td>
                        <td class="px-4 py-3">{{ $category->products_count }}</td>
                        <td class="px-4 py-3">{{ $category->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-rose-700 underline">Edit</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete this category?')">
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

    <div class="mt-4">{{ $categories->links() }}</div>
@endsection
