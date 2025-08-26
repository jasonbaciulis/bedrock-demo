<div
    class="relative bg-white"
    x-data="{
        mobileNavOpen: false,
        scrollbarWidth: 0,
        init() {
            this.scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        },
        preventBodyScroll() {
            document.documentElement.style.setProperty('--scrollbar-width', this.scrollbarWidth + 'px');
            document.body.classList.add('no-scroll');
        },
        restoreBodyScroll() {
            document.body.classList.remove('no-scroll');
        }
    }"
>
    <div class="container">
        <div class="flex justify-between items-center py-6 md:justify-start md:gap-x-10">
            <div class="flex-1 flex">
                <x-logo :logo="$theme->logo" class="w-40 h-8 inline-flex" />
            </div>
            <div class="-mr-2 -my-2 lg:hidden">
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

            <div class="hidden lg:flex items-center justify-end lg:flex-1 lg:w-0 gap-x-2">
                @foreach ($theme->header_buttons as $button)
                    <x-ui.button
                        :button_type="$button->button_type"
                        :link_type="$button->link_type"
                        :url="$button->url"
                        :entry="$button->entry"
                        :email="$button->email"
                        :phone="$button->phone"
                        :asset="$button->asset"
                        :code="$button->code"
                        :target_blank="$button->target_blank"
                    >
                        {!! $button->label !!}
                    </x-ui.button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Hidden from lg and up --}}
    @include('partials.nav-header-mob')
</div>
