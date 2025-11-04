@props(['chores' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Chores') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">
                    @if ($chores && $chores->count() > 0)
                        <div x-data="{ search: '' }">

                            <div class="flex flex-col sm:flex-row justify-between gap-4">
                                <div class="flex-1 min-w-xl">
                                    <input
                                        type="text"
                                        x-model="search"
                                        placeholder="Search chores..."
                                        class="min-w-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                    >
                                </div>
                                <x-buttons.primary-link-button
                                    class="justify-center"
                                    href="/chores/create"
                                >
                                    Create Chore
                                </x-buttons.primary-link-button>
                            </div>

                            <ul class="mx-4">
                                @foreach($chores as $chore)
                                    <li x-show="!search || '{{ strtolower($chore->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($chore->description) }}'.includes(search.toLowerCase())">
                                        <x-chore-display :chore="$chore"/>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <h3>No chores created yet, get in there and make some!</h3>
                    @endif

                    @if ($chores)
                        <div class="mt-8">
                            {{ $chores->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
