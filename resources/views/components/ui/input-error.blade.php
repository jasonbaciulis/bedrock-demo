{{--
    Display error when there is a validation error with the name field.
--}}

@props ([
    'handle',
    'id',
])

<template x-if="form.invalid('{{ $handle }}')">
    <p
        id="{{ $id }}-error"
        {{ $attributes->class(['text-destructive text-sm']) }}
        x-text="form.errors.{{ $handle }}"
    ></p>
</template>
