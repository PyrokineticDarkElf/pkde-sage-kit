{{-- One recently viewed product: image, name, price. $product: WC_Product. Small on purpose (it sits in a sidebar); the site's own card is for the grid. --}}
<li class="pkde-recent__item">
  <a class="pkde-recent__link" href="{{ get_permalink($product->get_id()) }}">
    <span class="pkde-recent__image">{!! $product->get_image('woocommerce_thumbnail', ['alt' => '']) !!}</span>
    <span class="pkde-recent__name">{{ $product->get_name() }}</span>
  </a>
  <span class="pkde-recent__price">{!! $product->get_price_html() !!}</span>
</li>
