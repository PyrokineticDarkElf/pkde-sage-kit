{{--
  One feature. Props: $icon (inline SVG or ''), $image (an <img> or ''), $heading, $text, $linkText, $linkUrl. With a link and no link text the
  whole heading is the link; with link text a "Learn more" style link follows the text.
--}}
<div class="pkde-feature">
  @if ($image !== '')
    <div class="pkde-feature__media">{!! $image !!}</div>
  @elseif ($icon !== '')
    {!! $icon !!}
  @endif
  @if ($heading !== '')
    <h3 class="pkde-feature__title">
      @if ($linkUrl !== '' && $linkText === '')
        <a href="{{ esc_url($linkUrl) }}">{!! wp_kses_post($heading) !!}</a>
      @else
        {!! wp_kses_post($heading) !!}
      @endif
    </h3>
  @endif
  @if ($text !== '')
    <p class="pkde-feature__text">{!! wp_kses_post($text) !!}</p>
  @endif
  @if ($linkUrl !== '' && $linkText !== '')
    <a class="pkde-feature__link" href="{{ esc_url($linkUrl) }}">{{ $linkText }}</a>
  @endif
</div>
