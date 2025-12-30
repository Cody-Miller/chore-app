@props(['pilllog' => null, 'users' => null, 'pills' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Pill Administration: ') . $pilllog->id}}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">

                    <form method="POST" action="/pilllogs/{{ $pilllog->id }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <x-form.select-input name="user_id" :label-content="'Given By:'">
                            @if ($users && count($users) > 0)
                                @foreach($users as $user)
                                    <x-form.select-input-option
                                        :selected="$user->id == $pilllog->user_id"
                                        value="{{ $user->id }}"
                                    >{{ $user->name }}</x-form.select-input-option>
                                @endforeach
                            @else
                                <x-form.select-input-option>NA</x-form.select-input-option>
                            @endif
                        </x-form.select-input>

                        <x-form.select-input name="pill_id" :label-content="'Medication:'">
                            @if ($pills && count($pills) > 0)
                                @foreach($pills as $pill)
                                    <x-form.select-input-option
                                        :selected="$pill->id == $pilllog->pill_id"
                                        value="{{ $pill->id }}"
                                    >{{ $pill->name }} ({{ $pill->pet->name }})</x-form.select-input-option>
                                @endforeach
                            @else
                                <x-form.select-input-option>NA</x-form.select-input-option>
                            @endif
                        </x-form.select-input>

                        <x-form.input
                            name='administered_at'
                            :default="$pilllog->administered_at?->format('Y-m-d\TH:i')"
                            :type="'datetime-local'"
                        >
                            {{ __('Administered Time:') }}
                        </x-form.input>

                        <x-form.input
                            name='scheduled_time'
                            :default="$pilllog->scheduled_time"
                            :type="'time'"
                        >
                            {{ __('Scheduled Dose Time:') }}
                        </x-form.input>

                        <x-form.textarea
                            name='notes'
                            :default="$pilllog->notes"
                            placeholder="{{ __('Notes') }}"
                        ></x-form.textarea>

                        <div>
                            <x-buttons.primary-button type="submit" class="mt-5">
                                {{ __('Update') }}
                            </x-buttons.primary-button>

                            <x-modal.button name="deletepilllog">
                                {{ __('Delete') }}
                            </x-modal.button>
                        </div>
                    </form>

                    <x-modal.popup name="deletepilllog">
                        <form method="POST" action="/pilllogs/{{ $pilllog->id }}" class="p-6">
                            @csrf
                            @method('DELETE')
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Are you sure you want to delete this pill administration record?
                            </h2>

                            <div class="mt-6 flex justify-end">
                                <button type="button"
                                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                                        x-on:click="$dispatch('close')">
                                    Cancel
                                </button>

                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ml-3">
                                    Delete Record
                                </button>
                            </div>
                        </form>
                    </x-modal.popup>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
