@props(['due_now_chores' => null, 'upcoming_chores' => null, 'one_time_chores' => null])

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    <div class="pb-12" x-data="{ slug: '' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h5>Don't be bored... Bee Chored</h5>
                <h5>Since 2021</h5>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
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

                    <x-modal.popup name="complete_chore">
                        <form class="p-6" method="POST" x-bind:action="'/chorelog/' + slug">
                            @csrf
                            @method('POST')
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Are you sure you want to Complete this chore?
                            </h2>

                            <div class="mt-6 flex justify-end">
                                <x-secondary-button class="px-4 py-2 mx-2" x-on:click="$dispatch('close')">
                                    Cancel
                                </x-secondary-button>

                                <x-primary-button class="px-4 py-2 mx-2" type="submit">
                                    Complete Chore
                                </x-primary-button>
                            </div>

{{--                            <div class="mt-6 flex justify-end">--}}
{{--                                <button type="button"--}}
{{--                                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"--}}
{{--                                        x-on:click="$dispatch('close')">--}}
{{--                                    Cancel--}}
{{--                                </button>--}}

{{--                                <button type="submit"--}}
{{--                                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ml-3">--}}
{{--                                    Complete Chore--}}
{{--                                </button>--}}
{{--                            </div>--}}
                        </form>
                    </x-modal.popup>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
