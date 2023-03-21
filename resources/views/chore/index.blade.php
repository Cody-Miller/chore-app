@props(['chores' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Chores') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($chores)
                        @foreach($chores as $chore)
                            @php
                                var_dump($chore);
                            @endphp
                        @endforeach
                    @else
                        <h3>No chores created yet, get in there and make some!</h3>
                    @endif
                    <x-primary-link-button
                        class="mt-4"
                        href="/chores/create"
                    >
                        Create Chore
                    </x-primary-link-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
