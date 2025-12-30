@props(['pet' => null, 'pills' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pet->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-start gap-6 mb-6">
                        @if($pet->photo_path)
                            <img src="{{ asset('storage/' . $pet->photo_path) }}" alt="{{ $pet->name }}" class="w-32 h-32 rounded-lg object-cover">
                        @else
                            <div class="w-32 h-32 rounded-lg bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <span class="text-4xl">🐾</span>
                            </div>
                        @endif
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold mb-2">{{ $pet->name }}</h3>
                            @if($pet->species)
                                <p><span class="font-semibold">Species:</span> {{ $pet->species }}</p>
                            @endif
                            @if($pet->breed)
                                <p><span class="font-semibold">Breed:</span> {{ $pet->breed }}</p>
                            @endif
                            @if($pet->birth_date)
                                <p><span class="font-semibold">Age:</span> {{ $pet->getAge() }} years old (born {{ $pet->birth_date->format('m/d/Y') }})</p>
                            @endif
                            @if($pet->notes)
                                <p class="mt-2"><span class="font-semibold">Notes:</span> {{ $pet->notes }}</p>
                            @endif

                            <div class="flex gap-2 mt-4">
                                <x-buttons.primary-link-button href="/pets/{{ $pet->slug }}/edit">
                                    Edit Pet
                                </x-buttons.primary-link-button>
                                <x-buttons.primary-link-button href="/pills/create?pet_id={{ $pet->id }}">
                                    Add Pill
                                </x-buttons.primary-link-button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-6 border-gray-300 dark:border-gray-700">

                    <h4 class="text-xl font-bold mb-4">Medications</h4>
                    @if($pills && $pills->count() > 0)
                        <ul class="space-y-4">
                            @foreach($pills as $pill)
                                <li class="border border-gray-300 dark:border-gray-700 rounded-lg p-4">
                                    <a href="/pills/{{ $pill->slug }}" class="text-lg font-bold dark:text-indigo-500">{{ $pill->name }}</a>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $pill->description }}</p>
                                    <p class="text-sm"><span class="font-semibold">Dosage:</span> {{ $pill->dosage }}</p>
                                    <p class="text-sm">
                                        <span class="font-semibold">Scheduled:</span>
                                        @foreach($pill->scheduled_times as $time)
                                            {{ date('g:i A', strtotime($time)) }}@if (!$loop->last), @endif
                                        @endforeach
                                    </p>
                                    @if($pill->getLastLog())
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Last given: {{ $pill->getLastAdministeredDate()->format('m/d/y h:i a') }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-600 dark:text-gray-400">No medications added yet.</p>
                        <x-buttons.primary-link-button href="/pills/create?pet_id={{ $pet->id }}" class="mt-2">
                            Add First Medication
                        </x-buttons.primary-link-button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
