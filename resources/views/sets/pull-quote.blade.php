<figure class="my-0 not-prose">
    <blockquote class="h3 leading-snug text-primary text-pretty">
        “{!! $quote !!}”
    </blockquote>
    @unless (empty($author))
        <figcaption class="block mt-4 text-sm text-muted-foreground">
            — {!! $author !!}
        </figcaption>
    @endunless
</figure>
