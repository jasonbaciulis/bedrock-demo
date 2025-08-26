<figure class="my-0 not-prose">
    <x-ui.picture :$image w="800" h="auto" />

    @isset($caption)
        <figcaption class="text-sm block mt-2">
            {!! $caption !!}
        </figcaption>
    @endisset
</figure>
