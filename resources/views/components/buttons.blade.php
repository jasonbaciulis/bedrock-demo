@props([
    'buttons' => [],
    'size' => null
])

@unless (empty($buttons))
    @if (count($buttons) > 1)
        <div {{ $attributes->class(['flex flex-wrap gap-4']) }}>
            @foreach ($buttons as $button)
                <x-ui.button
                    :size="$size"
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
    @else
        @foreach ($buttons as $button)
            <x-ui.button
                :size="$size"
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
    @endif
@endunless
