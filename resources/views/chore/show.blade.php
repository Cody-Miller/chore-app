@props(['chore' => null, 'users' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit: Chore Name') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">
                    @if ($chore)
                        <div class="mb-4">
                            <x-chore-display :chore="$chore"/>
                        </div>
                        <div class="mb-4" x-data="{ limit: 5 }">
                            @if ($chore->choreLogs && count($chore->choreLogs) > 0)
                                <h1>Recent Completions</h1>
                                <ul class="list-disc list-inside leading-4">
                                    @foreach($chore->choreLogs->take(50) as $index => $choreLog)
                                        <li x-show="{{ $index }} < limit">
                                            {{ $choreLog->completed_at->format('m/d/y h:i a') }}
                                            - {{ $choreLog->user?->name }}
                                        </li>
                                    @endforeach
                                </ul>
                                @if(count($chore->choreLogs) > 3)
                                    <button
                                        x-show="limit < {{ count($chore->choreLogs) }}"
                                        x-on:click="limit += 10"
                                        class="mt-2 text-sm text-blue-500 hover:text-blue-700"
                                    >
                                        See More
                                    </button>
                                @endif
                            @endif
                        </div>
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-3">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <x-buttons.primary-link-button
                                    href="/chores/{{ $chore->slug }}/edit"
                                    class="w-full sm:w-auto"
                                >
                                    Edit Chore
                                </x-buttons.primary-link-button>
                                <x-modal.button name="deletechore" class="w-full sm:w-auto">
                                    Delete Chore
                                </x-modal.button>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:justify-end">
                                <x-modal.button name="complete_chore" class="w-full sm:w-auto whitespace-nowrap dark:bg-thunder-200">
                                    Complete Chore
                                </x-modal.button>
                            </div>
                        </div>
                    @else
                        <p>Was not able to find this chore...</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-modal.popup name="deletechore">
        <form method="POST" action="/chores/{{ $chore->slug }}" class="p-6">
            @csrf
            @method('DELETE')
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Are you sure you want to this chore?
            </h2>

            <x-modal.confirm-actions confirmText="Delete Chore" />
        </form>
    </x-modal.popup>

    <x-chore-complete-modal :users="$users" action="/chorelog/{{ $chore->slug }}"/>

</x-app-layout>
