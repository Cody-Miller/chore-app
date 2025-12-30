@props(['due_now_pills' => null, 'upcoming_pills' => null, 'completed_today_pills' => null, 'users' => null, 'pets' => null])

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pill Dashboard') }}
        </h2>
    </x-slot>
    <div class="pb-12" x-data="{ pillId: '', scheduledTime: '' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h5>Pet Medication Tracker</h5>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @php
                        $all_pills = collect($due_now_pills)->merge($upcoming_pills)->merge($completed_today_pills)->unique('id');
                    @endphp

                    @if($all_pills && count($all_pills) > 0)
                        <h3 class="text-xl font-bold mb-4">{{ __('Today\'s Medications') }}</h3>
                        @foreach($all_pills as $pill)
                            <x-pill-display quick_administer="true" :pill="$pill"/>
                        @endforeach
                    @else
                        <p>No pills scheduled for today! 🎉</p>
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
        document.addEventListener('alpine:init', () => {
            Alpine.data('pillAdministerData', () => ({
                pillId: '',
                scheduledTime: ''
            }));
        });

        // Listen for modal open event
        window.addEventListener('open-modal', (event) => {
            if (event.detail[0] === 'administer_pill') {
                const slug = event.detail[1];
                const data = event.detail[2];

                // Update Alpine data
                const alpineData = Alpine.$data(document.querySelector('[x-data*="pillId"]'));
                if (alpineData) {
                    alpineData.pillId = slug;
                    alpineData.scheduledTime = data?.scheduledTime || '';
                }
            }
        });
    </script>
</x-app-layout>
