<nav class="hidden lg:flex space-x-10">
    <s:nav handle="header" max_depth="2" select="title|url|is_external|icon|description|badge|two_col_menu">
        @if ($children)
            <div class="relative" x-data="{ subnavOpen: false }">
                <button
                    type="button"
                    class="btn text-neutral-900 gap-x-1"
                    x-bind:class="{ 'opacity-75': subnavOpen }"
                    x-bind:aria-expanded="subnavOpen"
                    x-on:click.prevent="subnavOpen = !subnavOpen"
                >
                    <span>{!! $title !!}</span>
                    <span
                        class="transition-transform duration-500 mt-0.5"
                        x-bind:class="{'translate-y-0.5': subnavOpen }"
                    >
                        <x-lucide-chevron-down class="size-4 opacity-50" />
                    </span>
                </button>

                {{-- Menu. --}}
                <div
                    x-cloak
                    x-show="subnavOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    x-on:click.outside="subnavOpen = false"
                    @class([
                        'absolute z-10 transform mt-5 w-screen',
                        'flex max-w-max px-4 left-1/2 -translate-x-1/2' => $two_col_menu->value(),
                        'left-1/2 -translate-x-1/2 px-2 max-w-xs' => !$two_col_menu->value(),
                    ])
                >
                    <div class="flex-auto overflow-hidden rounded-lg bg-white text-sm/6 shadow border lg:max-w-3xl">
                        <div @class(['relative grid grid-cols-1 gap-x-5 gap-y-1 p-3', 'lg:grid-cols-2' => $two_col_menu->value()])>
                            @foreach ($children as $child)
                                @include('partials.nav-header-item', [...$child])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @else
            <a
                href="{{ $url }}"
                class="btn"
                @if ($is_external) target="_blank" rel="noopener nofollow" @endif
            >
                {!! $title !!}
            </a>
        @endif
    </s:nav>
</nav>
