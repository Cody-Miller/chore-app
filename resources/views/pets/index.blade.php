@props(['pets' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pets') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">
                    @if ($pets && $pets->count() > 0)
                        <div x-data="{ search: '' }">
                            <div class="flex flex-col sm:flex-row justify-between gap-4">
                                <div class="flex-1 min-w-xl">
                                    <input
                                        type="text"
                                        x-model="search"
                                        placeholder="Search pets..."
                                        class="min-w-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500 rounded-md shadow-sm w-full"
                                    >
                                </div>
                                <x-buttons.primary-link-button
                                    class="justify-center"
                                    href="/pets/create"
                                >
                                    Add Pet
                                </x-buttons.primary-link-button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                                @foreach($pets as $pet)
                                    <div x-show="!search || '{{ strtolower($pet->name) }}'.includes(search.toLowerCase())" class="border border-gray-300 dark:border-gray-700 rounded-lg p-4">
                                        <div class="flex items-center gap-4">
                                            @if($pet->photo_path)
                                                <img src="{{ asset('storage/' . $pet->photo_path) }}" alt="{{ $pet->name }}" class="w-20 h-20 rounded-full object-cover">
                                            @else
                                                <div class="w-20 h-20 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                    <span class="text-2xl">🐾</span>
                                                </div>
                                            @endif
                                            <div class="flex-1">
                                                <a href="/pets/{{ $pet->slug }}" class="text-lg font-bold dark:text-indigo-500">{{ $pet->name }}</a>
                                                @if($pet->species)
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $pet->species }}</p>
                                                @endif
                                                @if($pet->breed)
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $pet->breed }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-4 flex gap-2">
                                            <a href="/pets/{{ $pet->slug }}/edit" class="text-sm text-indigo-600 dark:text-indigo-400">Edit</a>
                                            <a href="/pets/{{ $pet->slug }}" class="text-sm text-indigo-600 dark:text-indigo-400">View</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <h3>No pets added yet. Add your first pet!</h3>
                        <x-buttons.primary-link-button
                            class="justify-center mt-4"
                            href="/pets/create"
                        >
                            Add Pet
                        </x-buttons.primary-link-button>
                    @endif

                    @if ($pets)
                        <div class="mt-8">
                            {{ $pets->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
