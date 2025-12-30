@props(['pills' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pills') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">
                    @if ($pills && $pills->count() > 0)
                        <div x-data="{ search: '' }">
                            <div class="flex flex-col sm:flex-row justify-between gap-4">
                                <div class="flex-1 min-w-xl">
                                    <input
                                        type="text"
                                        x-model="search"
                                        placeholder="Search medications..."
                                        class="min-w-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                    >
                                </div>
                                <x-buttons.primary-link-button
                                    class="justify-center"
                                    href="/pills/create"
                                >
                                    Add Medication
                                </x-buttons.primary-link-button>
                            </div>

                            <ul class="mx-4">
                                @foreach($pills as $pill)
                                    <li x-show="!search || '{{ strtolower($pill->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($pill->description) }}'.includes(search.toLowerCase()) || '{{ strtolower($pill->pet->name) }}'.includes(search.toLowerCase())">
                                        <x-pill-display :pill="$pill"/>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <h3>No medications added yet. Add your first one!</h3>
                        <x-buttons.primary-link-button
                            class="justify-center mt-4"
                            href="/pills/create"
                        >
                            Add Medication
                        </x-buttons.primary-link-button>
                    @endif

                    @if ($pills)
                        <div class="mt-8">
                            {{ $pills->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
