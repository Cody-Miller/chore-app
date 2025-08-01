@php use App\Models\User; @endphp
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
                        <div class="flex justify-between">
                            <div class="flex justify-around space-x-4">
                                <x-buttons.primary-link-button
                                    href="/chores/{{ $chore->slug }}/edit"
                                >
                                    Edit Chore
                                </x-buttons.primary-link-button>
                                <x-modal.button name="complete_chore" class="whitespace-nowrap dark:bg-thunder-200">
                                    Complete Chore
                                </x-modal.button>
                            </div>
                            <div class="flex justify-end">
                                <x-modal.button name="deletechore">
                                    Delete Chore
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

            <div class="mt-6 flex justify-end">
                <button type="button"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                        x-on:click="$dispatch('close')">
                    Cancel
                </button>

                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ml-3">
                    Delete Chore
                </button>
            </div>
        </form>
    </x-modal.popup>

    <x-chore-complete-modal :users="User::all()" action="/chorelog/{{ $chore->id }}"/>

</x-app-layout>
