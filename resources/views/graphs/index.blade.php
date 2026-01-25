@props(['chartWeek' => false, 'chartWeekWeighted' => false, 'chartMonth' => false, 'chartYear' => false, 'chartChoreFrequency' => false, 'chartChoreRate' => false, 'chartPillMissedDoses' => false, 'weekStartDate' => '', 'weekEndDate' => '', 'monthStartDate' => '', 'monthEndDate' => '', 'pillStartDate' => '', 'pillEndDate' => ''])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Graphs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Week Charts Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">User Activity by Day of Week</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">View chore completions broken down by weekday for each user, showing both counts and weighted totals.</p>
                    <form method="GET" action="{{ route('graphs.index') }}" class="mb-6">
                        <!-- Preserve month date range if it exists -->
                        <input type="hidden" name="month_start_date" value="{{ $monthStartDate }}">
                        <input type="hidden" name="month_end_date" value="{{ $monthEndDate }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="week_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Start Date
                                </label>
                                <input type="date" id="week_start_date" name="week_start_date"
                                       value="{{ $weekStartDate }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="week_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    End Date
                                </label>
                                <input type="date" id="week_end_date" name="week_end_date"
                                       value="{{ $weekEndDate }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Apply Filter
                        </button>
                    </form>

                    @if($chartWeek)
                        <div class="p-4 mb-4 bg-gray-50 dark:bg-gray-200 sm:rounded-lg shadow">
                            {!! $chartWeek->container() !!}
                        </div>
                    @endif
                    @if($chartWeekWeighted)
                        <div class="p-4 bg-gray-50 dark:bg-gray-200 sm:rounded-lg shadow">
                            {!! $chartWeekWeighted->container() !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Custom Period & Chore Charts Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">User Contribution & Chore Completion Analysis</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Compare user contributions by percentage, identify the most frequently completed chores, and see which recurring chores are falling behind their expected schedule.</p>
                    <form method="GET" action="{{ route('graphs.index') }}" class="mb-6">
                        <!-- Preserve week date range if it exists -->
                        <input type="hidden" name="week_start_date" value="{{ $weekStartDate }}">
                        <input type="hidden" name="week_end_date" value="{{ $weekEndDate }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="month_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Start Date
                                </label>
                                <input type="date" id="month_start_date" name="month_start_date"
                                       value="{{ $monthStartDate }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="month_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    End Date
                                </label>
                                <input type="date" id="month_end_date" name="month_end_date"
                                       value="{{ $monthEndDate }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Apply Filter
                        </button>
                    </form>

                    @if($chartMonth)
                        <div class="p-4 mb-4 bg-gray-50 dark:bg-gray-200 sm:rounded-lg shadow">
                            {!! $chartMonth->container() !!}
                        </div>
                    @endif
                    @if($chartChoreFrequency)
                        <div class="p-4 mb-4 bg-gray-50 dark:bg-gray-200 sm:rounded-lg shadow">
                            {!! $chartChoreFrequency->container() !!}
                        </div>
                    @endif
                    @if($chartChoreRate)
                        <div class="p-4 bg-gray-50 dark:bg-gray-200 sm:rounded-lg shadow">
                            {!! $chartChoreRate->container() !!}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Year Chart Section (No Date Filter) -->
            @if($chartYear)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Annual User Contribution Summary</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Overall user contribution percentages for the last 365 days, weighted by chore difficulty.</p>
                        <div class="p-4 bg-gray-50 dark:bg-gray-200 sm:rounded-lg shadow">
                            {!! $chartYear->container() !!}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Pill Missed Doses Chart Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Missed Medication Doses</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Track scheduled medication doses that were not administered, broken down by medication and pet.</p>
                    <form method="GET" action="{{ route('graphs.index') }}" class="mb-6">
                        <!-- Preserve other date ranges -->
                        <input type="hidden" name="week_start_date" value="{{ $weekStartDate }}">
                        <input type="hidden" name="week_end_date" value="{{ $weekEndDate }}">
                        <input type="hidden" name="month_start_date" value="{{ $monthStartDate }}">
                        <input type="hidden" name="month_end_date" value="{{ $monthEndDate }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="pill_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Start Date
                                </label>
                                <input type="date" id="pill_start_date" name="pill_start_date"
                                       value="{{ $pillStartDate }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="pill_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    End Date
                                </label>
                                <input type="date" id="pill_end_date" name="pill_end_date"
                                       value="{{ $pillEndDate }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Apply Filter
                        </button>
                    </form>

                    @if($chartPillMissedDoses)
                        <div class="p-4 bg-gray-50 dark:bg-gray-200 sm:rounded-lg shadow">
                            {!! $chartPillMissedDoses->container() !!}
                        </div>
                    @else
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 sm:rounded-lg text-center text-gray-600 dark:text-gray-300">
                            No missed doses in the selected date range.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
@if($chartWeek) <script src="{{ $chartWeek->cdn() }}"></script> @endif
{{--@if($chartWeekWeighted) <script src="{{ $chartWeekWeighted->cdn() }}"></script> @endif--}}
@if($chartMonth) <script src="{{ $chartMonth->cdn() }}"></script> @endif
@if($chartYear) <script src="{{ $chartYear->cdn() }}"></script> @endif
@if($chartChoreFrequency) <script src="{{ $chartChoreFrequency->cdn() }}"></script> @endif
@if($chartChoreRate) <script src="{{ $chartChoreRate->cdn() }}"></script> @endif
@if($chartPillMissedDoses) <script src="{{ $chartPillMissedDoses->cdn() }}"></script> @endif
@if($chartWeek) {{ $chartWeek->script() }} @endif
@if($chartWeekWeighted) {{ $chartWeekWeighted->script() }} @endif
@if($chartMonth) {{ $chartMonth->script() }} @endif
@if($chartYear) {{ $chartYear->script() }} @endif
@if($chartChoreFrequency) {{ $chartChoreFrequency->script() }} @endif
@if($chartChoreRate) {{ $chartChoreRate->script() }} @endif
@if($chartPillMissedDoses) {{ $chartPillMissedDoses->script() }} @endif
