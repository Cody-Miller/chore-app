@props(['chore' => null, 'quick_complete' => false])
<div class="py-4 flex items-center justify-between md:flex-nowrap flex-wrap">
    <span class="block">
        <a class="text-lg font-bold dark:text-indigo-500 block mb-1" href="/chores/{{ $chore->slug }}">
            {{ $chore->name }}
        </a>
        <span class="block whitespace-wrap px-1">{{ $chore->description }}</span>
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
        @if($chore->hasCompleted())
            <span class="px-1"><span class="font-semibold">Last Completed:</span> {{ $chore->getLastCompletedUser() }} - {{ $chore->getLastCompletedDate()->format('m/d/y h:i a') }}</span>
        @endif
    </span>
    @if($quick_complete)
        <div class="flex gap-2 ml-8 whitespace-nowrap pt-4">
            <x-modal.button class="dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:ring-blue-500 focus:ring-2 focus:ring-offset-2" name="complete_chore" :slug="$chore->slug">
                Complete
            </x-modal.button>
            <x-buttons.snooze-button :slug="$chore->slug">
                Snooze
            </x-buttons.snooze-button>
        </div>
    @endif
</div>
