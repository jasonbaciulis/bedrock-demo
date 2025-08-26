<s:nav handle="footer" max_depth="2" select="title|url|is_external">
    <div>
        <p class="text-sm/6 font-medium text-foreground">{!! $title !!}</p>
        @if (count($children))
            <ul role="list" class="space-y-4 mt-6">
                @foreach ($children as $child)
                    <li>
                        <a
                            href="{{ $child['url'] }}"
                            class="text-sm/6 text-muted-foreground hover:text-foreground"
                            @if ($child['is_external']) target="_blank" rel="noopener nofollow" @endif
                        >
                            {!! $child['title'] !!}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</s:nav>
