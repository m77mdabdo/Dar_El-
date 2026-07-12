@extends('admin.layout')

@section('title', __('categories.title'))

@section('content')
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.categories.create') }}" class="dj-admin-btn dj-admin-btn-primary">+ {{ __('categories.add_category') }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('general.name') }}</th>
                    <th>{{ __('categories.products_count') }}</th>
                    <th>{{ __('general.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">{{ trans_field($category, 'name') }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td><span class="dj-admin-badge {{ $category->is_active ? 'dj-admin-badge-success' : 'dj-admin-badge-neutral' }}">{{ $category->is_active ? __('general.active') : __('general.inactive') }}</span></td>
                        <td class="text-end space-x-3 rtl:space-x-reverse">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="dj-admin-link">{{ __('general.edit') }}</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('{{ __('categories.confirm_delete') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="dj-admin-table-empty">{{ __('categories.no_categories') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>
@endsection
