@props(['chorelog' => null, 'users' => null, 'chores' => null])
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Completion: ') . $chorelog->id}}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 dark:text-gray-100">

                    <form method="POST" action="/chorelog/{{ $chorelog->id }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        @if($chorelog->is_split)
                            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                        Split Completion
                                    </span>
                                </div>
                                @php
                                    $partner = $chorelog->splitPartner();
                                @endphp
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-2">
                                    This completion was split 50/50 between <strong>{{ $chorelog->user?->name }}</strong>
                                    @if($partner)
                                        and <strong>{{ $partner->user?->name }}</strong>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Each user receives {{ $chorelog->weight_percentage }}% of the weight ({{ round($chorelog->chore?->weight * ($chorelog->weight_percentage ?? 100) / 100, 1) }} points)
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                    Note: Editing this log only affects this user's record. The split partner's record remains unchanged.
                                </p>
                            </div>
                        @endif

                        <x-form.select-input name="user_id" :label-content="'User:'">
                            @if ($users && count($users) > 0)
                                @foreach($users as $user)
                                    <x-form.select-input-option
                                        :selected="$user->id == $chorelog->user_id"
                                        value="{{ $user->id }}"
                                    >{{ $user->name }}</x-form.select-input-option>
                                @endforeach
                            @else
                                <x-form.select-input-option>NA</x-form.select-input-option>
                            @endif
                        </x-form.select-input>

                        <x-form.select-input name="chore_id" :label-content="'Chore:'">
                            @if ($chores && count($chores) > 0)
                                @foreach($chores as $chore)
                                    <x-form.select-input-option
                                        :selected="$chore->id == $chorelog->chore_id"
                                        value="{{ $chore->id }}"
                                    >{{ $chore->name }}</x-form.select-input-option>
                                @endforeach
                            @else
                                <x-form.select-input-option>NA</x-form.select-input-option>
                            @endif
                        </x-form.select-input>

                        <x-form.input
                            name='completed_time'
                            :default="$chorelog->completed_at?->format('Y-m-d\TH:i')"
                            :type="'datetime-local'"
                        >
                            {{ __('Completed Time:') }}
                        </x-form.input>

                        <div class="mt-5 flex flex-col sm:flex-row sm:justify-between gap-3">
                            <x-modal.button name="deletechorelog" class="w-full sm:w-auto">
                                {{ __('Delete') }}
                            </x-modal.button>

                            <x-buttons.primary-button type="submit" class="w-full sm:w-auto">
                                {{ __('Update') }}
                            </x-buttons.primary-button>
                        </div>
                    </form>

                    <x-modal.popup name="deletechorelog">
                        <form method="POST" action="/chorelog/{{ $chorelog->id }}" class="p-6">
                            @csrf
                            @method('DELETE')
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Are you sure you want to this completion?
                            </h2>

                            <x-modal.confirm-actions confirmText="Delete Completion" />
                        </form>
                    </x-modal.popup>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
