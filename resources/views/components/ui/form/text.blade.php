@props([
    'field',
    'model',
])

<div class="relative w-full">
    @unless (empty($field->prepend))
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5">
            <span class="text-sm text-muted-foreground">{!! $field->prepend !!}</span>
        </div>
    @endunless
    <input
        x-model="{{ $model }}"
        {{
            $attributes
                ->class(['pl-10' => $field->prepend ?? false, 'pr-10' => $field->append ?? false])
                ->merge([
                    'maxlength' => $field->character_limit ?? null,
                    'autocomplete' => $field->autocomplete ?? null,
                    'readonly' => $field->visibility === 'read_only' ? 'readonly' : null,
                    'placeholder' => $field->placeholder ?? null,

                ])
        }}
        id="{{ $field->id }}"
        name="{{ $field->handle }}"
        type="{{ $field->input_type ?? 'text' }}"
        ::aria-invalid="form.invalid('{{ $field->handle }}')"
        @unless (empty($field->instructions))
            ::aria-describedby="form.invalid('{{ $field->handle }}') ? '{{ $field->id }}-error' : '{{ $field->id }}-instructions'"
        @else
            ::aria-describedby="form.invalid('{{ $field->handle }}') ? '{{ $field->id }}-error' : undefined"
        @endunless
        x-on:change="form.validate('{{ $field->handle }}')"
    />
    @unless (empty($field->append))
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pr-5">
            <span class="text-sm text">{!! $field->append !!}</span>
        </div>
    @endunless
</div>
