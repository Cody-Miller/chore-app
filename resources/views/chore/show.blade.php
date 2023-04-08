@props(['chore' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit: Chore Name') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($chore)
                        <div class="mb-4">
                            <p>
                                <strong>{{ $chore->name }}</strong> - {{  $chore->description  }}
                            </p>
                            <span>Weight: {{  $chore->weight  }}</span>
                            <span class="ml-3">Occurance: {{  $chore->occurrence_hours  }}</span>
                        </div>
                        @if (true) <!-- //$chore->hasHistory() -->
                            <div class="mb-4">
                                <h1>Recent Completions</h1>
                                <ul class="list-disc list-inside leading-4">
                                    
                                        <li>10/24/1991 - CJM - 6 points</li>
                                        <li>10/24/1991 - CJM - 6 points</li>
                                        <li>10/24/1991 - CJM - 6 points</li>
                                        <li>10/24/1991 - CJM - 6 points</li>
                                        <li>10/24/1991 - CJM - 6 points</li>
                                        <li>10/24/1991 - CJM - 6 points</li>
                                        <li>10/24/1991 - CJM - 6 points</li>
                                    
                                </ul>
                            </div>
                        @endif
                        <div>
                            <x-primary-link-button
                                class="mt-4"
                                href="/chores/{{ $chore->slug }}/edit"
                                >
                                Edit Chore
                            </x-primary-link-button>

                            <x-modal.button name="deletechore">
                                Delete Chore
                            </x-modal.button>

                            <x-modal.popup name="deletechore">
                                <form method="DELETE" action="/chores/{{ $chore->slug }}" class="p-6">
                                        @csrf
                                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                            Are you sure you want to this chore?
                                        </h2>

                                        <div class="mt-6 flex justify-end">
                                            <button type="button" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150" x-on:click="$dispatch('close')">
                                                Cancel
                                            </button>

                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ml-3">
                                                Delete Chore
                                            </button>
                                        </div>
                                    </form>
                            </x-modal.popup>
                        </div>
                    @else
                        <p>Was not able to find this chore...</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
