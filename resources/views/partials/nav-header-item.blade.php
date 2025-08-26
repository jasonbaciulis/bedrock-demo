<a
    href="{{ $url }}"
    class="group relative flex items-center lg:items-start gap-x-4 rounded-lg -mx-2 lg:m-0 p-2 lg:p-3 transition-colors hover:bg-accent"
    @if ($is_external) target="_blank" rel="noopener nofollow" @endif
>
    @unless (empty($icon->value()))
        <s:svg :src="$icon" class="lg:mt-1 size-6 text-muted-foreground transition-colors group-hover:text-primary" />
    @endunless
    <div>
        <span class="font-medium text-foreground">
            {!! $title !!}
        </span>
        @unless (empty($badge->value()))
            <span class="badge badge--outline relative -top-px ml-1.5">
                {!! $badge !!}
            </span>
        @endunless
        @unless (empty($description))
            <p class="hidden lg:block text-muted-foreground text-pretty">
                {!! $description !!}
            </p>
        @endunless
    </div>
</a>
