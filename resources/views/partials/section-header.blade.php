<div class="text-center max-w-xl mx-auto {{ $margin ?? 'mb-20' }}">
    <h2 class="h2 text-pretty">{{ $title }}</h2>

    @unless (empty($text))
        <p class="mt-6 text-lg text-pretty text-muted-foreground">{!! $text !!}</p>
    @endunless
</div>
