@props(['pill' => null, 'quick_administer' => false])
<div class="py-4 flex items-center justify-between md:flex-nowrap flex-wrap">
    <span class="block">
        <a class="text-lg font-bold dark:text-indigo-500 block mb-1" href="/pills/{{ $pill->slug }}">
            {{ $pill->name }}
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
            <span class="block px-1"><span class="font-semibold">Last Given:</span> {{ $pill->getLastAdministeredUser() }} - {{ $pill->getLastAdministeredDate()->format('m/d/y h:i a') }}</span>
        @endif
    </span>
    @if($quick_administer)
        <div class="flex gap-2 ml-8 whitespace-nowrap pt-4 flex-wrap">
            @foreach($pill->scheduled_times as $time)
                @if(!$pill->hasBeenGivenAt($time))
                    <x-modal.button
                        class="dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:ring-blue-500 focus:ring-2 focus:ring-offset-2"
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
