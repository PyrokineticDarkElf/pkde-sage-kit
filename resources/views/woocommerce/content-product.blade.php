{{--
  The product card in every product list (woocommerce/content-product.php loads it). It draws its own badges, image, title and
  price and fires WooCommerce's five card hooks in their usual places so plugins can add to it; WooCommerce's own output on
  those hooks is removed in app/woocommerce/display.php, apart from the add-to-cart button, which the last hook prints.
--}}
@php
  $productId = $product->get_id();
  // The Sale and New badges link to those views of the shop (?sage_view=), so on archives and searches they are links; on a
  // curated row (a themed block, a related-products list) they are plain labels.
  $badgeLinks = is_shop() || is_product_taxonomy() || is_search();
  // Bridged via $GLOBALS, not a normal Blade prop — see
  // partials/blocks/product-grid.blade.php's own comment on why (WC's
  // wc_get_template_part() has no $args parameter to pass this through in
  // this WooCommerce version). Absent entirely outside a themed loop block
  // (the Shop archive, a single product page) — 'white' there is the plain
  // card look this always had. See app.css's .pkde-section :where(article,
  // .woocommerce ul.products > .product) rule for what the marker class does.
  $cardStyle = $GLOBALS['pkde_product_card_style'] ?? 'white';
@endphp

<li @php(wc_product_class(trim('pkde-product-card group relative flex flex-col gap-3 ' . ($cardStyle !== 'white' ? "pkde-card-{$cardStyle}" : '')), $product))>
  @php(do_action('woocommerce_before_shop_loop_item'))

  {{-- The badges are links on archives, so they sit beside the picture link, not inside it. --}}
  <div class="pkde-product-card__media relative block overflow-hidden rounded-2xl bg-neutral-100">
    @php(do_action('woocommerce_before_shop_loop_item_title'))

    @include('pkde-kit::partials.product-badges', ['links' => $badgeLinks])

    {{-- The picture link repeats the title link below it: hidden from assistive tech and the tab order (axe link-name). --}}
    <a aria-hidden="true" tabindex="-1" href="{{ get_permalink($productId) }}" class="block">
      @if (has_post_thumbnail($productId))
        <div class="aspect-square overflow-hidden bg-neutral-100">
          {!! get_the_post_thumbnail($productId, 'medium_large', ['class' => 'h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.03]']) !!}
        </div>
      @else
        <x-pkde-kit::placeholder-image ratio="aspect-square" label="{{ __('Product image', 'sage') }}" />
      @endif
    </a>
  </div>

  <div class="pkde-product-card__body flex flex-1 flex-col gap-1.5">
    @php($ratingPosition = \App\card_rating_position())
    @if ($ratingPosition === 'above_title')
      @include('pkde-kit::partials.card-rating', ['position' => $ratingPosition])
    @endif

    <h2 class="font-display text-lg font-semibold leading-snug tracking-[-0.02em]">
      <a href="{{ get_permalink($productId) }}" class="text-[color:var(--pkde-card-content)] transition hover:text-[color:var(--pkde-card-accent)]">
        {{ $product->get_name() }}
      </a>
    </h2>
    @php(do_action('woocommerce_shop_loop_item_title'))

    @if ($ratingPosition === 'below_title')
      @include('pkde-kit::partials.card-rating', ['position' => $ratingPosition])
    @endif

    <div class="text-sm text-[color:var(--pkde-card-content)] opacity-75">
      {!! $product->get_price_html() !!}
    </div>
    @php(do_action('woocommerce_after_shop_loop_item_title'))

    {{-- The short description: only the list view shows it (shop-pages.css); the grid card hides it. --}}
    {{-- Decoded before trimming: wp_trim_words() appends an HTML entity and a short description can hold others (&amp;, &#039;), which Blade's {{ }} would escape a second time (tcs). --}}
    @php($excerpt = post_password_required($product->get_id()) ? '' : trim(html_entity_decode(wp_strip_all_tags((string) $product->get_short_description()), ENT_QUOTES | ENT_HTML5, 'UTF-8')))
    @if ($excerpt !== '')
      <p class="pkde-product-card__excerpt text-sm text-neutral-600">{{ wp_trim_words($excerpt, 30, '…') }}</p>
    @endif

    @if ($ratingPosition === 'above_button')
      @include('pkde-kit::partials.card-rating', ['position' => $ratingPosition])
    @endif

    {{-- The action row (pkde-product-actions, resources/css/shop-pages.css): the add-to-cart button, and "View basket" (a.added_to_cart.wc-forward),
         which WooCommerce inserts after it once an AJAX add to cart succeeds. One row at the bottom of the card, whatever the link does. --}}
    <div class="pkde-product-actions pkde-product-actions--default">
      @php(do_action('woocommerce_after_shop_loop_item'))
    </div>
  </div>
</li>
