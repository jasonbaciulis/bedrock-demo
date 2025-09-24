<div
    class="relative bg-white"
    x-data="{
        mobileNavOpen: false,
        scrollbarWidth: 0,
        init() {
            this.scrollbarWidth =
                window.innerWidth - document.documentElement.clientWidth
        },
        preventBodyScroll() {
            document.documentElement.style.setProperty(
                '--scrollbar-width',
                this.scrollbarWidth + 'px',
            )
            document.body.classList.add('no-scroll')
        },
        restoreBodyScroll() {
            document.body.classList.remove('no-scroll')
        },
    }"
>
    <div class="container">
        <div class="flex items-center justify-between py-6 md:justify-start md:gap-x-10">
            <div class="flex flex-1">
                <x-logo :logo="$theme->logo" class="inline-flex h-8 w-40" />
            </div>
            <div class="-my-2 -mr-2 lg:hidden">
                <button
                    type="button"
                    class="btn btn--ghost btn--square"
                    x-bind:aria-expanded="mobileNavOpen"
                    x-on:click.prevent="mobileNavOpen = !mobileNavOpen"
                    aria-label="Open menu"
                >
                    <x-lucide-menu class="size-5" />
                </button>
            </div>

            @include('partials.nav-header-desktop')

            <div class="hidden items-center justify-end gap-x-2 lg:flex lg:w-0 lg:flex-1">
                <div class="hidden items-center justify-end gap-x-2 lg:flex lg:w-0 lg:flex-1">
                    @if (auth()->guard('web')->check())
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn--ghost">
                                {{ __('Log out') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn--ghost">
                            {{ __('Sign in') }}
                        </a>
                    @endif

                    <a href="{{ route('register') }}" class="btn btn--primary">
                        {{ __('Get started') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden from lg and up --}}
    @include('partials.nav-header-mob')
</div>
