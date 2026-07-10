{{--
    Expects $permissionGroups (config('permission_groups')), $presets
    (config('permission_presets')), and $userPermissions (array of
    currently-granted permission slugs, empty on create).

    Only meaningful for the "employee" role — shown/hidden via the parent
    form's Alpine x-show bound to the role select (see _form.blade.php).
--}}
<div x-data="djPermissionsGrid()" class="space-y-4">
    <div>
        <label class="dj-admin-label">{{ __('users.presets_title') }}</label>
        <div class="flex flex-wrap gap-2">
            @foreach ($presets as $presetKey => $presetPermissions)
                <button
                    type="button"
                    class="dj-admin-btn dj-admin-btn-sm dj-admin-btn-secondary"
                    @click="applyPreset(@js($presetPermissions))"
                >
                    {{ __('users.presets.'.$presetKey) }}
                </button>
            @endforeach
        </div>
    </div>

    <input
        type="text"
        x-model="query"
        placeholder="{{ __('users.search_permissions') }}"
        class="dj-admin-input"
    >

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($permissionGroups as $groupKey => $groupPermissions)
            <fieldset class="dj-admin-card p-3" x-show="groupHasMatch(@js($groupPermissions))">
                <div class="flex items-center justify-between mb-2">
                    <legend class="font-semibold text-sm text-[var(--dj-maroon-dark)]">{{ __('permissions.groups.'.$groupKey) }}</legend>
                    <div class="flex gap-2 text-xs">
                        <button type="button" class="dj-admin-link" @click="selectGroup(@js($groupPermissions))">{{ __('users.select_all') }}</button>
                        <button type="button" class="dj-admin-link-muted" @click="clearGroup(@js($groupPermissions))">{{ __('users.clear_all') }}</button>
                    </div>
                </div>
                <div class="space-y-1.5">
                    @foreach ($groupPermissions as $permission)
                        <label class="dj-admin-checkbox-row" x-show="matches(@js($permission))">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission }}"
                                {{ in_array($permission, $userPermissions ?? old('permissions', []), true) ? 'checked' : '' }}
                            >
                            {{ __('permissions.'.$permission) }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </div>
</div>

@once
    <script>
        function djPermissionsGrid() {
            return {
                query: '',
                matches(permission) {
                    if (!this.query) return true;
                    return permission.toLowerCase().includes(this.query.toLowerCase());
                },
                groupHasMatch(permissions) {
                    return permissions.some((p) => this.matches(p));
                },
                setGroup(permissions, checked) {
                    permissions.forEach((permission) => {
                        const el = document.querySelector(`input[name="permissions[]"][value="${permission}"]`);
                        if (el) el.checked = checked;
                    });
                },
                selectGroup(permissions) { this.setGroup(permissions, true); },
                clearGroup(permissions) { this.setGroup(permissions, false); },
                applyPreset(permissions) {
                    document.querySelectorAll('input[name="permissions[]"]').forEach((el) => el.checked = false);
                    this.setGroup(permissions, true);
                },
            };
        }
    </script>
@endonce
