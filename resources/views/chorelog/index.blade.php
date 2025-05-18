@props(['choreLogs' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Completion Log') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="m-4 text-gray-900 dark:text-gray-100 border-2 dark:border-thunder-200 overflow-x-auto">
                    @if ($choreLogs && $choreLogs->count() > 0)
                        <x-table.wrapper>
                            <x-table.head-row>
                                <x-table.head>Date</x-table.head>
                                <x-table.head>Time</x-table.head>
                                <x-table.head>User</x-table.head>
                                <x-table.head>Chore</x-table.head>
                                <x-table.head>Weight</x-table.head>
                                <x-table.head>Edit</x-table.head>
                            </x-table.head-row>
                            <tbody>
                            @foreach($choreLogs as $choreLog)
                                <x-table.body-row>
                                    <x-table.body-data-number>{{ $choreLog->completed_at->format('m/d/y') }}</x-table.body-data-number>
                                    <x-table.body-data-number>{{ $choreLog->completed_at->format('h:i a') }}</x-table.body-data-number>
                                    <x-table.body-data-text>{{ $choreLog->user?->name }}</x-table.body-data-text>
                                    <x-table.body-data-text class="dark:text-indigo-500 underline">
                                        <a href="/chores/{{ $choreLog->chore?->slug }}">{{ $choreLog->chore->name }}</a>
                                    </x-table.body-data-text>
                                    <x-table.body-data-number>{{ $choreLog->chore?->weight }}</x-table.body-data-number>
                                    <x-table.body-data-text>
                                        <x-primary-link-button href="/chorelog/{{ $choreLog->id }}/edit" >Edit</x-primary-link-button>
                                    </x-table.body-data-text>
                                </x-table.body-row>
                            @endforeach
                            </tbody>
                        </x-table.wrapper>
                    @else
                        <h3>No chores completed yet, get in there and get some done!</h3>
                    @endif
                </div>
                @if ($choreLogs && $choreLogs->count() > 0)
                    <div class="mt-8 m-6">
                        {{ $choreLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
