@props(['pillLogs' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pill Administration Log') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="m-4 text-gray-900 dark:text-gray-100 border-2 dark:border-thunder-200 overflow-x-auto">
                    @if ($pillLogs && $pillLogs->count() > 0)
                        <x-table.wrapper>
                            <x-table.head-row>
                                <x-table.head>Date</x-table.head>
                                <x-table.head>Time</x-table.head>
                                <x-table.head>User</x-table.head>
                                <x-table.head>Medication</x-table.head>
                                <x-table.head>Pet</x-table.head>
                                <x-table.head>Scheduled Time</x-table.head>
                                <x-table.head>Notes</x-table.head>
                                <x-table.head>Edit</x-table.head>
                            </x-table.head-row>
                            <tbody>
                            @foreach($pillLogs as $pillLog)
                                <x-table.body-row>
                                    <x-table.body-data-number>{{ $pillLog->administered_at->format('m/d/y') }}</x-table.body-data-number>
                                    <x-table.body-data-number>{{ $pillLog->administered_at->format('h:i a') }}</x-table.body-data-number>
                                    <x-table.body-data-text>{{ $pillLog->user?->name }}</x-table.body-data-text>
                                    <x-table.body-data-text class="dark:text-indigo-500 underline">
                                        <a href="/pills/{{ $pillLog->pill?->slug }}">{{ $pillLog->pill->name }}</a>
                                    </x-table.body-data-text>
                                    <x-table.body-data-text class="dark:text-indigo-500 underline">
                                        <a href="/pets/{{ $pillLog->pill?->pet?->slug }}">{{ $pillLog->pill?->pet?->name }}</a>
                                    </x-table.body-data-text>
                                    <x-table.body-data-number>{{ date('g:i A', strtotime($pillLog->scheduled_time)) }}</x-table.body-data-number>
                                    <x-table.body-data-text>{{ $pillLog->notes ?? '-' }}</x-table.body-data-text>
                                    <x-table.body-data-text>
                                        <x-buttons.primary-link-button href="/pilllogs/{{ $pillLog->id }}/edit" >Edit</x-buttons.primary-link-button>
                                    </x-table.body-data-text>
                                </x-table.body-row>
                            @endforeach
                            </tbody>
                        </x-table.wrapper>
                    @else
                        <h3>No pills administered yet. Start tracking medication!</h3>
                    @endif
                </div>
                @if ($pillLogs && $pillLogs->count() > 0)
                    <div class="mt-8 m-6">
                        {{ $pillLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
