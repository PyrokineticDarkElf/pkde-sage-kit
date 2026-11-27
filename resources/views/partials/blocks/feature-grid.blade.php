{{--
  Feature grid. Props: $wrapper (block wrapper attributes, escaped), $eyebrow, $heading, $intro, $content (the rendered feature items).
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
  <div class="pkde-features__grid">{!! $content !!}</div>
</div>
