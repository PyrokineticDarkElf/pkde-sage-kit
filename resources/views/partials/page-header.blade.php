{{--
  The title band. Its colours, the H1's font, weight and tracking, and the breadcrumb colour are CSS variables (--pkde-page-header-*, see
  resources/css/page-header.css): a site sets them in its own CSS instead of restyling .page-header.
  $intro (optional, from App\page_header_intro()): a short paragraph under the H1 - the posts page's excerpt, or a term's description where no toggle
  prints it. Filter: pkde_kit/page_header_intro (return '' to switch it off).
--}}
<div class="page-header">
  <div class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)] py-14 sm:py-16', 'page-header') }}">
    @include('pkde-kit::partials.breadcrumbs')
    <h1 class="mt-3 max-w-3xl font-display text-4xl leading-tight sm:text-5xl">{!! $title !!}</h1>
    @if (! empty($intro))
      <div class="page-header__intro mt-4">{!! wp_kses_post($intro) !!}</div>
    @endif
  </div>
</div>
