@props(['entry'])

<article {{ $attributes }}>
    <div class="aspect-video lg:aspect-3/2 bg-gray-100 rounded-2xl ring-1 ring-gray-900/10 ring-inset">
        <x-ui.picture :image="$entry->image" w="672" h="378" cover="true" class="rounded-2xl" />
    </div>

    <x-entry-meta :$entry class="mt-8" />

    <a href="{{ $entry->url }}" class="block mt-3 group">
        <h3 class="h5 text-pretty group-hover:underline underline-offset-4">{!! $entry->title !!}</h3>
        <p class="mt-5 line-clamp-3 text-sm/6 text-muted-foreground">
            {!! $entry->excerpt !!}
        </p>
    </a>
</article>
