@props([
    'model',
    'options',
    'display',
    'handle',
    'id',
    'instructions',
])

<fieldset
    {{ $attributes }}
    ::aria-invalid="form.invalid('{{ $handle }}')"
    @unless (empty($instructions))
        ::aria-describedby="form.invalid('{{ $handle }}') ? '{{ $id }}-error' : '{{ $id }}-instructions'"
    @else
        ::aria-describedby="form.invalid('{{ $handle }}') ? '{{ $id }}-error' : undefined"
    @endunless
>
    <legend class="block font-medium text-foreground select-none text-sm">{{ $display }}</legend>

    @unless (empty($instructions))
        @include('components.ui.input-instructions', ['field' => $field])
    @endunless

    <div class="mt-3 space-y-4">
        @foreach ($options as $option => $label)
            <div class="flex items-center gap-3">
                <input
                    x-model="{{ $model }}"
                    id="{{ $id }}-{{ Str::slug($option) }}-option"
                    type="checkbox"
                    name="{{ $handle }}[]"
                    value="{!! $option !!}"
                    x-bind:aria-invalid="form.invalid('{{ $handle }}')"
                    x-on:change="form.validate('{{ $handle }}')"
                    {{ $attributes }}
                >
                <label for="{{ $id }}-{{ Str::slug($option) }}-option" class="text-foreground font-normal">
                    {!! $label !!}
                </label>
            </div>
        @endforeach
    </div>
</fieldset>
