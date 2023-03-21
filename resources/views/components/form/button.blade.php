<x-form.field>
    <button
        type="submit"
        {{ $attributes->merge(['class' => 'bg-blue-500 text-white uppercase font-semibold text-xs py-2 px-10 rounded-xl hover:bg-blue-600'])}}
    >
        {{ $slot }}
    </button>
</x-form.field>
