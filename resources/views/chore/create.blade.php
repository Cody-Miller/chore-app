<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Chore') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3>{{ __('Add a new chore!') }}</h3>

                    <form method="POST" action="/chores" enctype="multipart/form-data">
                        @csrf
                        @method('POST')

                        <x-form.input name='name' type='text'>{{ __('Name:') }}</x-form.input>
                        <x-form.input name='desc' type='text'>{{ __('Description:') }}</x-form.input>
                        <x-form.select-input name="assigned_user_id" :label-content="'Assigned To:'">
                            <x-form.select-input-option value="" :selected="!old('assigned_user_id')">
                                {{ __('Unassigned') }}
                            </x-form.select-input-option>
                            @foreach($users as $user)
                                <x-form.select-input-option value="{{ $user->id }}" :selected="old('assigned_user_id') == $user->id">
                                    {{ $user->name }}
                                </x-form.select-input-option>
                            @endforeach
                        </x-form.select-input>
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
                                    default="0"
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
                                    default="4"
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
                                    default="1"
                                >
                                    {{ __('Weight:') }}
                                </x-form.input>
                                <output x-text="weightVal">0</output>

                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-3 mt-5">
                            <x-buttons.cancel-link-button href="/chores" class="w-full sm:w-auto">
                                {{ __('Cancel') }}
                            </x-buttons.cancel-link-button>
                            <x-buttons.primary-button type="submit" class="w-full sm:w-auto">
                                {{ __('Create') }}
                            </x-buttons.primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
