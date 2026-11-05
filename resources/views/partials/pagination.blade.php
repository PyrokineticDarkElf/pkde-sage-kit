{{--
  Numeric pagination for the main query (the blog, category, tag, author, date and search archives): previous and next arrows, the numbers,
  aria-current on the current page, one look with the shop's pagination (resources/css/shop-pages.css, .pkde-pagination). Nothing when there is
  one page. The arrows and the numbers carry screen-reader text; the page links are WordPress's own (paginate_links), so they keep the query
  string (a search, a filter).
--}}
@php
  $links = paginate_links([
      'type' => 'list',
      'mid_size' => 1,
      'prev_text' => '<span aria-hidden="true">&larr;</span><span class="sr-only">' . __('Previous page', 'sage') . '</span>',
      'next_text' => '<span aria-hidden="true">&rarr;</span><span class="sr-only">' . __('Next page', 'sage') . '</span>',
      'before_page_number' => '<span class="sr-only">' . __('Page', 'sage') . ' </span>',
  ]);
@endphp
@if ($links)
  <nav class="pkde-pagination" aria-label="{{ __('Posts navigation', 'sage') }}">
    {!! $links !!}
  </nav>
@endif
