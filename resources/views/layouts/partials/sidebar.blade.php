@php
    use App\Domain\Administration\Services\PermissionResolver;
    use App\Support\NavigationMenu;

    $navigationGroups = NavigationMenu::groupsFor(auth()->user());
    $canAccessPos = app(PermissionResolver::class)->canAccessScreen(auth()->user(), 'sales.pos');

    $initialOpenGroups = collect($navigationGroups)
        ->filter(fn (array $group): bool => collect($group['items'])->contains(
            fn (array $item): bool => request()->routeIs($item['match'])
        ))
        ->pluck('key')
        ->values();

    if ($initialOpenGroups->isEmpty() && count($navigationGroups) > 0) {
        $initialOpenGroups = collect([$navigationGroups[0]['key']]);
    }
@endphp

<div
    class="fixed inset-0 z-40 bg-black/30 xl:hidden"
    x-show="sidebarOpen"
    x-transition.opacity
    x-on:click="sidebarOpen = false"
    style="display: none;"
></div>

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[260px] flex-col border-r border-[#bec8ca] bg-[#f8f9fa] px-4 py-6 transition-transform duration-200 xl:z-30"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full xl:translate-x-0'"
>
    <div class="mb-2 flex items-center justify-end xl:hidden">
        <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded border border-[#bec8ca] bg-white text-[#00535b]"
            x-on:click="sidebarOpen = false"
            aria-label="Close navigation"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12"/>
            </svg>
        </button>
    </div>
    <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3 px-2 pb-6">
        <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded border border-[#bec8ca] bg-white">
            <img src="{{ asset('images/zlmnlogo.jpg') }}" alt="Zarli Min Nwe Clinic logo" class="h-full w-full object-cover">
        </span>
        <span class="min-w-0">
            <span class="block text-2xl font-bold leading-tight text-[#00535b]">ZLMN</span>
            <span class="block truncate text-xs font-medium tracking-[0.06em] text-[#3e494a]">Clinic Management</span>
        </span>
    </a>

    @if ($canAccessPos)
        <div class="shrink-0 px-2 pb-4">
            <a href="{{ route('sales.pos') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#00535b] px-4 py-3 text-xs font-medium tracking-[0.06em] text-white shadow-sm">
                <span class="text-base leading-none">+</span>
                Go to POS
            </a>
        </div>
    @endif

    <nav
        class="flex min-h-0 flex-1 flex-col"
        x-data="{
            openGroups: @js($initialOpenGroups->all()),
            toggleGroup(key) {
                if (this.openGroups.includes(key)) {
                    this.openGroups = this.openGroups.filter((groupKey) => groupKey !== key);
                } else {
                    this.openGroups.push(key);
                }
            },
            isGroupOpen(key) {
                return this.openGroups.includes(key);
            },
        }"
    >
        <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
            @foreach ($navigationGroups as $group)
                <div class="rounded-lg border border-transparent">
                    <button
                        type="button"
                        x-on:click="toggleGroup('{{ $group['key'] }}')"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left transition hover:bg-white"
                        :class="isGroupOpen('{{ $group['key'] }}') ? 'bg-white shadow-sm' : ''"
                    >
                        <span class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#6f797a]">
                            {{ $group['label'] }}
                        </span>
                        <svg
                            class="h-4 w-4 shrink-0 text-[#6f797a] transition-transform duration-200"
                            :class="isGroupOpen('{{ $group['key'] }}') ? 'rotate-180' : ''"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div
                        x-show="isGroupOpen('{{ $group['key'] }}')"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mt-1 space-y-1 pb-2 pl-1"
                        style="display: none;"
                    >
                        @foreach ($group['items'] as $item)
                            @php($active = request()->routeIs($item['match']))
                            <a
                                href="{{ route($item['route']) }}"
                                x-on:click="sidebarOpen = false"
                                class="flex items-center gap-3 rounded px-3 py-2.5 text-sm transition {{ $active ? 'bg-[#00535b08] text-[#00535b]' : 'text-[#3e494a] hover:bg-white hover:text-[#00535b]' }}"
                            >
                                <span class="flex h-5 w-5 items-center justify-center rounded text-[11px] font-semibold {{ $active ? 'bg-[#00535b] text-white' : 'bg-[#e1e3e4] text-[#3e494a]' }}">
                                    {{ $item['icon'] }}
                                </span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </nav>

    <div class="mt-4 shrink-0 border-t border-[#bec8ca] pt-4">
        <div class="flex items-center gap-3 rounded bg-white px-3 py-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#e1e3e4] text-sm font-semibold text-[#00535b]">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-[#191c1d]">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs capitalize text-[#3e494a]">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
            </div>
        </div>
    </div>
</aside>
