<section id="{{ Statamic::modify($block->type)->slugify() }}" class="m-section">
    <div class="container">
        <x-section-header :title="$block->title" :text="$block->text ?? null" />

        @if ($block->query->value() === 'custom')
            @php($entries = $block->entries)
        @elseif ($block->query->value() === 'latest')
            @php($entries = Statamic::tag('collection:posts')->limit($block->limit)->sort('date:desc')->fetch())
        @elseif ($block->query->value() === 'featured')
            @php($entries = Statamic::tag('collection:posts')->limit($block->limit)->featured()->sort('order')->fetch())
        @endif

        <div class="site-grid gap-y-20 max-w-2xl mx-auto lg:max-w-none">
            @foreach ($entries as $entry)
                <x-entry-posts :$entry class="col-span-full lg:col-span-4" />
            @endforeach
        </div>
    </div>
</section>
