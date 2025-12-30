@props(['pet' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Pet') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3>{{ __('Edit') }} {{ $pet->name }}</h3>

                    <form method="POST" action="/pets/{{ $pet->slug }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <x-form.input name='name' type='text' :default="$pet->name">{{ __('Name:') }}</x-form.input>

                        <x-form.input name='species' type='text' :default="$pet->species">{{ __('Species:') }}</x-form.input>

                        <x-form.input name='breed' type='text' :default="$pet->breed">{{ __('Breed:') }}</x-form.input>

                        <x-form.input name='birth_date' type='date' :default="$pet->birth_date?->format('Y-m-d')">{{ __('Birth Date:') }}</x-form.input>

                        <x-form.textarea name='notes' :default="$pet->notes" placeholder="{{ __('Notes') }}"></x-form.textarea>

                        @if($pet->photo_path)
                            <div class="mb-4">
                                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Current Photo:</label>
                                <img src="{{ asset('storage/' . $pet->photo_path) }}" alt="{{ $pet->name }}" class="w-32 h-32 rounded-lg object-cover">
                            </div>
                        @endif

                        <x-form.input name='photo' type='file' accept='image/*'>{{ __('Change Photo (optional):') }}</x-form.input>

                        <div class="flex gap-2 mt-6">
                            <x-buttons.primary-button type="submit">{{ __('Update Pet') }}</x-buttons.primary-button>
                            <a href="/pets" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>

                    <div class="mt-8">
                        <x-modal.button name="deletepet">
                            {{ __('Delete Pet') }}
                        </x-modal.button>
                    </div>
                </div>
            </div>
        </div>

        <x-modal.popup name="deletepet">
            <form method="POST" action="/pets/{{ $pet->slug }}" class="p-6">
                @csrf
                @method('DELETE')
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Are you sure you want to delete this pet?
                </h2>

                <div class="mt-6 flex justify-end">
                    <button type="button"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                            x-on:click="$dispatch('close')">
                        Cancel
                    </button>

                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ml-3">
                        Delete Pet
                    </button>
                </div>
            </form>
        </x-modal.popup>
    </div>
</x-app-layout>
