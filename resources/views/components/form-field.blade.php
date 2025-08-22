@props([
    'field',
])

@php
$fields_without_labels = ['toggle', 'radio', 'checkboxes', 'stepper'];
@endphp

<template x-if="{!! $field->show_field !!}">
    <div {{ $attributes->class(['grid gap-3 content-start', 'hidden' => ($field->input_type ?? null) === 'hidden']) }}>

        @unless (in_array($field->type, $fields_without_labels) || ($field->input_type ?? null) === 'hidden')
            <x-ui.label :id="$field->id" :display="$field->display" :hide_display="$field->hide_display ?? false" />
        @endunless

        <div class="grid gap-2">
            <x-dynamic-component component="ui.form.{{ $field->type }}" model="form.{{ $field->handle }}" :field="$field" />

            @if ($field->instructions && !in_array($field->type, $fields_without_labels))
                <x-ui.input-instructions :instructions="$field->instructions" :id="$field->id" />
            @endif

            <x-ui.input-error :handle="$field->handle" :id="$field->id" />
        </div>
    </div>
</template>
