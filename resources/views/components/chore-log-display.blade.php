@props(['choreLog' => null])
<div class="py-4">
    <span class="block">
        <a class="text-lg font-bold dark:text-indigo-500 block mb-1" href="/chores/{{ $choreLog?->chore?->slug }}">
            {{ $choreLog?->chore?->name }}
        </a>
        <span class="px-1"><span class="font-semibold">Completed By: </span>{{ $choreLog?->user?->name }}</span>
        <span class="px-1"><span class="font-semibold">Last Completed: </span>{{ $choreLog->created_at }}</span>
        <span class="px-1"><span class="font-semibold">Weight: </span>{{ $choreLog?->chore?->weight }}</span>
    </span>
</div>
