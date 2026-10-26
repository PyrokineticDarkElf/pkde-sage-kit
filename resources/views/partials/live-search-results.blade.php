{{--
  The header search's live results (Relevanssi Live Ajax Search; resources/live-search/search-results.php renders this view). Each row keeps
  the plugin's .relevanssi-live-search-result class and option role: its script moves through them with the arrow keys. The last row opens
  the full results, the shop's own search page. Products only: the form sends post_type=product.
--}}
@php($found = (int) $GLOBALS['wp_query']->found_posts)

@if (have_posts())
  <p class="live-search__status" role="status" aria-live="polite">
    {{ sprintf(_n('%d product found.', '%d products found.', $found, 'sage'), $found) }}
  </p>

  @while (have_posts())
    @php(the_post())
    @php($product = function_exists('wc_get_product') ? wc_get_product(get_the_ID()) : null)
    <div class="relevanssi-live-search-result live-search__result" role="option" aria-selected="false">
      <a href="{{ get_permalink() }}" class="live-search__link">
        {!! $product ? $product->get_image('woocommerce_gallery_thumbnail', ['class' => 'live-search__img', 'alt' => '']) : '' !!}
        <span class="live-search__title">{!! get_the_title() !!}</span>
        <span class="live-search__price">{!! $product ? $product->get_price_html() : '' !!}</span>
      </a>
    </div>
  @endwhile

  <div class="relevanssi-live-search-result live-search__result" role="option" aria-selected="false">
    <a class="live-search__all" href="{{ add_query_arg(['s' => rawurlencode(get_search_query(false)), 'post_type' => 'product'], home_url('/')) }}">
      {{ sprintf(_n('See the %d result', 'See all %d results', $found, 'sage'), $found) }}
    </a>
  </div>
@else
  <p class="live-search__none" role="status" aria-live="polite">{{ __('No products found. Try fewer or different words.', 'sage') }}</p>
@endif
