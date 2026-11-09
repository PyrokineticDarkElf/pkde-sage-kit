{{--
  One "more products" section on the product page (app/woocommerce/product-sections.php, docs/product-sections.md): a heading, previous / next
  buttons and a scroll-snap list of the site's own product cards. Same for every visitor. The buttons start hidden: resources/js/product-carousel.js
  shows them when the list overflows, so without script the list still scrolls by touch, wheel and keyboard.
  $key: upsells | cross-sells | related; $cards: the list items; $moreCount / $moreUrl / $moreLabel: the optional "Show more" (related only).
--}}
<section
  class="pkde-product-section pkde-product-section--{{ $key }}{{ $key === 'related' ? ' related' : '' }}{{ $key === 'upsells' ? ' upsells' : '' }}"
  aria-labelledby="pkde-section-{{ $key }}-title"
  data-pkde-carousel
>
  <div class="pkde-product-section__head">
    <h2 id="pkde-section-{{ $key }}-title" class="pkde-product-section__title">{{ $title }}</h2>
    <div class="pkde-carousel__nav" data-pkde-carousel-nav hidden>
      <button type="button" class="pkde-carousel__button" data-pkde-carousel-prev aria-controls="pkde-section-{{ $key }}-list" aria-label="{{ $prevLabel }}">
        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 5-7 7 7 7"/></svg>
      </button>
      <button type="button" class="pkde-carousel__button" data-pkde-carousel-next aria-controls="pkde-section-{{ $key }}-list" aria-label="{{ $nextLabel }}">
        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 5 7 7-7 7"/></svg>
      </button>
    </div>
  </div>

  <div class="woocommerce pkde-carousel__viewport">
    <ul id="pkde-section-{{ $key }}-list" class="products pkde-carousel__track" data-pkde-carousel-track>
      {!! $cards !!}
    </ul>
  </div>

  @if ($moreCount > 0)
    <p class="pkde-product-section__more">
      <button type="button" class="pkde-product-section__more-button" data-pkde-load-more="{{ $moreUrl }}" data-pkde-more-label="{{ $moreLabel }}">{{ $moreLabel }}</button>
    </p>
  @endif
</section>
