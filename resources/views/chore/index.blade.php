@props(['chores' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Chores') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($chores)
                        <ul>
                            @foreach($chores as $chore)
                                <a href="/chores/{{ $chore->slug }}">
                                    <li class="mb-4">
                                        <p>
                                            <strong>{{ $chore->name }}</strong> - {{  $chore->description  }}
                                        </p>
                                        <span>Weight: {{  $chore->weight  }}</span>
                                        <span class="ml-3">Occurance: {{  $chore->occurrence_hours  }}</span>
                                    </li>
                                </a>
                            @endforeach
                        </ul>
                    @else
                        <h3>No chores created yet, get in there and make some!</h3>
                    @endif
                    <form method="delete" action="/chores/{{ $chore->slug }}" enctype="multipart/form-data">
                        @csrf
                        <x-primary-link-button
                            class="mt-4"
                            href="/chores/create"
                        >
                            Create Chore
                        </x-primary-link-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
