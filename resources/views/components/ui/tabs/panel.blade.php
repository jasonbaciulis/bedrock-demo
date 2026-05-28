@props ([
    'name',
    'alpine_property' => 'activeTab',
])

<div
    x-cloak
    x-show="{{ $alpine_property }} == '{{ $name }}'"
    id="tabpanel-{{ $name }}"
    aria-labelledby="tab-{{ $name }}"
    role="tabpanel"
    x-bind:tabindex="{{ $alpine_property }} == '{{ $name }}' ? 0 : -1"
    {{ $attributes }}
>
    {{ $slot }}
</div>
