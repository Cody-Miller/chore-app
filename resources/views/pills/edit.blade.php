@props(['pill' => null, 'pets' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Medication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3>{{ __('Edit') }} {{ $pill->name }}</h3>

                    <form method="POST" action="/pills/{{ $pill->slug }}" x-data="{ times: @json($pill->scheduled_times) }">
                        @csrf
                        @method('PATCH')

                        <x-form.select-input name="pet_id" :label-content="'Pet:'">
                            @if ($pets && count($pets) > 0)
                                @foreach($pets as $pet)
                                    <x-form.select-input-option value="{{ $pet->id }}" :selected="$pet->id == $pill->pet_id">
                                        {{ $pet->name }}
                                    </x-form.select-input-option>
                                @endforeach
                            @endif
                        </x-form.select-input>

                        <x-form.input name='name' type='text' :default="$pill->name">{{ __('Medication Name:') }}</x-form.input>

                        <x-form.input name='dosage' type='text' :default="$pill->dosage">{{ __('Dosage:') }}</x-form.input>

                        <x-form.textarea name='description' :default="$pill->description">{{ __('Description/Instructions:') }}</x-form.textarea>

                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">
                                Scheduled Times:
                            </label>
                            <template x-for="(time, index) in times" :key="index">
                                <div class="flex gap-2 mb-2">
                                    <input
                                        type="time"
                                        :name="'scheduled_times[' + index + ']'"
                                        x-model="times[index]"
                                        class="w-40 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500 rounded-md shadow-sm"
                                        required
                                    >
                                    <button
                                        type="button"
                                        @click="times.splice(index, 1)"
                                        x-show="times.length > 1"
                                        class="px-3 py-2 bg-red-600 text-white rounded-md text-sm"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </template>
                            <button
                                type="button"
                                @click="times.push('12:00')"
                                class="mt-2 px-4 py-2 bg-gray-600 text-white rounded-md text-sm"
                            >
                                Add Another Time
                            </button>
                        </div>

                        <div class="flex gap-2 mt-6">
                            <x-buttons.primary-button type="submit">{{ __('Update Medication') }}</x-buttons.primary-button>
                            <a href="/pills" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>

                    <div class="mt-8">
                        <x-modal.button name="deletepill">
                            {{ __('Delete Medication') }}
                        </x-modal.button>
                    </div>
                </div>
            </div>
        </div>

        <x-modal.popup name="deletepill">
            <form method="POST" action="/pills/{{ $pill->slug }}" class="p-6">
                @csrf
                @method('DELETE')
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Are you sure you want to delete this medication?
                </h2>

                <div class="mt-6 flex justify-end">
                    <button type="button"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                            x-on:click="$dispatch('close')">
                        Cancel
                    </button>

                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ml-3">
                        Delete Medication
                    </button>
                </div>
            </form>
        </x-modal.popup>
    </div>
</x-app-layout>
