@props([
    'trigger',
    'content',
    'name' => Str::random(8),
])

<div x-data="{ open: false }" {{ $attributes->class(['relative']) }}>
    <dt>
        <button
            type="button"
            aria-controls="collapsible-{{ $name }}"
            x-bind:aria-expanded="open"
            x-on:click="open = !open"
            {{ $trigger->attributes }}
        >
            {{ $trigger }}
        </button>
    </dt>
    <dd
        x-cloak
        x-show="open"
        x-collapse
        id="collapsible-{{ $name }}"
        {{ $content->attributes->class(['relative']) }}
    >
        {{ $content }}
    </dd>
</div>
