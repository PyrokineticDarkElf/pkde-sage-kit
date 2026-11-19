{{--
  The title band. Its colours, the H1's font, weight and tracking, and the breadcrumb colour are CSS variables (--pkde-page-header-*, see
  resources/css/page-header.css): a site sets them in its own CSS instead of restyling .page-header.
  $intro (optional, a STRING: passed in, else App\page_header_intro(); never a composer method, which Acorn hands over as an always-set object): a short paragraph under the H1 - the posts page's excerpt, or a term's description where no toggle
  prints it. Filter: pkde_kit/page_header_intro (return '' to switch it off).
  Slots for a decoration, instead of a copy of this view (TPL-196): the actions pkde_kit/page_header_start (first thing inside the band, before the
  container: an absolutely placed sigil or number; give .page-header `position: relative; overflow: hidden` in the site's CSS for that) and
  pkde_kit/page_header_after_title (after the H1, inside the container: a rule), each called with the title.
--}}
@php($intro = is_string($intro ?? null) ? $intro : (function_exists('App\\page_header_intro') ? \App\page_header_intro() : ''))
<div class="page-header">
  @php(do_action('pkde_kit/page_header_start', $title))
  <div class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'page-header') }} page-header__inner">
    @include('pkde-kit::partials.breadcrumbs')
    <h1 class="mt-3 max-w-3xl font-display text-4xl leading-tight sm:text-5xl">{!! $title !!}</h1>
    @php(do_action('pkde_kit/page_header_after_title', $title))
    @if (trim($intro) !== '')
      <div class="page-header__intro mt-4">{!! wp_kses_post($intro) !!}</div>
    @endif
  </div>
</div>
