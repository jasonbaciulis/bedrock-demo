@foreach (collect($seo->collection_defaults)->where('collection', $collection) as $item)
    @if ($item['fallback'] == 'field')
        @foreach ($item['field'] as $field)
            {!! Statamic::modify($field['field_handle'])->stripTags()->entities()->trim()->safeTruncate(159, '…') !!}
        @endforeach
    @elseif ($item['fallback'] == 'custom_text')
        {!! $item['custom_text'] !!}
    @elseif ($item['fallback'] == 'blocks')
        @if ($first_block = Arr::first($item['blocks'], fn($block) => $block['type'] == 'text'))
            @if (is_array($first_block))
                {!! Statamic::modify($first_block)
                        ->raw()
                        ->where('type', 'paragraph')
                        ->bardText()
                        ->stripTags()
                        ->entities()
                        ->trim()
                        ->safeTruncate(159, '…')
                !!}
            @else
                {!! Statamic::modify($first_block)
                    ->stripTags()
                    ->entities()
                    ->trim()
                    ->safeTruncate(159, '…')
                !!}
            @endif
        @else
            @foreach ($item['blocks'] as $block)
                @if ($block['type'] == 'article')
                    {!! Statamic::modify($block['article'])
                        ->raw()
                        ->where('type', 'paragraph')
                        ->bardText()
                        ->stripTags()
                        ->entities()
                        ->trim()
                        ->safeTruncate(159, '…')
                    !!}
                @endif
            @endforeach
        @endif
    @endif
@endforeach
