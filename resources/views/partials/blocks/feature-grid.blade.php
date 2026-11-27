{{--
  Feature grid. Props: $wrapper (block wrapper attributes, escaped), $eyebrow, $heading, $intro, $content (the rendered feature items), $linkText and $linkUrl (an optional link under the grid), $scrolls (the People style: the row scrolls sideways, so it is a labelled, focusable region).
--}}
<div {!! $wrapper !!}>
  @if ($heading !== '' || $intro !== '' || $eyebrow !== '')
    <div class="pkde-features__head">
      @if ($eyebrow !== '')
        <p class="pkde-eyebrow">{{ $eyebrow }}</p>
      @endif
      @if ($heading !== '')
        <h2 class="pkde-features__title">{!! wp_kses_post($heading) !!}</h2>
      @endif
      @if ($intro !== '')
        <p class="pkde-features__intro">{!! wp_kses_post($intro) !!}</p>
      @endif
    </div>
  @endif
  <div class="pkde-features__grid"@if ($scrolls) role="region" tabindex="0" aria-label="{{ wp_strip_all_tags($heading) !== '' ? wp_strip_all_tags($heading) : __('Team', 'sage') }}"@endif>{!! $content !!}</div>
  @if ($linkUrl !== '' && $linkText !== '')
    <p class="pkde-features__more"><a href="{{ esc_url($linkUrl) }}">{{ $linkText }}</a></p>
  @endif
</div>
