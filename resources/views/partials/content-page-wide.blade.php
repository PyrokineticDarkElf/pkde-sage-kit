{{--
  content-grid (see app.css) — same breakout mechanism as content-page.blade.php,
  but --content-grid-width is overridden to the site's shared content width
  (the whole page measure) instead of the narrower prose default, since this
  template is for pages that are mostly wide/full blocks, not body text.
--}}
<div class="page-content prose-content content-grid {{ \App\page_content_padding(parse_blocks((string) get_post_field('post_content'))) }}" style="--content-grid-width: var(--content-grid-measure)">
  @php(the_content())

  @if ($pagination())
    <nav class="page-nav" aria-label="Page">
      {!! $pagination !!}
    </nav>
  @endif
</div>
