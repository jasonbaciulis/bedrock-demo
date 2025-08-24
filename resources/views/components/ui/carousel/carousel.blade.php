{{--
    Carousel component similar to shadcn/ui carousel
    Uses Embla Carousel with Alpine.js state management
--}}

@props([
    'orientation' => 'horizontal',
    'opts' => '',
])

@once
    @push('scripts')
        @vite('resources/js/embla.js')
    @endpush
@endonce

<div
    {{ $attributes->class([
        'relative outline-none',
    ]) }}
    role="region"
    aria-roledescription="carousel"
    x-data="{
        emblaApi: null,
        orientation: '{{ $orientation }}',
        canScrollPrev: false,
        canScrollNext: false,

        init() {
            const options = {
                axis: this.orientation === 'horizontal' ? 'x' : 'y',
                {!! $opts !!}
            };

            this.emblaApi = window.EmblaCarousel(this.$refs.viewport, options);

            this.updateScrollButtons();
            this.emblaApi.on('select', () => this.updateScrollButtons());
            this.emblaApi.on('reInit', () => this.updateScrollButtons());
        },

        updateScrollButtons() {
            if (!this.emblaApi) return;
            this.canScrollPrev = this.emblaApi.canScrollPrev();
            this.canScrollNext = this.emblaApi.canScrollNext();
        },

        scrollPrev() {
            if (this.emblaApi) this.emblaApi.scrollPrev();
        },

        scrollNext() {
            if (this.emblaApi) this.emblaApi.scrollNext();
        },
    }"
    x-init="init()"
    x-on:keydown.left.prevent="scrollPrev()"
    x-on:keydown.right.prevent="scrollNext()"
    tabindex="0"
>
    <div x-ref="viewport" class="overflow-hidden">
        <div
            {{ $content->attributes->class([
                'flex',
                '-ml-4' => $orientation === 'horizontal',
                '-mt-4 flex-col' => $orientation === 'vertical',
            ]) }}
        >
            {{ $content }}
        </div>
    </div>

    {{ $navigation }}
</div>
