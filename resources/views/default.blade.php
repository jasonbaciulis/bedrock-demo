<main id="content">
    @foreach ($page->blocks as $block)
        @include('blocks.' . str_replace('_', '-', $block->type), ['block' => $block])
    @endforeach
</main>
