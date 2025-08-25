@once
    @push('scripts')
        @vite('resources/css/lite-yt-embed.css')
        @vite('resources/js/lite-yt-embed.js')
    @endpush
@endonce

@php($youtube_id = Statamic::tag('youtube_id')->youtubeUrl($youtube_url)->fetch())

<figure class="my-0 not-prose">
    <lite-youtube videoid="{{ $youtube_id }}" style="background-image: url('https://i.ytimg.com/vi/{{ $youtube_id }}/hqdefault.jpg');" class="!max-w-none">
        <a href="{{ $youtube_url }}" class="lty-playbtn" title="Play Video">
            <span class="lyt-visually-hidden">Play Video</span>
        </a>
    </lite-youtube>

    @unless (empty($caption))
        <figcaption class="text-sm block mt-2 text-muted-foreground">
            {!! $caption !!}
        </figcaption>
    @endunless
</figure>
