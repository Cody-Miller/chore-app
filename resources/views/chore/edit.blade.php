@props(['due-now-chores' => null, 'choreMonth' => 0, 'choreDay' => 4])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Chore: ') . $chore->name}}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">

                    <form method="POST" action="/chores/{{ $chore->slug }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <x-form.input :default="$chore->name" name='name'>{{ __('Name:') }}</x-form.input>
                        <x-form.input :default="$chore->description" name='desc'>{{ __('Description:') }}</x-form.input>
                        <div
                            class="flex flex-wrap justify-between"
                            x-data="{
                                occurMonthVal: $refs.occurMonthVal.value,
                                occurDayVal: $refs.occurDayVal.value,
                                weightVal: $refs.weightVal.value,
                            }"
                        >
                            <div class="flex-col w-23/48 min-w-300">

                                <x-form.input
                                    name='occurMonth'
                                    type='range'
                                    min="0"
                                    max="12"
                                    x-ref="occurMonthVal"
                                    x-model="occurMonthVal"
                                    default="{{ $choreMonth }}"
                                >
                                    {{ __('Occurrence (Month):') }}
                                </x-form.input>
                                <output x-text="occurMonthVal">0</output>

                                <x-form.input
                                    name='occurDay'
                                    type='range'
                                    min="0" max="32"
                                    x-ref="occurDayVal"
                                    x-model="occurDayVal"
                                    default="{{ $choreDay }}"
                                >
                                    {{ __('Occurrence (Day):') }}
                                </x-form.input>
                                <output x-text="occurDayVal">0</output>

                            </div>
                            <div class="flex-col w-23/48 min-w-300">

                                <x-form.input
                                    name='weight'
                                    type='range'
                                    min="1"
                                    max="5"
                                    x-ref="weightVal"
                                    x-model="weightVal"
                                    default="{{ $chore->weight }}"
                                >
                                    {{ __('Weight:') }}
                                </x-form.input>
                                <output x-text="weightVal">0</output>

                            </div>
                        </div>
                        <x-primary-button class="mt-5">
                            {{ __('Update') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
