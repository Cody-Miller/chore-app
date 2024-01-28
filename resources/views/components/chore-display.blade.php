@props(['chore'])
<a class="text-lg font-bold dark:text-indigo-600 block" href="/chores/{{ $chore->slug }}">
    {{ $chore->name }}
</a>
<span class="block px-1">{{  $chore->description  }}</span>
<span class="block">
    <span class="px-1"><span class="font-semibold">Weight</span>: {{  $chore->weight  }}</span>
    <span class="px-1"><span class="font-semibold">Occurance</span>: {{  $chore->occurrence_hours  }}</span>
    <span class="px-1"><span class="font-semibold">Last Completed</span>: {{  __('Cody')  }}</span>
</span>