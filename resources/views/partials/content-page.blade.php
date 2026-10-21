{{--
  content-grid (see app.css) — wide/full blocks in the_content() break out
  of this prose column via grid-column, not a viewport-unit hack. Default
  --content-grid-width is already min(48rem, 90%), the prose reading width
  this page wants, so no override is needed here.
--}}
<div class="page-content prose-content content-grid py-16">
  @php(the_content())

  @if ($pagination())
    <nav class="page-nav" aria-label="Page">
      {!! $pagination !!}
    </nav>
  @endif
</div>
