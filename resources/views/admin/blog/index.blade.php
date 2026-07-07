@extends('admin.layout')

@section('title', 'Blog')

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between gap-3 mb-4">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search posts..." class="w-full sm:w-auto rounded border-stone-300 text-sm">
            <button class="bg-stone-800 text-white text-sm px-4 py-2 rounded shrink-0">Search</button>
        </form>
        <a href="{{ route('admin.blog.create') }}" class="bg-rose-700 hover:bg-rose-800 text-white text-sm px-4 py-2 rounded text-center">Add Post</a>
    </div>

    <div class="bg-white border border-stone-200 rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">Cover</th>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Published</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($posts as $post)
                    <tr>
                        <td class="px-4 py-3">
                            @if ($post->cover_image)
                                <img src="{{ asset('storage/'.$post->cover_image) }}" class="w-12 h-12 object-cover rounded border border-stone-200">
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $post->title_en }}</td>
                        <td class="px-4 py-3">{{ $post->is_published ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3">{{ $post->published_at?->format('M j, Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.blog.edit', $post) }}" class="text-rose-700 underline">Edit</a>
                            <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" class="inline" onsubmit="return confirm('Delete this post?')">
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

    <div class="mt-4">{{ $posts->links() }}</div>
@endsection
