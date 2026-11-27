{{--
  Feature grid. Props: $wrapper (block wrapper attributes, escaped), $eyebrow, $heading, $intro, $content (the rendered feature items), $linkText and $linkUrl (an optional link under the grid), $scrolls (the People style: the row is a carousel, a labelled region), $carousel (its settings: loop, autoplay, delay in ms, duration, dots, arrows).
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
  @if ($scrolls)
    <div class="pkde-features__carousel" data-carousel data-carousel-loop="{{ $carousel['loop'] ? 'true' : 'false' }}" data-carousel-autoplay="{{ $carousel['autoplay'] ? 'true' : 'false' }}" data-carousel-autoplay-delay="{{ $carousel['delay'] }}" data-carousel-duration="{{ $carousel['duration'] }}">
      <div class="pkde-features__grid" data-carousel-viewport role="region" aria-roledescription="carousel" aria-label="{{ wp_strip_all_tags($heading) !== '' ? wp_strip_all_tags($heading) : __('Team', 'sage') }}">
        <div class="pkde-features__track" data-carousel-track>{!! $content !!}</div>
      </div>
      @if ($carousel['dots'] || $carousel['arrows'] || $carousel['autoplay'])
        <div class="pkde-features__controls">
          @if ($carousel['arrows'])
            <button type="button" class="pkde-features__arrow" data-carousel-prev aria-label="{{ esc_attr__('Previous', 'sage') }}"><span aria-hidden="true">&lsaquo;</span></button>
          @endif
          @if ($carousel['dots'])
            <div class="pkde-features__dots" data-carousel-dots data-label="{{ esc_attr__('Go to position', 'sage') }}"></div>
          @endif
          @if ($carousel['arrows'])
            <button type="button" class="pkde-features__arrow" data-carousel-next aria-label="{{ esc_attr__('Next', 'sage') }}"><span aria-hidden="true">&rsaquo;</span></button>
          @endif
          @if ($carousel['autoplay'])
            <button type="button" class="pkde-features__pause" data-carousel-autoplay-toggle data-label-pause="{{ esc_attr__('Pause the slideshow', 'sage') }}" data-label-play="{{ esc_attr__('Start the slideshow', 'sage') }}" aria-label="{{ esc_attr__('Pause the slideshow', 'sage') }}"><span class="pkde-features__pause-pause" aria-hidden="true">&#10074;&#10074;</span><span class="pkde-features__pause-play" aria-hidden="true">&#9654;</span></button>
          @endif
        </div>
      @endif
    </div>
  @else
    <div class="pkde-features__grid">{!! $content !!}</div>
  @endif
  @if ($linkUrl !== '' && $linkText !== '')
    <p class="pkde-features__more"><a href="{{ esc_url($linkUrl) }}">{{ $linkText }}</a></p>
  @endif
</div>
