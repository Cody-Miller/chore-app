@props(['chore' => null, 'quick_complete' => false, 'anchor' => null])
@php
    $completeData = $anchor ? "'{$anchor}'" : null;
@endphp
<div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <span class="block">
        <a class="text-lg font-bold dark:text-indigo-500 block mb-1" href="/chores/{{ $chore->slug }}">
            {{ $chore->name }}
        </a>
        @if($chore->tags->isNotEmpty())
            <span class="flex flex-wrap gap-1 mb-1 px-1">
                @foreach($chore->tags as $tag)
                    <x-tag-badge :tag="$tag"/>
                @endforeach
            </span>
        @endif
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
        @if($chore->assignedUser)
            <span class="px-1"><span class="font-semibold">Assigned To:</span> {{ $chore->assignedUser->name }}</span>
        @endif
        @if($chore->hasCompleted())
            <span class="px-1"><span class="font-semibold">Last Completed:</span> {{ $chore->getLastCompletedUser() }} - {{ $chore->getLastCompletedDate()->format('m/d/y h:i a') }}</span>
        @endif
    </span>
    @if($quick_complete)
        <div class="flex flex-col sm:flex-row gap-2 sm:ml-8 sm:whitespace-nowrap">
            <x-buttons.snooze-button :slug="$chore->slug" class="w-full sm:w-auto">
                Snooze
            </x-buttons.snooze-button>
            <x-modal.button class="w-full sm:w-auto" variant="primary" name="complete_chore" :slug="$chore->slug" :data="$completeData">
                Complete
            </x-modal.button>
        </div>
    @endif
</div>
