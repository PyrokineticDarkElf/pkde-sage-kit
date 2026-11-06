{{--
  The table of contents of a post: a nav with the scroll-spied active link (data-active, resources/js/kit.js). Always open beside the text on a wide
  screen, a collapsed <details> above it on a narrow one (kit.js opens and closes it with the breakpoint); no animation, so reduced motion needs nothing.
--}}
@if (count($headings ?? []) > 1)
  <nav data-toc class="post-toc" aria-label="{{ __('Table of contents', 'sage') }}">
    <details class="post-toc__details" open>
      <summary>{{ __('On this page', 'sage') }}</summary>
      <ul>
        @foreach ($headings as $heading)
          <li data-level="{{ $heading['level'] }}">
            <a href="#{{ $heading['id'] }}" data-toc-link="{{ $heading['id'] }}">{{ $heading['text'] }}</a>
          </li>
        @endforeach
      </ul>
    </details>
  </nav>
@endif
