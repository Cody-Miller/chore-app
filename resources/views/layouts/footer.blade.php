<footer class="p-4 bg-white rounded-lg shadow md:px-6 md:py-8 dark:bg-gray-800">
    <div class="lg:px-8 max-w-7xl mx-auto sm:px-6 sm:flex sm:items-center sm:justify-between">
        <!-- Logo -->
        <div class="shrink-0 flex items-center">
            <a href="{{ route('dashboard') }}">
                <x-application-logo class="block p-1 h-14 w-auto mx-auto fill-current text-gray-800 dark:text-gray-200" />
                <span class="self-center text-xl font-semibold whitespace-nowrap dark:text-white">BeChore</span>
            </a>
        </div>
        <span class="block text-sm text-gray-500 sm:text-center dark:text-gray-400">
            2024 <a href="https://flowbite.com" target="_blank" class="hover:underline">BeChore</a>. A better means to manage our chores, pets, and house: Version 0.8 Beta.
        </span>
        <ul class="flex flex-wrap items-center mb-6 sm:mb-0">
            <li>
                <a href="{{ route('about') }}" class="mr-4 text-sm text-gray-500 hover:underline md:mr-6 dark:text-gray-400">About</a>
            </li>
            <li>
                <a class="text-sm text-gray-500 hover:underline dark:text-gray-400" href="mailto:cody.miller102@gmail.com">Contact</a>
            </li>
        </ul>
    </div>
    <span class="block text-sm text-gray-500 sm:text-center dark:text-gray-400">
        Keep Calm and Chore On
    </span>
</footer>
