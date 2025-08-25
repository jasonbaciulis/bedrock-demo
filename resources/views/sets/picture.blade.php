<figure class="my-0 not-prose">
    <x-ui.picture :$image w="800" h="auto" />

    @unless (empty($caption))
        <figcaption class="text-sm block mt-2">
            {!! $caption !!}
        </figcaption>
    @endunless
</figure>
