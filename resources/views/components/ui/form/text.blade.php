@props([
    'model',
    'prepend' => null,
    'append' => null,
    'character_limit' => null,
    'autocomplete' => null,
    'visibility' => null,
    'placeholder' => null,
    'id',
    'handle',
    'input_type' => 'text',
    'instructions' => null,
])

<div class="relative w-full">
    @unless (empty($prepend))
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5">
            <span class="text-sm text-muted-foreground">{!! $prepend !!}</span>
        </div>
    @endunless
    <input
        x-model="{{ $model }}"
        {{
            $attributes
                ->class(['pl-10' => $prepend, 'pr-10' => $append])
                ->merge([
                    'maxlength' => $character_limit,
                    'autocomplete' => $autocomplete,
                    'readonly' => $visibility === 'read_only' ? 'readonly' : null,
                    'placeholder' => $placeholder,
                ])
        }}
        id="{{ $id }}"
        name="{{ $handle }}"
        type="{{ $input_type }}"
        x-bind:aria-invalid="form.invalid('{{ $handle }}')"
        @unless (empty($instructions))
            x-bind:aria-describedby="form.invalid('{{ $handle }}') ? '{{ $id }}-error' : '{{ $id }}-instructions'"
        @else
            x-bind:aria-describedby="form.invalid('{{ $handle }}') ? '{{ $id }}-error' : false"
        @endunless
        x-on:change="form.validate('{{ $handle }}')"
    />
    @unless (empty($append))
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pr-5">
            <span class="text-sm text">{!! $append !!}</span>
        </div>
    @endunless
</div>
