{{--
  content-grid (see app.css) — same breakout mechanism as content-page.blade.php,
  but --content-grid-width is overridden to the site's shared content width
  (min(var(--container-content), 100%)) instead of the narrower prose default, since this
  template is for pages that are mostly wide/full blocks, not body text.
--}}
<div class="page-content prose-content content-grid py-16" style="--content-grid-width: min(var(--container-content, 80rem), 100%)">
  @php(the_content())

  @if ($pagination())
    <nav class="page-nav" aria-label="Page">
      {!! $pagination !!}
    </nav>
  @endif
</div>
