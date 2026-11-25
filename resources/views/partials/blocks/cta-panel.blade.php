{{--
  The CTA block's split and boxed layouts (WP-108 B). Props: $wrapper (block wrapper attributes, escaped), $heading, $content, $buttons (HTML of the buttons, built in render.php),
  $picture (split only: an <img> or the placeholder).
--}}
<div {!! $wrapper !!}>
  <div class="pkde-cta__text">
    @if ($heading !== '')
      <h2 class="pkde-cta__title">{!! wp_kses_post($heading) !!}</h2>
    @endif
    @if ($content !== '')
      <p class="pkde-cta__line">{!! wp_kses_post($content) !!}</p>
    @endif
    @if ($buttons !== '')
      <div class="pkde-actions">{!! $buttons !!}</div>
    @endif
  </div>
  @if (($picture ?? '') !== '')
    <div class="pkde-cta__media pkde-card__media--flush-right">{!! $picture !!}</div>
  @endif
</div>
