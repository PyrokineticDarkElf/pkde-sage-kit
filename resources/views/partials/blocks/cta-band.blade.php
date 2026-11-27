{{--
  The global CTA band (tier 3). Props: $wrapper (block wrapper attributes, escaped), $text, $buttonText, $buttonUrl, $logo (an <img> or '').
  The colours are the --section-* roles of the Brand section theme named in the declaration (the wrapper carries is-style-brand-*).
--}}
<div {!! $wrapper !!}>
  <div class="pkde-cta-band__inner pkde-block">
    @if ($logo !== '')
      {!! $logo !!}
    @endif
    <p class="pkde-cta-band__text">{!! wp_kses_post($text) !!}</p>
    @if ($buttonText !== '' && $buttonUrl !== '')
      <a href="{{ esc_url($buttonUrl) }}" class="pkde-btn">{{ $buttonText }}</a>
    @endif
  </div>
</div>
