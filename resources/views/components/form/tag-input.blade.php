@props(['name' => 'tags', 'selected' => [], 'allTags' => collect()])
<x-form.field>
    <x-form.input-label for="{{ $name }}-input">
        {{ $slot }}
    </x-form.input-label>
    <div
        x-data="{
            tags: @js(array_values(old($name, $selected))),
            names: @js($allTags->pluck('name')),
            colors: @js($allTags->mapWithKeys(fn ($tag) => [mb_strtolower($tag->name) => $tag->color])),
            draft: '',
            colorFor(tag) { return this.colors[tag.toLowerCase()] ?? null },
            add() {
                const tag = this.draft.replace(/,/g, '').trim().slice(0, 50);
                this.draft = '';
                if (!tag || this.tags.some(t => t.toLowerCase() === tag.toLowerCase())) return;
                // Reuse an existing tag's casing so it matches the saved one
                this.tags.push(this.names.find(t => t.toLowerCase() === tag.toLowerCase()) ?? tag);
            },
            remove(index) { this.tags.splice(index, 1) },
        }"
        class="flex flex-wrap items-center gap-2 w-full rounded border border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm px-2 py-1.5 focus-within:ring-1 focus-within:ring-indigo-500"
    >
        <template x-for="(tag, index) in tags" :key="tag">
            <span
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                :class="colorFor(tag) ? 'text-gray-800' : 'border border-dashed border-gray-400 text-gray-600 dark:text-gray-300'"
                :style="colorFor(tag) ? { backgroundColor: colorFor(tag) } : {}"
                :title="colorFor(tag) ? '' : 'New tag, a color is picked when saved'"
            >
                <span x-text="tag"></span>
                <button type="button" @click="remove(index)" class="leading-none hover:text-red-600" :aria-label="'Remove tag ' + tag">&times;</button>
                <input type="hidden" name="{{ $name }}[]" :value="tag">
            </span>
        </template>
        <input
            id="{{ $name }}-input"
            type="text"
            list="{{ $name }}-options"
            x-model="draft"
            @keydown.enter.prevent="add()"
            @keydown.comma.prevent="add()"
            @keydown.backspace="if (!draft && tags.length) tags.pop()"
            @change="if (names.includes(draft)) add()"
            @blur="add()"
            maxlength="50"
            placeholder="Add a tag and press Enter"
            class="flex-1 min-w-[10rem] border-0 p-1 bg-transparent dark:text-gray-300 focus:ring-0 text-sm"
        >
        <datalist id="{{ $name }}-options">
            @foreach($allTags as $tag)
                <option value="{{ $tag->name }}"></option>
            @endforeach
        </datalist>
    </div>
    <x-form.error name="{{ $name }}" />
    @foreach($errors->get($name . '.*') as $messages)
        @foreach($messages as $message)
            <span class="block text-red-500 text-xs mt-1">{{ $message }}</span>
        @endforeach
    @endforeach
</x-form.field>
