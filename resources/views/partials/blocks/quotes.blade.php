{{--
  Testimonials. Props: $wrapper (block wrapper attributes, escaped), $layout (carousel|strip|panel), $eyebrow, $heading, $quotes (list of
  quote/name/role/rating), $stars (show the rating). The carousel is the kit's Embla carousel (resources/js/carousel.js: data-carousel*).
--}}
@php
  $star = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>';
  $card = function (array $quote) use ($stars, $star) {
      return view('pkde-kit::partials.blocks.quote', ['quote' => $quote, 'stars' => $stars, 'star' => $star])->render();
  };
@endphp
<div {!! $wrapper !!} @if ($layout === 'carousel') data-carousel data-carousel-loop="true" data-carousel-autoplay="false" @endif>
  @if ($heading !== '' || $eyebrow !== '')
    <div class="pkde-quotes__head">
      @if ($eyebrow !== '')
        <p class="pkde-eyebrow">{{ $eyebrow }}</p>
      @endif
      @if ($heading !== '')
        <h2 class="pkde-quotes__title">{!! wp_kses_post($heading) !!}</h2>
      @endif
    </div>
  @endif

  @if ($layout === 'carousel')
    <div data-carousel-viewport class="overflow-hidden" role="region" aria-roledescription="carousel" aria-label="{{ esc_attr__('Customer testimonials', 'sage') }}">
      <div data-carousel-track>
        @foreach ($quotes as $quote)
          <div class="pkde-quotes__slide">{!! $card($quote) !!}</div>
        @endforeach
      </div>
    </div>
    @if (count($quotes) > 1)
      <div class="pkde-quotes__dots">
        @foreach ($quotes as $index => $quote)
          <button type="button" data-carousel-dot aria-label="{{ esc_attr(sprintf(__('Go to testimonial %d', 'sage'), $index + 1)) }}"><span class="pkde-dot"></span></button>
        @endforeach
      </div>
    @endif
  @elseif ($layout === 'strip')
    <div class="pkde-quotes__grid">
      @foreach (array_slice($quotes, 0, 3) as $quote)
        {!! $card($quote) !!}
      @endforeach
    </div>
  @else
    {!! $card($quotes[0]) !!}
  @endif
</div>
