<section id="{{ Statamic::modify($block->type)->slugify() }}" class="m-section">
    <div class="container max-w-4xl">
        <x-section-header :title="$block->title" :description="$block->description" />

        <dl class="divide-y divide-gray-300 border-b border-gray-300">
            @foreach ($block->items as $item)
                <x-faq-item :title="$item->title" :text="$item->text" />
            @endforeach
        </dl>
    </div>
</section>

<script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "FAQPage",
        "mainEntity": [
            @foreach ($block->items as $item)
                {
                    "@@type": "Question",
                    "name": "{{ $item->title }}",
                    "acceptedAnswer": {
                        "@@type": "Answer",
                        "text": "{{ $item->text }}"
                    }
                }{{ !$loop->last ? ',' : '' }}
            @endforeach
        ]
    }
</script>
