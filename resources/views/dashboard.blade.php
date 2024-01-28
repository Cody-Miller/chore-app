<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h5>Bored? Bee Chored</h5>
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
                            Section 1
                        </x-tab.content>
                        <x-tab.content tabNumber="2">
                            Section 2
                        </x-tab.content>
                        <x-tab.content tabNumber="3">
                            Section 3
                        </x-tab.content>
                    </x-tab.wrapper>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
