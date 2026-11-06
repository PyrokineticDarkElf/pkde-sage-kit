{{--
  The star rating of a product card ($product, $position: above_title, below_title or above_button; App\card_rating_position()). Nothing for a product
  without reviews. The position class lets the CSS keep the stars and the button together at the card's bottom (shop-pages.css); the order in the
  markup is the order on the screen.
--}}
@if (wc_review_ratings_enabled() && $product->get_rating_count() > 0)
  <div class="pkde-product-card__rating pkde-product-card__rating--{{ str_replace('_', '-', $position ?? 'above_button') }} text-xs text-amber-700">
    {!! wc_get_rating_html($product->get_average_rating()) !!}
  </div>
@endif
