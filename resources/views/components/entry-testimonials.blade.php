@props(['entry'])

<figure class="rounded-2xl bg-primary-foreground p-8 text-sm/6">
    <blockquote class="text-neutral-900">
        <p>“{!! $entry->quote !!}”</p>
    </blockquote>
    <figcaption class="mt-6 flex items-center gap-x-4">
        <x-ui.avatar :image="$entry->avatar" w="40" h="40" class="size-10" :name="$entry->title" />
        <div>
            <p class="font-semibold text-neutral-900">{!! $entry->title !!}</p>
            <p class="text-muted-foreground">{!! $entry->author_title !!}</p>
        </div>
    </figcaption>
</figure>
