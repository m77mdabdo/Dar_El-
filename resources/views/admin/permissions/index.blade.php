@extends('admin.layout')

@section('title', __('permissions.groups.roles_permissions'))

@section('content')
    @php
        $djLabeledGroups = collect($permissionGroups)->map(
            fn ($slugs, $groupKey) => [
                'label' => __('permissions.groups.'.$groupKey),
                'permissions' => collect($slugs)->map(fn ($slug) => ['slug' => $slug, 'label' => __('permissions.'.$slug)])->all(),
            ]
        )->values();
    @endphp

    <div x-data="djPermissionsCatalog(@js($djLabeledGroups))" class="space-y-4">
        <input
            type="text"
            x-model="query"
            placeholder="{{ __('users.search_permissions') }}"
            class="dj-admin-input max-w-md"
        >

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="group in filteredGroups()" :key="group.label">
                <div class="dj-admin-card p-3" x-show="group.permissions.length > 0">
                    <h3 class="font-semibold text-sm text-[var(--dj-maroon-dark)] mb-2" x-text="group.label"></h3>
                    <ul class="space-y-1.5 text-sm">
                        <template x-for="permission in group.permissions" :key="permission.slug">
                            <li class="flex items-center justify-between gap-2 text-[var(--dj-ink)]">
                                <span x-text="permission.label"></span>
                                <code class="text-xs text-[var(--dj-rose-dust)]" x-text="permission.slug"></code>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
    </div>

    <script>
        function djPermissionsCatalog(groups) {
            return {
                query: '',
                groups,
                filteredGroups() {
                    if (!this.query) return this.groups;
                    const q = this.query.toLowerCase();
                    return this.groups.map((group) => ({
                        ...group,
                        permissions: group.permissions.filter((p) => p.label.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q)),
                    }));
                },
            };
        }
    </script>
@endsection
