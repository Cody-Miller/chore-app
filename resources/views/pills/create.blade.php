@props(['pets' => null, 'allTags' => collect()])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Medication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3>{{ __('Add a new medication') }}</h3>

                    <form method="POST" action="/pills" x-data="{ times: ['08:00'] }">
                        @csrf
                        @method('POST')

                        <x-form.select-input name="pet_id" :label-content="'Pet:'">
                            <x-form.select-input-option disabled :selected="!request('pet_id')" value="">
                                {{ __('Please Select') }}
                            </x-form.select-input-option>
                            @if ($pets && count($pets) > 0)
                                @foreach($pets as $pet)
                                    <x-form.select-input-option value="{{ $pet->id }}" :selected="request('pet_id') == $pet->id">{{ $pet->name }}</x-form.select-input-option>
                                @endforeach
                            @else
                                <x-form.select-input-option>No pets added yet</x-form.select-input-option>
                            @endif
                        </x-form.select-input>

                        <x-form.input name='name' type='text'>{{ __('Medication Name:') }}</x-form.input>

                        <x-form.input name='dosage' type='text'>{{ __('Dosage (e.g., 10mg, 1 tablet):') }}</x-form.input>

                        <x-form.textarea name='description' placeholder="{{ __('Description/Instructions (optional)') }}"></x-form.textarea>

                        <x-form.tag-input :all-tags="$allTags">{{ __('Tags:') }}</x-form.tag-input>

                        <div class="mt-6 mb-4">
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

                        <div class="mb-4">
                            <input type="hidden" name="is_active" value="0">
                            <label for="is_active" class="inline-flex items-center cursor-pointer gap-3">
                                <input id="is_active" type="checkbox" name="is_active" value="1" class="sr-only peer" @checked(old('is_active', true))>
                                <span class="relative w-14 h-8 bg-gray-300 dark:bg-gray-700 rounded-full peer-focus:ring-2 peer-focus:ring-indigo-500 peer-checked:bg-indigo-500 after:content-[''] after:absolute after:top-1 after:start-1 after:bg-white after:rounded-full after:h-6 after:w-6 after:shadow after:transition-all peer-checked:after:translate-x-6"></span>
                                <span class="font-medium text-gray-700 dark:text-gray-300 peer-checked:hidden">{{ __('Inactive') }}</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300 hidden peer-checked:inline">{{ __('Active') }}</span>
                            </label>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Inactive medications are hidden from the dashboard and the missed doses chart.') }}</p>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:justify-between gap-3 mt-6">
                            <x-buttons.cancel-link-button href="/pills" class="w-full sm:w-auto">
                                {{ __('Cancel') }}
                            </x-buttons.cancel-link-button>
                            <x-buttons.primary-button type="submit" class="w-full sm:w-auto">{{ __('Add Medication') }}</x-buttons.primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
