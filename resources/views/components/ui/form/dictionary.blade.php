@props([
    'model',
    'options',
    'placeholder' => 'Select a value…',
    'handle',
    'id',
    'instructions',
    'display',
])

@include('components.ui.form.combobox', array_merge($field, ['model' => $model]))
