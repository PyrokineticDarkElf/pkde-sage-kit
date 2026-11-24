{{--
  The Hero block's split and text-only layouts (WP-100). No panel of its own: the colours are the --section-* roles of the Brand section theme
  the hero sits in (resources/css/sections.css), so the same hero is light in "Brand Sage (light)" and dark in "Brand Slate".
  Props: $wrapper (the block wrapper attributes, escaped), $layout (split|text), $eyebrow, $heading, $level (1-4), $body, $buttonText/$buttonUrl,
         $secondaryText/$secondaryUrl, $image (an <img> from \App\block_image(), split only).
--}}
<div {!! $wrapper !!}>
  <div @class(['pkde-hero__grid', 'pkde-split' => $layout === 'split' && $image !== ''])>
    <div class="pkde-hero__copy">
      @if ($eyebrow !== '')
        <p class="pkde-eyebrow">{{ $eyebrow }}</p>
      @endif
      @if ($heading !== '')
        <h{{ $level }} class="pkde-hero__title">{!! wp_kses_post($heading) !!}</h{{ $level }}>
      @endif
      @if ($body !== '')
        <p class="pkde-hero__text">{!! wp_kses_post($body) !!}</p>
      @endif
      @if (($buttonText && $buttonUrl) || ($secondaryText && $secondaryUrl))
        <div class="pkde-actions">
          @if ($buttonText && $buttonUrl)
            <a href="{{ esc_url($buttonUrl) }}" class="{{ \App\button_classes($buttonStyle ?? 'primary') }}">{{ $buttonText }}</a>
          @endif
          @if ($secondaryText && $secondaryUrl)
            <a href="{{ esc_url($secondaryUrl) }}" class="{{ \App\button_classes($secondaryButtonStyle ?? 'secondary') }}">{{ $secondaryText }}</a>
          @endif
        </div>
      @endif
    </div>
    @if ($layout === 'split' && $image !== '')
      <div class="pkde-split__media">{!! $image !!}</div>
    @endif
  </div>
</div>
