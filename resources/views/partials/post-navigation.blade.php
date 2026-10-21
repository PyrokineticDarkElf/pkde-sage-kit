@php
  $prevLink = get_previous_post_link('%link', '&larr; %title');
  $nextLink = get_next_post_link('%link', '%title &rarr;');
@endphp

@if ($prevLink || $nextLink)
  <nav
    class="mt-12 flex items-center justify-between gap-6 border-t border-neutral-200 pt-8 text-sm font-medium [&_a]:transition [&_a:hover]:text-neutral-500"
    aria-label="{{ __('Post navigation', 'sage') }}"
  >
    <div>{!! $prevLink !!}</div>
    <div class="text-right">{!! $nextLink !!}</div>
  </nav>
@endif
