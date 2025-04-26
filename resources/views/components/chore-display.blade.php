@props(['chore' => null, 'quick_complete' => false])
<div class="py-4 flex items-center justify-between">
    <span class="block">
        <a class="text-lg font-bold dark:text-indigo-500 block mb-1" href="/chores/{{ $chore->slug }}">
            {{ $chore->name }}
        </a>
        <span class="block px-1">{{ $chore->description }}</span>
        <span class="px-1"><span class="font-semibold">Weight</span>: {{ $chore->weight }}</span>
        <span class="px-1">
            @if ($chore->hasOccurrences())
                @if($chore->hasOccurrenceMonth())
                    <span class="font-semibold">Occurrence Month:</span> {{ $chore->getOccurrencesMonths() }}
                    <span class="font-semibold">Day:</span> {{ $chore->getOccurrencesDays() }}
                @else
                    <span class="font-semibold">Occurrence Day:</span> {{ $chore->getOccurrencesDays() }}
                @endif
            @endif
        </span>
        <span class="px-1"><span class="font-semibold">Last Completed</span>: {{ __('Cody') }}</span>
    </span>
    @if($quick_complete)
        <x-modal.button class="whitespace-nowrap" name="complete_chore" :slug="$chore->id">
            Quick Complete
        </x-modal.button>
    @endif
</div>
