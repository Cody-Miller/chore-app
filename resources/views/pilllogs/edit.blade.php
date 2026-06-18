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

                        <div class="mt-5 flex flex-col sm:flex-row sm:justify-between gap-3">
                            <x-modal.button name="deletepilllog" class="w-full sm:w-auto">
                                {{ __('Delete') }}
                            </x-modal.button>

                            <x-buttons.primary-button type="submit" class="w-full sm:w-auto">
                                {{ __('Update') }}
                            </x-buttons.primary-button>
                        </div>
                    </form>

                    <x-modal.popup name="deletepilllog">
                        <form method="POST" action="/pilllogs/{{ $pilllog->id }}" class="p-6">
                            @csrf
                            @method('DELETE')
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Are you sure you want to delete this pill administration record?
                            </h2>

                            <x-modal.confirm-actions confirmText="Delete Record" />
                        </form>
                    </x-modal.popup>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
