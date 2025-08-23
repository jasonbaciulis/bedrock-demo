@props([
    'field',
    'model',
])

<div
    class="flex items-center gap-3"
    x-data="{
        on: false,
        toggle() {
            this.on = !this.on;
            this.$refs.switch.focus();
            this.{{ $model }} = this.on;
            this.form.validate('{{ $field->handle }}');
        }
    }"
    {{ $attributes }}
>
    <button
        x-ref="switch"
        id="{{ $field->id }}"
        type="button"
        class="peer focus-visible:border-ring focus-visible:ring-ring/50 inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border border-transparent shadow-xs transition-all outline-none focus-visible:ring-3 disabled:cursor-not-allowed disabled:opacity-50"
        :class="{ 'bg-primary': on, 'bg-input dark:bg-input/80': !on }"
        role="switch"
        aria-labelledby="{{ $field->id }}-label"
        @unless (empty($field->instructions))
            ::aria-describedby="form.invalid('{{ $field->handle }}') ? '{{ $field->id }}-error' : '{{ $field->id }}-instructions'"
        @else
            ::aria-describedby="form.invalid('{{ $field->handle }}') ? '{{ $field->id }}-error' : undefined"
        @endunless
        :aria-checked="on.toString()"
        @click="toggle()"
    >
        <span
            aria-hidden="true"
            class="bg-background pointer-events-none block size-4 rounded-full ring-0 transition-transform"
            :class="{ 'translate-x-[calc(100%-2px)] dark:bg-primary-foreground': on, 'translate-x-0 dark:bg-foreground': !on }"
        ></span>
        <span class="sr-only">Toggle {{ Str::title(str_replace('_', ' ', $field->handle)) }}</span>
    </button>

    <label
        class="text-sm/none text-foreground prose"
        id="{{ $field->id }}-label"
        for="{{ $field->id }}"
    >
        {{ $field->inline_label }}
    </label>

    <input name="{{ $field->handle }}" aria-hidden="true" tabindex="-1" type="checkbox" value="on" :checked="on" class="sr-only">
</div>
