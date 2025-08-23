@props([
    'model',
    'options',
    'placeholder' => 'Select a value…',
    'handle',
    'id',
    'instructions',
    'display',
])

@once
    @push('scripts')
        @vite('resources/js/components/combobox.js')
    @endpush
@endonce

<div
    x-data="combobox({
        id: '{{ $id }}',
        items: {{ Js::from($options) }},
        placeholder: '{{ $placeholder }}'
    })"
    x-modelable="value"
    x-model="{{ $model }}"
    x-on:keydown.escape.stop.prevent="closeListbox()"
    x-on:change="form.validate('{{ $handle }}')"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    <input type="hidden" name="{{ $handle }}" x-model="value">

    <button
        x-cloak
        x-bind:id="id"
        type="button"
        aria-haspopup="listbox"
        x-bind:aria-expanded="listboxOpen"
        class="btn btn--outline w-full justify-between font-normal"
        x-on:click="toggleListbox()"
    >
        <span class="block truncate" x-text="buttonLabel" x-bind:class="{ 'text-muted-foreground': !value }"></span>
        <x-lucide-chevrons-up-down class="opacity-50" />
    </button>

    <div
        x-ref="popover"
        x-show="listboxOpen"
        x-cloak
        x-trap="listboxOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-on:click.away="closeListbox()"
        class="absolute z-50 mt-1 w-full bg-popover text-popover-foreground shadow-md rounded-md border origin-top p-0 outline-none"
    >
        <div class="flex h-full w-full flex-col overflow-hidden rounded-md">
            <div class="flex h-9 items-center gap-2 border-b px-3">
                <x-lucide-search class="size-4 shrink-0 opacity-50" />
                <input
                    :id="`${id}-search`"
                    x-ref="comboboxInput"
                    x-model.debounce.200ms="comboboxSearch"
                    placeholder="Search…"
                    class="placeholder:text-muted-foreground flex w-full rounded-md bg-transparent py-3 px-0 text-sm outline-hidden disabled:cursor-not-allowed disabled:opacity-50 h-9 border-none ring-0"
                    role="combobox"
                    x-bind:aria-controls="listboxId"
                    x-bind:aria-activedescendant="activeDescendant"
                    x-bind:aria-expanded="listboxOpen"
                    autocomplete="off"
                    autocorrect="off"
                    spellcheck="false"
                    aria-autocomplete="list"
                    x-on:keydown.enter.stop.prevent="selectOption()"
                    x-on:keydown.arrow-up.prevent="navigate('previous')"
                    x-on:keydown.arrow-down.prevent="navigate('next')"
                />
            </div>

            <ul
                x-ref="listbox"
                x-bind:id="listboxId"
                class="max-h-[300px] overflow-y-auto overflow-x-hidden p-1"
                tabindex="-1"
                role="listbox"
            >
                <template x-for="(item, index) in itemsShown" :key="`item-${index}`">
                    <li
                        x-bind:id="optionId(item.key)"
                        role="option"
                        x-bind:aria-selected="itemIsSelected(item)"
                        class="relative flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-hidden select-none"
                        x-bind:class="{ 'bg-accent text-accent-foreground': itemIsActive(item) }"
                        x-on:click="selectOption(item)"
                        x-on:mousemove="setActiveItem(item)"
                    >
                        <span x-text="item.value"></span>
                        <x-lucide-check class="ml-auto size-4 pointer-events-none shrink-0 text-muted-foreground" x-show="itemIsSelected(item)" />
                    </li>
                </template>

                {{-- Loads more items when the user scrolls to the bottom of the list. --}}
                <li x-show="itemsFiltered.length > itemsLoaded" x-intersect="loadMoreItems()" class="px-2 py-1.5 text-xs text-muted-foreground">
                    Loading more…
                </li>

                <li x-show="!itemsFiltered.length && comboboxSearch.length > 0" class="py-6 text-center text-sm">
                    No results match your search.
                </li>
            </ul>
        </div>
    </div>
</div>
