@props(['entry'])

<div {{ $attributes->class(['flex items-center gap-4']) }}>
  <time datetime="{{ Statamic::modify($entry->date)->format('c') }}" class="text-xs text-muted-foreground">
      {{ Statamic::modify($entry->date)->format('M j, Y') }}
  </time>
  @unless (empty($entry->categories->title))
      <span class="badge badge--outline">{{ $entry->categories->title }}</span>
  @endunless
</div>
