@props([
    'model',
    'field_data' => [],
])

@include('components.ui.form.text', array_merge($field_data, ['model' => $model]))
