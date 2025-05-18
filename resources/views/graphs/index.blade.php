@props(['chartWeek', 'chartMonth', 'chartYear'])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Graphs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div class="p-4 m-4 dark:bg-gray-200 sm:rounded-lg shadow">
                        {!! $chartWeek->container() !!}
                    </div>
                    <div class="p-4 m-4 dark:bg-gray-200 sm:rounded-lg shadow">
                        {!! $chartMonth->container() !!}
                    </div>
                    <div class="p-4 m-4 dark:bg-gray-200 sm:rounded-lg shadow">
                        {!! $chartYear->container() !!}
                    </div>
                    
                
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<script src="{{ $chartWeek->cdn() }}"></script>
<script src="{{ $chartMonth->cdn() }}"></script>
<script src="{{ $chartYear->cdn() }}"></script>
{{ $chartWeek->script() }}
{{ $chartMonth->script() }}
{{ $chartYear->script() }}
