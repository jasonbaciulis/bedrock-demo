{{--
    This component is a child component used to create tab panels.
    It needs to be used together with the `tab-trigger` component and a parent Tabs component that has a property
    that controls an active tab. E.g. x-data="{ activeTab: 0 }".
--}}

@props([
    'name',
    'alpine_property' => 'activeTab', // Alpine property of the parent component that controls the active tab.
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
