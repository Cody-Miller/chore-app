@props(['pill' => null, 'quick_administer' => false])
<div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <span class="block">
        <a class="text-lg font-bold dark:text-indigo-500 block mb-1" href="/pills/{{ $pill->slug }}">
            {{ $pill->name }}
            <x-pill-inactive-badge :pill="$pill" class="ml-1" />
        </a>
        <span class="block whitespace-wrap px-1">{{ $pill->description }}</span>
        <span class="px-1"><span class="font-semibold">Pet:</span> <a href="/pets/{{ $pill->pet->slug }}" class="text-indigo-600 dark:text-indigo-400">{{ $pill->pet->name }}</a></span>
        <span class="px-1"><span class="font-semibold">Dosage:</span> {{ $pill->dosage }}</span>
        <span class="px-1">
            <span class="font-semibold">Scheduled:</span>
            @foreach($pill->scheduled_times as $time)
                @if($pill->hasBeenGivenAt($time))
                    <span class="text-green-600 dark:text-green-400">✓ {{ date('g:i A', strtotime($time)) }}</span>
                @else
                    <span class="text-gray-600 dark:text-gray-400">○ {{ date('g:i A', strtotime($time)) }}</span>
                @endif
            @endforeach
        </span>
        @if($pill->getLastLog())
            <span class="block px-1"><span class="font-semibold">Last Given:</span> {{ $pill->getLastAdministeredUser() }} - {{ $pill->getLastAdministeredDate()->inViewerTz()->format('m/d/y h:i a') }}</span>
        @endif
        <x-tag-list :tags="$pill->tags" class="mt-1 px-1"/>
    </span>
    @if($quick_administer)
        <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 sm:ml-8 sm:whitespace-nowrap">
            @foreach($pill->scheduled_times as $time)
                @if(!$pill->hasBeenGivenAt($time))
                    <x-modal.button
                        class="w-full sm:w-auto"
                        variant="primary"
                        name="administer_pill"
                        :slug="$pill->slug"
                        :data="json_encode(['scheduledTime' => $time])"
                    >
                        Give {{ date('g:i A', strtotime($time)) }}
                    </x-modal.button>
                @endif
            @endforeach
        </div>
    @endif
</div>
