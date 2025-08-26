@props([
    'title',
    'text',
    'margin' => 'mb-20',
])

<div {{ $attributes->class(['text-center max-w-xl mx-auto', $margin]) }}>
    <h2 class="h2 text-pretty">{!! $title !!}</h2>

    @isset($text)
        <p class="mt-6 text-lg text-pretty text-muted-foreground">{!! $text !!}</p>
    @endisset
</div>
