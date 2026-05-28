@extends('layouts.app')

@section('title', 'Edit Role Permissions')
@section('page-title', 'Roles & Permissions')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $role->name }} Permissions</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Toggle screen and route access for this role.</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    @if ($role->slug === \App\Models\Role::SLUG_ADMIN)
        <div class="max-w-3xl rounded-lg border border-[#bec8ca] bg-white p-6 text-sm text-[#3e494a]">
            Admin always has full access to all screens and routes.
        </div>
    @else
        <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6" id="role-permissions-form">
            @csrf
            @method('PUT')

            @php
                $selectedPermissionIds = old('permission_ids', $role->permissions->pluck('id')->all());
            @endphp

            <div class="rounded-lg border border-[#bec8ca] bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-[0.06em] text-[#6f797a]">Screen Permissions</h2>
                    <span class="rounded-full bg-[#00535b08] px-3 py-1 text-xs font-medium text-[#00535b]">{{ $screenPermissions->count() }} items</span>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($screenPermissions as $permission)
                        <label class="flex items-start gap-3 rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm text-[#3e494a]">
                            <input
                                type="checkbox"
                                name="permission_ids[]"
                                value="{{ $permission->id }}"
                                data-permission-type="screen"
                                data-screen-key="{{ $permission->screen_key }}"
                                @checked(in_array($permission->id, $selectedPermissionIds, true))
                            >
                            <span>
                                <span class="block font-medium text-[#191c1d]">{{ $permission->name }}</span>
                                <span class="block text-xs">{{ $permission->slug }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-[#bec8ca] bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-[0.06em] text-[#6f797a]">Route Permissions</h2>
                    <span class="rounded-full bg-[#00535b08] px-3 py-1 text-xs font-medium text-[#00535b]">{{ $routePermissionGroups->flatten()->count() }} items</span>
                </div>
                <p class="mb-4 text-xs text-[#6f797a]">Grouped by module to reduce noise and make route access easier to review.</p>
                <div class="space-y-4">
                    @foreach ($routePermissionGroups as $groupName => $groupPermissions)
                        <details class="rounded-xl border border-[#bec8ca] bg-[#f8f9fa] open:bg-white" open>
                            <summary class="cursor-pointer list-none px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-[#191c1d]">{{ $groupName }}</span>
                                    <span class="text-xs text-[#6f797a]">{{ $groupPermissions->count() }} routes</span>
                                </div>
                            </summary>
                            <div class="grid gap-3 border-t border-[#bec8ca] px-4 py-4 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($groupPermissions as $permission)
                                    <label class="flex items-start gap-3 rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm text-[#3e494a]">
                                        <input
                                            type="checkbox"
                                            name="permission_ids[]"
                                            value="{{ $permission->id }}"
                                            data-permission-type="route"
                                            data-requires-screen-key="{{ $routeToScreenKey[$permission->id] ?? '' }}"
                                            @checked(in_array($permission->id, $selectedPermissionIds, true))
                                        >
                                        <span>
                                            <span class="block font-medium text-[#191c1d]">{{ $permission->name }}</span>
                                            <span class="block text-xs">{{ $permission->slug }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save Permissions</button>
                <a href="{{ route('admin.roles.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
            </div>
        </form>

        <script>
            (function () {
                const form = document.getElementById('role-permissions-form');
                if (!form) return;

                const screenCheckboxes = Array.from(form.querySelectorAll('input[type="checkbox"][data-permission-type="screen"]'));
                const routeCheckboxes = Array.from(form.querySelectorAll('input[type="checkbox"][data-permission-type="route"]'));

                function applyScreenDependency() {
                    const enabledScreens = new Set(
                        screenCheckboxes
                            .filter((checkbox) => checkbox.checked)
                            .map((checkbox) => checkbox.dataset.screenKey)
                            .filter(Boolean)
                    );

                    routeCheckboxes.forEach((checkbox) => {
                        const requiredScreen = checkbox.dataset.requiresScreenKey;
                        if (!requiredScreen) {
                            checkbox.disabled = false;
                            return;
                        }

                        const allowed = enabledScreens.has(requiredScreen);
                        checkbox.disabled = !allowed;

                        if (!allowed) {
                            checkbox.checked = false;
                        }
                    });
                }

                screenCheckboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', applyScreenDependency);
                });

                applyScreenDependency();
            })();
        </script>
    @endif
@endsection
