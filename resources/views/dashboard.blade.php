@props(['due_now_chores' => null, 'upcoming_chores' => null, 'one_time_chores' => null, 'snoozed_chores' => null, 'users' => null])

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Chore Dashboard') }}
        </h2>
    </x-slot>
    <div class="pb-12" x-data="{ choreId: '' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h5>Don't be bored... Bee Chored</h5>
                <h5>Since 2021</h5>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">
                    <x-tab.wrapper defaultTabNumber="1">
                        <x-tab.group>
                            <x-tab.link tabNumber="1">
                                {{ __('Due Now') }}
                            </x-tab.link>
                            <x-tab.link tabNumber="2">
                                {{ __('Upcoming') }}
                            </x-tab.link>
                            <x-tab.link tabNumber="3">
                                {{ __('Non-Reoccuring') }}
                            </x-tab.link>
                            <x-tab.link tabNumber="4">
                                {{ __('Snoozed') }}
                            </x-tab.link>
                        </x-tab.group>
                        <x-tab.content tabNumber="1">
                            @if($due_now_chores && count($due_now_chores))
                                @foreach($due_now_chores as $due_now_chore)
                                    <x-chore-display quick_complete="true" :chore="$due_now_chore"/>
                                @endforeach
                            @else
                                <p>No chores do right now nice job! 😎</p>
                            @endif
                        </x-tab.content>
                        <x-tab.content tabNumber="2">
                            @if($upcoming_chores && count($upcoming_chores))
                                @foreach($upcoming_chores as $upcoming_chore)
                                    <x-chore-display quick_complete="true" :chore="$upcoming_chore"/>
                                @endforeach
                            @else
                                <p>No upcoming chores, that's a shocker! ⚡</p>
                            @endif
                        </x-tab.content>
                        <x-tab.content tabNumber="3">
                            @if($one_time_chores && count($one_time_chores))
                                @foreach($one_time_chores as $one_time_chore)
                                    <x-chore-display quick_complete="true" :chore="$one_time_chore"/>
                                @endforeach
                            @else
                                <p>No one time chores for once...</p>
                            @endif
                        </x-tab.content>
                        <x-tab.content tabNumber="4">
                            @if($snoozed_chores && count($snoozed_chores))
                                @foreach($snoozed_chores as $snoozed_chore)
                                    <div class="py-4 flex items-center justify-between md:flex-nowrap flex-wrap">
                                        <span class="block">
                                            <a class="text-lg font-bold dark:text-indigo-500 block mb-1" href="/chores/{{ $snoozed_chore->slug }}">
                                                {{ $snoozed_chore->name }}
                                            </a>
                                            <span class="block whitespace-wrap px-1">{{ $snoozed_chore->description }}</span>
                                            <span class="px-1"><span class="font-semibold">Snoozed until:</span> {{ \Carbon\Carbon::parse($snoozed_chore->snoozed_until)->format('m/d/y h:i a') }}</span>
                                        </span>
                                        <form method="POST" action="{{ route('chores.unsnooze', $snoozed_chore->slug) }}" class="ml-8">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 dark:bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 dark:hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
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

                    <x-chore-complete-modal :users="$users" x-bind:action="'/chorelog/' + choreId"/>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
