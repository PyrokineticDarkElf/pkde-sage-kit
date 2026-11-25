{{--
  The table of contents of a post: a nav with the scroll-spied active link (data-active, resources/js/kit.js). Always open beside the text on a wide
  screen, a collapsed <details> above it on a narrow one; no animation, so reduced motion needs nothing. The markup is the narrow state (closed) and CSS shows the
  list at 1024px and up (resources/css/post-single.css), so the first paint already has its final state at every width and nothing moves when the script runs
  (TPL-286: an `open` attribute that the script closed on a narrow screen pushed the post body about 25px down).
--}}
@if (count($headings ?? []) > 1)
  <nav data-toc class="post-toc" aria-label="{{ __('Table of contents', 'sage') }}">
    <details class="post-toc__details">
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
