<nav aria-label="breadcrumb" {{ $attributes->class(['overflow-hidden max-w-[calc(100vw-48px)]']) }}>
    <ol
        vocab="https://schema.org/"
        typeof="BreadcrumbList"
        class="text-muted-foreground flex gap-1.5 text-sm sm:gap-2.5"
    >
        <s:nav:breadcrumbs>
            <li
                class="flex items-center gap-1.5 {{ $loop->last ? 'truncate' : '' }}"
                property="itemListElement"
                typeof="ListItem"
            >
                @unless ($loop->last)
                    <a
                        href="{{ $url }}"
                        property="item"
                        typeof="WebPage"
                        class="hover:text-foreground whitespace-nowrap"
                    >
                        <span property="name">{!! $title !!}</span>
                    </a>
                    <meta property="position" content="{{ $loop->iteration }}" />
                    <x-lucide-chevron-right class="size-3.5 shrink-0" />
                @else
                    <span property="name" class="text-primary truncate" aria-current="page">
                        <span property="name">{!! $title !!}</span>
                    </span>
                    <meta property="position" content="{{ $loop->iteration }}" />
                @endunless
            </li>
        </s:nav:breadcrumbs>
    </ol>
</nav>
