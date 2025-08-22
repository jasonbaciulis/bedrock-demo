@props(['id', 'instructions'])

<p {{ $attributes->class(['text-sm text-muted-foreground']) }} id="{{ $id }}-instructions">
    {{ $instructions }}
</p>
