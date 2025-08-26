<footer class="pb-12 pt-20 bg-white" aria-labelledby="footer">
    <h2 id="footer" class="sr-only">Footer</h2>
    <div class="container">
        <hr />
        <div class="py-20 xl:site-grid">
            <div class="space-y-6 xl:col-span-4 xl:-mt-1">
                <x-logo class="w-40 h-8 inline-flex" :logo="$theme->logo" />

                <p class="text-muted-foreground text-sm leading-6 max-w-prose xl:max-w-xs text-pretty">
                    {!! $theme->about !!}
                </p>
            </div>
            <div class="mt-12 grid grid-cols-2 gap-x-8 gap-y-12 md:grid-cols-4 xl:mt-0 xl:col-span-8">
                @include('partials.nav-footer')
            </div>
        </div>
        <hr />
        <div class="pt-12">
            <div class="space-y-8 justify-between lg:items-center flex-row-reverse md:space-y-0 md:flex">
                @include('partials.nav-social')

                <div class="space-y-8 flex-row-reverse items-center lg:space-y-0 lg:flex lg:gap-x-16">
                    @include('partials.nav-bottom-footer')

                    <div class="text-xs text-muted-foreground prose">
                        {!! str_replace(':copyright_year', '&copy; ' . now()->format('Y'), $theme->copyright_text) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
