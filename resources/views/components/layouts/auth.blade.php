@props([
    'title',
    'description' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />

        <title>{!! $title !!}</title>

        @include('partials.browser-appearance')

        <s:vite src="resources/css/site.css" />

        {{--
            Async-load fonts:
            1) Preload fonts.css (rel="preload" as="style") so it starts downloading early without applying.
            2) Load again with media="print", then switch to media="all" onload to avoid render blocking.
            With font-display: swap in fonts.css, fallback text paints immediately; the webfont swaps in when ready.
        --}}
        <s:vite src="resources/css/fonts.css" attr:style:rel="preload" attr:style:as="style" />
        <s:vite
            src="resources/css/fonts.css"
            attr:style:media="print"
            attr:style:onload="this.media='all'"
        />
    </head>
    <body
        class="selection:bg-primary bg-muted flex min-h-screen items-center justify-center overflow-x-hidden font-sans text-base/6 text-neutral-800 antialiased selection:text-white"
    >
        @include('partials.skip-to-content')

        <div class="flex w-full max-w-sm flex-col gap-6">
            <div class="text-center">
                <x-logo logo="assets/logos/logo-brand.svg" class="inline-flex w-36" />
            </div>
            <div class="card gap-6">
                <div class="card__header text-center">
                    <h1 class="text-xl font-semibold">
                        {{ $title }}
                    </h1>
                    <p class="text-muted-foreground text-sm">
                        {!! $description !!}
                    </p>
                </div>

                {{ $slot }}
            </div>
        </div>

        {{-- Push scripts when various components are added --}}
        @stack('scripts')
        <s:vite src="resources/js/site.js" />
    </body>
</html>
