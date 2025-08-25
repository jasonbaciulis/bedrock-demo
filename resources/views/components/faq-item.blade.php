@props(['title', 'text'])

<x-ui.collapsible class="group">
    <x-slot:trigger class="py-6 text-left w-full flex items-center justify-between gap-x-6 hover:underline underline-offset-4">
        <span class="content-lg text-neutral-900 font-medium">{!! $title !!}</span>
        <span class="flex-none size-6 text-neutral-800/70" aria-hidden="true">
            <x-lucide-plus x-show="!open" />
            <x-lucide-minus x-show="open" />
        </span>
    </x-slot:trigger>

    <x-slot:content class="-top-2 pr-12">
        <div class="content-sm pb-4 prose text-muted-foreground text-pretty max-w-none">
            {!! $text !!}
        </div>
    </x-slot:content>
</x-ui.collapsible>
