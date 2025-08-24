@props([
    'alignment' => 'left',
    'width' => 'w-56',
    'toggle',
    'content',
])

<div
    x-data="{
        open: false,
        toggle() {
            if (this.open) {
                return this.close();
            }
            this.$refs.button.focus();
            this.open = true;
        },
        close(focusAfter) {
            if (! this.open) return;
            this.open = false;
            focusAfter && focusAfter.focus();
        }
    }"
    x-on:keydown.escape.prevent.stop="close($refs.button)"
    x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
    x-id="['dropdown-menu']"
    {{ $attributes->class(['relative']) }}
>
    <button
        x-ref="button"
        x-on:click="toggle()"
        x-bind:aria-expanded="open"
        x-bind:aria-controls="$id('dropdown-menu')"
        x-id="['dropdown-button']"
        {{ $toggle->attributes->class(['btn']) }}
    >
        {{ $toggle }}
    </button>
    <div
        x-cloak
        x-ref="panel"
        x-show="open"
        x-trap="open"
        x-transition:enter="transition ease duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-on:click.outside="close($refs.button)"
        x-bind:id="$id('dropdown-menu')"
        x-bind:aria-labelledby="$id('dropdown-button')"
        tabindex="-1"
        {{ $content->attributes->class([
            'absolute z-10 mt-1 rounded-lg bg-popover text-popover-foreground overflow-x-hidden overflow-y-auto shadow-md border p-1 max-h-60',
            'left-0 origin-top-left' => $alignment === 'left',
            'right-0 origin-top-right' => $alignment === 'right',
            $width
        ]) }}
    >
        {{ $content }}
    </div>
</div>
