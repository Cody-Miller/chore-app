@php use App\Models\ChoreSnooze; @endphp
@props(['slug'])

@php
    $snoozeOptions = ChoreSnooze::getSnoozeOptions();
@endphp

<div x-data="{ open: false }" class="relative">
    <button
        @click="open = !open"
        type="button"
        {{
            $attributes->merge([
                'class' => 'inline-flex items-center justify-center px-4 py-2 bg-thunder-200 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-thunder-100 focus:outline-none focus:ring-2 focus:ring-thunder-200 focus:ring-offset-2 dark:focus:ring-offset-thunder-500 transition ease-in-out duration-150'
            ])
        }}
    >
        {{ $slot ?? 'Snooze' }}
    </button>
    <div
        x-show="open"
        @click.away="open = false"
        x-cloak
        class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-10"
    >
        <div class="py-1">
            <form method="POST" action="{{ route('chores.snooze', $slug) }}">
                @csrf
                @foreach($snoozeOptions as $hours => $label)
                    <button
                        type="submit"
                        name="hours"
                        value="{{ $hours }}"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </form>
        </div>
    </div>
</div>
