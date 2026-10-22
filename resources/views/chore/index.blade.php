@props(['chores' => null, 'users' => collect(), 'tags' => collect(), 'filters' => []])
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
                    @php($hasFilters = !empty($filters['occurrence']) || !empty($filters['assigned']) || !empty($filters['tag']))
                    @if ($chores && ($chores->count() > 0 || $hasFilters))
                        <div x-data="{ search: '' }">

                            <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-4">
                                <input
                                    type="text"
                                    x-model="search"
                                    placeholder="Search chores or tags..."
                                    class="w-full sm:w-64 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500 rounded-md shadow-sm"
                                >

                                <form method="GET" action="{{ route('chores.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-4">
                                    <select name="occurrence" onchange="this.form.submit()" aria-label="Filter by occurrence" class="w-full sm:w-auto dark:bg-gray-900 border-gray-300 dark:border-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500">
                                        <option value="" @selected(empty($filters['occurrence']))>All occurrences</option>
                                        <option value="recurring" @selected(($filters['occurrence'] ?? null) === 'recurring')>Recurring</option>
                                        <option value="one-time" @selected(($filters['occurrence'] ?? null) === 'one-time')>One-time (doesn't re-occur)</option>
                                    </select>
                                    <select name="assigned" onchange="this.form.submit()" aria-label="Filter by assignment" class="w-full sm:w-auto dark:bg-gray-900 border-gray-300 dark:border-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500">
                                        <option value="" @selected(empty($filters['assigned']))>All chores</option>
                                        <option value="everyone" @selected(($filters['assigned'] ?? null) === 'everyone')>For everyone (unassigned)</option>
                                        <option value="assigned" @selected(($filters['assigned'] ?? null) === 'assigned')>Assigned to anyone</option>
                                        <optgroup label="Assigned to">
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}" @selected(($filters['assigned'] ?? null) == $user->id)>{{ $user->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                    @if ($tags->isNotEmpty() || !empty($filters['tag']))
                                        <select name="tag" onchange="this.form.submit()" aria-label="Filter by tag" class="w-full sm:w-auto dark:bg-gray-900 border-gray-300 dark:border-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500">
                                            <option value="" @selected(empty($filters['tag']))>All tags</option>
                                            @foreach ($tags as $tag)
                                                <option value="{{ $tag->name }}" @selected(($filters['tag'] ?? null) === $tag->name)>{{ $tag->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <noscript><button type="submit" class="underline">Apply</button></noscript>
                                    @if ($hasFilters)
                                        <a href="{{ route('chores.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Clear filters</a>
                                    @endif
                                </form>

                                <x-buttons.primary-link-button
                                    class="justify-center sm:ml-auto"
                                    href="/chores/create"
                                >
                                    Create Chore
                                </x-buttons.primary-link-button>
                            </div>

                            <ul class="mx-4">
                                @foreach($chores as $chore)
                                    <li x-show="!search || '{{ strtolower($chore->name) }}'.includes(search.toLowerCase()) || '{{ strtolower($chore->description) }}'.includes(search.toLowerCase()) || @js($chore->tags->map(fn ($tag) => mb_strtolower($tag->name))).some(tag => tag.includes(search.toLowerCase()))">
                                        <x-chore-display quick_complete="true" :chore="$chore"/>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($chores->count() === 0)
                                <p class="mx-4 mt-6 text-gray-500 dark:text-gray-400">No chores match these filters.</p>
                            @endif

                            <x-chore-complete-modal :users="$users" :redirectTo="url()->full()" x-bind:action="'/chorelog/' + choreId"/>
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
