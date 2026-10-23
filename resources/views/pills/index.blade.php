@props(['pills' => null, 'tags' => collect(), 'filters' => []])
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
                    @php($hasFilters = !empty($filters['tag']))
                    @if ($pills && ($pills->count() > 0 || $hasFilters))
                        <div x-data="{ search: '' }">
                            <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-4">
                                <input
                                    type="text"
                                    x-model="search"
                                    placeholder="Search medications or tags..."
                                    class="w-full sm:w-auto sm:grow sm:basis-48 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500 rounded-md shadow-sm"
                                >

                                @if ($tags->isNotEmpty() || $hasFilters)
                                    <form method="GET" action="{{ route('pills.index') }}" class="contents">
                                        <select name="tag" onchange="this.form.submit()" aria-label="Filter by tag" class="w-full sm:w-auto dark:bg-gray-900 border-gray-300 dark:border-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500">
                                            <option value="" @selected(empty($filters['tag']))>All tags</option>
                                            @foreach ($tags as $tag)
                                                <option value="{{ $tag->name }}" @selected(($filters['tag'] ?? null) === $tag->name)>{{ $tag->name }}</option>
                                            @endforeach
                                        </select>
                                        <noscript><button type="submit" class="underline">Apply</button></noscript>
                                        @if ($hasFilters)
                                            <a href="{{ route('pills.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Clear filters</a>
                                        @endif
                                    </form>
                                @endif

                                <x-buttons.primary-link-button
                                    class="justify-center sm:ml-auto"
                                    href="/pills/create"
                                >
                                    Add Medication
                                </x-buttons.primary-link-button>
                            </div>

                            <ul class="mx-4">
                                @foreach($pills as $pill)
                                    <li x-show="!search || @js(mb_strtolower($pill->name)).includes(search.toLowerCase()) || @js(mb_strtolower($pill->description ?? '')).includes(search.toLowerCase()) || @js(mb_strtolower($pill->pet->name)).includes(search.toLowerCase()) || @js($pill->tags->map(fn ($tag) => mb_strtolower($tag->name))).some(tag => tag.includes(search.toLowerCase()))">
                                        <x-pill-display :pill="$pill"/>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($pills->count() === 0)
                                <p class="mx-4 mt-6 text-gray-500 dark:text-gray-400">No medications match these filters.</p>
                            @endif
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
