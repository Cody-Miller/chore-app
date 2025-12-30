<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Pet') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3>{{ __('Add a new pet!') }}</h3>

                    <form method="POST" action="/pets" enctype="multipart/form-data">
                        @csrf
                        @method('POST')

                        <x-form.input name='name' type='text'>{{ __('Name:') }}</x-form.input>

                        <x-form.input name='species' type='text'>{{ __('Species (e.g., Dog, Cat):') }}</x-form.input>

                        <x-form.input name='breed' type='text'>{{ __('Breed (optional):') }}</x-form.input>

                        <x-form.input name='birth_date' type='date'>{{ __('Birth Date (optional):') }}</x-form.input>

                        <x-form.textarea name='notes'>{{ __('Notes (optional):') }}</x-form.textarea>

                        <x-form.input name='photo' type='file' accept='image/*'>{{ __('Photo (optional):') }}</x-form.input>

                        <div class="flex gap-2 mt-6">
                            <x-buttons.primary-button type="submit">{{ __('Add Pet') }}</x-buttons.primary-button>
                            <a href="/pets" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
