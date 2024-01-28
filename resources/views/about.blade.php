<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('About BeChore') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="p-2">Me and my wife don't work the same hours and as such we had a hard time keeping track of our household chores. I work as a Web Developer and when you work as a Web Developer all 'nails' look like they can be hit with some code as a fix.
                    <p class="p-2">We ended up talking about how we would want this kind of site to function and I got to implement it. We had wanted to track re-occurring chores namly dishes, laundry, etc... But we also wanted to make it a little competitive. So we added the weighted part of the chores and the completion tracking overview. The first version was just a raw HTML/JS  site with your standard LAMP backend. But we ended up liking it so I created this Laravel build.</p>
                    <p class="p-2">You're welcome to give this a download and implement it yourself if youself if you think that it would help you manage your chores :)</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
