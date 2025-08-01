@props(['chartWeek' => false, 'chartWeekWeighted' => false, 'chartMonth' => false, 'chartYear' => false])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Graphs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                @if($chartWeek)
                    <div class="p-4 m-4 dark:bg-gray-200 sm:rounded-lg shadow">
                        {!! $chartWeek->container() !!}
                    </div>
                @endif
                @if($chartWeekWeighted)
                    <div class="p-4 m-4 dark:bg-gray-200 sm:rounded-lg shadow">
                        {!! $chartWeekWeighted->container() !!}
                    </div>
                @endif
                @if($chartMonth)
                    <div class="p-4 m-4 dark:bg-gray-200 sm:rounded-lg shadow">
                        {!! $chartMonth->container() !!}
                    </div>
                @endif
                @if($chartYear)
                    <div class="p-4 m-4 dark:bg-gray-200 sm:rounded-lg shadow">
                        {!! $chartYear->container() !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
@if($chartWeek) <script src="{{ $chartWeek->cdn() }}"></script> @endif
{{--@if($chartWeekWeighted) <script src="{{ $chartWeekWeighted->cdn() }}"></script> @endif--}}
@if($chartMonth) <script src="{{ $chartMonth->cdn() }}"></script> @endif
@if($chartYear) <script src="{{ $chartYear->cdn() }}"></script> @endif
@if($chartWeek) {{ $chartWeek->script() }} @endif
@if($chartWeekWeighted) {{ $chartWeekWeighted->script() }} @endif
@if($chartMonth) {{ $chartMonth->script() }} @endif
@if($chartYear) {{ $chartYear->script() }} @endif
