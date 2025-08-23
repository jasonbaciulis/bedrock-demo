@props([
    'model',
    'field_data' => [],
])

@include('components.ui.form.combobox', array_merge($field_data, ['model' => $model]))
