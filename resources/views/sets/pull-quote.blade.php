<figure class="my-0 not-prose">
    <blockquote class="h3 leading-snug text-primary text-pretty">
        “{!! $quote !!}”
    </blockquote>
    @isset($author)
        <figcaption class="block mt-4 text-sm text-muted-foreground">
            — {!! $author !!}
        </figcaption>
    @endisset
</figure>
