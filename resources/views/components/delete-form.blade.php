@props(['action', 'confirm' => 'this record'])

<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm('Delete {{ $confirm }}? This cannot be undone.');">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'text-sm font-medium text-red-700 hover:text-red-900']) }}>
        {{ $slot->isEmpty() ? 'Delete' : $slot }}
    </button>
</form>
