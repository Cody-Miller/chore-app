@props(['due_now_chores' => null, 'upcoming_chores' => null, 'one_time_chores' => null, 'snoozed_chores' => null, 'users' => null, 'show_all_chores' => false])

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Chore Dashboard') }}
        </h2>
    </x-slot>
    <div class="pb-12" x-data="{ choreId: '', choreAnchor: '', currentTab: parseInt(new URLSearchParams(window.location.search).get('tab')) || 1 }" @tab-changed.window="currentTab = $event.detail" @open-modal.window="choreAnchor = $event.detail[2] || ''">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h5>Don't be bored... Bee Chored</h5>
                <h5>Since 2021</h5>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('dashboard.toggle-show-all') }}" class="flex justify-end mb-4">
                        @csrf
                        <input type="hidden" name="tab" x-bind:value="currentTab">
                        <label class="inline-flex items-center cursor-pointer gap-3">
                            <span class="text-sm font-semibold">{{ __('Show all chores') }}</span>
                            <input type="checkbox" class="sr-only peer" onchange="this.form.submit()" @checked($show_all_chores)>
                            <span class="relative w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer-focus:ring-2 peer-focus:ring-indigo-500 peer-checked:bg-indigo-500 after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></span>
                        </label>
                    </form>
                    <x-tab.wrapper defaultTabNumber="1">
                        <x-tab.group>
                            <x-tab.link tabNumber="1">
                                {{ __('Reoccurring') }}
                                <span class="hidden sm:inline ml-1 text-xs opacity-75">({{ count($due_now_chores ?? []) }} / {{ count($upcoming_chores ?? []) }})</span>
                            </x-tab.link>
                            <x-tab.link tabNumber="2">
                                {{ __('One-Time') }}
                                <span class="hidden sm:inline ml-1 text-xs opacity-75">({{ count($one_time_chores ?? []) }})</span>
                            </x-tab.link>
                            <x-tab.link tabNumber="3">
                                {{ __('Snoozed') }}
                                <span class="hidden sm:inline ml-1 text-xs opacity-75">({{ count($snoozed_chores ?? []) }})</span>
                            </x-tab.link>
                        </x-tab.group>
                        <x-tab.content tabNumber="1">
                            <div class="text-center mb-6">
                                <h3 class="text-2xl font-bold tracking-wide text-indigo-500 dark:text-indigo-400">{{ __('Due Now') }}</h3>
                                @if($upcoming_chores && count($upcoming_chores))
                                    <a href="#upcoming" class="inline-block mt-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Jump to Upcoming') }} ↓ ({{ count($upcoming_chores) }})</a>
                                @endif
                            </div>
                            @if($due_now_chores && count($due_now_chores))
                                @foreach($due_now_chores as $due_now_chore)
                                    <x-chore-display quick_complete="true" :chore="$due_now_chore"/>
                                @endforeach
                            @else
                                <p class="text-center">No chores due right now, nice job! 😎</p>
                            @endif
                            <div id="upcoming" class="mt-12 pt-8 border-t-2 border-gray-200 dark:border-gray-600 scroll-mt-4">
                                <h3 class="text-center text-2xl font-bold tracking-wide text-indigo-500 dark:text-indigo-400 mb-6">{{ __('Upcoming') }}</h3>
                                @if($upcoming_chores && count($upcoming_chores))
                                    @foreach($upcoming_chores as $upcoming_chore)
                                        <x-chore-display quick_complete="true" :chore="$upcoming_chore" anchor="upcoming"/>
                                    @endforeach
                                @else
                                    <p class="text-center">No upcoming chores, that's a shocker! ⚡</p>
                                @endif
                            </div>
                        </x-tab.content>
                        <x-tab.content tabNumber="2">
                            @if($one_time_chores && count($one_time_chores))
                                @foreach($one_time_chores as $one_time_chore)
                                    <x-chore-display quick_complete="true" :chore="$one_time_chore"/>
                                @endforeach
                            @else
                                <p>No one time chores for once...</p>
                            @endif
                        </x-tab.content>
                        <x-tab.content tabNumber="3">
                            @if($snoozed_chores && count($snoozed_chores))
                                @foreach($snoozed_chores as $snoozed_chore)
                                    <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <span class="block">
                                            <a class="text-lg font-bold dark:text-indigo-500 block mb-1" href="/chores/{{ $snoozed_chore->slug }}">
                                                {{ $snoozed_chore->name }}
                                            </a>
                                            <span class="block whitespace-wrap px-1">{{ $snoozed_chore->description }}</span>
                                            <span class="px-1"><span class="font-semibold">Snoozed until:</span> {{ \Carbon\Carbon::parse($snoozed_chore->snoozed_until, 'UTC')->inViewerTz()->format('m/d/y h:i a') }}</span>
                                            <x-tag-list :tags="$snoozed_chore->tags" class="mt-1 px-1"/>
                                        </span>
                                        <form method="POST" action="{{ route('chores.unsnooze', $snoozed_chore->slug) }}" class="sm:ml-8">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-red-600 dark:bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 dark:hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                                Unsnooze
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            @else
                                <p>No snoozed chores. You're on top of everything! 🎯</p>
                            @endif
                        </x-tab.content>
                    </x-tab.wrapper>

                    <x-chore-complete-modal :users="$users" :includeTab="true" x-bind:action="'/chorelog/' + choreId"/>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
