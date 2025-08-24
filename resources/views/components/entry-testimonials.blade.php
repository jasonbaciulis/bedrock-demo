@props([
    'entry',
    'fetched_from_rest_api' => false,
])

<figure class="rounded-2xl bg-primary-foreground p-8 text-sm/6">
    <blockquote class="text-neutral-900">
        @if ($fetched_from_rest_api)
            <p x-html="`“${entry.quote}”`"></p>
        @else
            <p>“{!! $entry->quote !!}”</p>
        @endif
    </blockquote>
    <figcaption class="mt-6 flex items-center gap-x-4">
        @if ($fetched_from_rest_api)
            <div class="shrink-0 rounded-full overflow-hidden bg-primary-foreground size-10">
                <img :src="`${entry.avatar.url}`" :alt="`${entry.title}'s avatar`" loading="lazy" class="object-cover size-full">
            </div>
        @else
            <x-ui.avatar :image="$entry->avatar" w="40" h="40" class="size-10" :name="$entry->title" />
        @endif
        <div>
            @if ($fetched_from_rest_api)
                <p class="font-semibold text-neutral-900" x-text="entry.title"></p>
                <p class="text-muted-foreground" x-text="entry.author_title"></p>
            @else
                <p class="font-semibold text-neutral-900">{!! $entry->title !!}</p>
                <p class="text-muted-foreground">{!! $entry->author_title !!}</p>
            @endif
        </div>
    </figcaption>
</figure>
