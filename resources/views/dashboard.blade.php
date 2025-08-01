@php use App\Models\User; @endphp
@props(['due_now_chores' => null, 'upcoming_chores' => null, 'one_time_chores' => null])

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
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
                        <x-tab.group class="overflow-x-auto">
                            <x-tab.link tabNumber="1">
                                {{ __('Due Now') }}
                            </x-tab.link>
                            <x-tab.link tabNumber="2">
                                {{ __('Upcoming') }}
                            </x-tab.link>
                            <x-tab.link tabNumber="3">
                                {{ __('Non-Reoccuring') }}
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
                    </x-tab.wrapper>

                    <x-chore-complete-modal :users="User::all()" x-bind:action="'/chorelog/' + choreId"/>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
