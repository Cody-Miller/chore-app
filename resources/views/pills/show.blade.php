@props(['pill' => null, 'users' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pill->name }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ pillId: '{{ $pill->slug }}', scheduledTime: '', limit: 5 }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold mb-2">{{ $pill->name }}</h3>
                        <p><span class="font-semibold">Pet:</span> <a href="/pets/{{ $pill->pet->slug }}" class="text-indigo-600 dark:text-indigo-400">{{ $pill->pet->name }}</a></p>
                        <p><span class="font-semibold">Dosage:</span> {{ $pill->dosage }}</p>
                        @if($pill->description)
                            <p class="mt-2"><span class="font-semibold">Description:</span> {{ $pill->description }}</p>
                        @endif
                        <p class="mt-2">
                            <span class="font-semibold">Scheduled Times:</span>
                            @foreach($pill->scheduled_times as $time)
                                @if($pill->hasBeenGivenAt($time))
                                    <span class="text-green-600 dark:text-green-400">✓ {{ date('g:i A', strtotime($time)) }}</span>
                                @else
                                    <span class="text-gray-600 dark:text-gray-400">○ {{ date('g:i A', strtotime($time)) }}</span>
                                @endif
                            @endforeach
                        </p>

                        <div class="flex gap-2 mt-4 flex-wrap">
                            <x-buttons.primary-link-button href="/pills/{{ $pill->slug }}/edit">
                                Edit Medication
                            </x-buttons.primary-link-button>
                            @foreach($pill->scheduled_times as $time)
                                @if(!$pill->hasBeenGivenAt($time))
                                    <x-modal.button
                                        class="dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:ring-blue-500 focus:ring-2 focus:ring-offset-2"
                                        name="administer_pill"
                                        :slug="$pill->slug"
                                        :data="json_encode(['scheduledTime' => $time])"
                                        @click="scheduledTime = '{{ $time }}'"
                                    >
                                        Give {{ date('g:i A', strtotime($time)) }}
                                    </x-modal.button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <hr class="my-6 border-gray-300 dark:border-gray-700">

                    <h4 class="text-xl font-bold mb-4">Recent Administrations</h4>
                    @if($pill->pillLogs && $pill->pillLogs->count() > 0)
                        <ul class="space-y-2">
                            @foreach($pill->pillLogs->take(50) as $index => $log)
                                <li x-show="{{ $index }} < limit" class="text-sm">
                                    <span class="font-semibold">{{ $log->administered_at->format('m/d/y h:i a') }}</span>
                                    - {{ $log->user?->name }}
                                    ({{ date('g:i A', strtotime($log->scheduled_time)) }} dose)
                                    @if($log->notes)
                                        <span class="text-gray-600 dark:text-gray-400">- {{ $log->notes }}</span>
                                    @endif
                                    <a href="/pilllogs/{{ $log->id }}/edit" class="text-indigo-600 dark:text-indigo-400 ml-2">Edit</a>
                                </li>
                            @endforeach
                        </ul>
                        @if($pill->pillLogs->count() > 5)
                            <button
                                @click="limit += 10"
                                x-show="limit < {{ $pill->pillLogs->count() }}"
                                class="mt-4 px-4 py-2 bg-gray-600 text-white rounded-md text-sm"
                            >
                                See More
                            </button>
                        @endif
                    @else
                        <p class="text-gray-600 dark:text-gray-400">No administrations recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <x-pill-administer-modal
            x-bind:action="'/pilllogs/' + pillId"
            :users="$users"
        />
    </div>

    <script>
        window.addEventListener('open-modal', (event) => {
            if (event.detail[0] === 'administer_pill') {
                const data = event.detail[2];
                const alpineData = Alpine.$data(document.querySelector('[x-data*="pillId"]'));
                if (alpineData && data) {
                    alpineData.scheduledTime = data.scheduledTime || '';
                }
            }
        });
    </script>
</x-app-layout>
