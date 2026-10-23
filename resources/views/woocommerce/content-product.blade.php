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

<li @php(wc_product_class(trim('group relative flex flex-col gap-3 ' . ($cardStyle !== 'white' ? "pkde-card-{$cardStyle}" : '')), $product))>
  @php(do_action('woocommerce_before_shop_loop_item'))

  {{-- The badges are links on archives, so they sit beside the picture link, not inside it. --}}
  <div class="relative block overflow-hidden rounded-2xl bg-neutral-100">
    @php(do_action('woocommerce_before_shop_loop_item_title'))

    @if ($product->is_on_sale())
      @if ($badgeLinks)
        <a href="{{ add_query_arg('sage_view', 'sale', wc_get_page_permalink('shop')) }}" rel="nofollow" aria-label="{{ sprintf(__('%1$s: %2$s (see all products on sale)', 'sage'), __('Sale', 'sage'), $product->get_name()) }}" class="absolute left-3 top-3 z-10 rounded-full bg-neutral-950 px-3 py-1 text-[11px] font-semibold text-white">{{ __('Sale', 'sage') }}</a>
      @else
        <span class="absolute left-3 top-3 z-10 rounded-full bg-neutral-950 px-3 py-1 text-[11px] font-semibold text-white">{{ __('Sale', 'sage') }}<span class="sr-only">: {{ $product->get_name() }}</span></span>
      @endif
    @endif

    @if (\App\RecentContent::isNew($productId, 'product'))
      @if ($badgeLinks)
        <a href="{{ add_query_arg('sage_view', 'new', wc_get_page_permalink('shop')) }}" rel="nofollow" aria-label="{{ sprintf(__('%1$s: %2$s (see the newest products)', 'sage'), __('New', 'sage'), $product->get_name()) }}" class="absolute right-3 top-3 z-10 rounded-full bg-brand-600 px-3 py-1 text-[11px] font-semibold text-white">{{ __('New', 'sage') }}</a>
      @else
        <span class="absolute right-3 top-3 z-10 rounded-full bg-brand-600 px-3 py-1 text-[11px] font-semibold text-white">{{ __('New', 'sage') }}<span class="sr-only">: {{ $product->get_name() }}</span></span>
      @endif
    @endif

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

  <div class="flex flex-col gap-1.5">
    @if (wc_review_ratings_enabled() && $product->get_rating_count() > 0)
      <div class="text-xs text-amber-500">
        {!! wc_get_rating_html($product->get_average_rating()) !!}
      </div>
    @endif

    <h2 class="font-display text-lg font-semibold leading-snug tracking-[-0.02em]">
      <a href="{{ get_permalink($productId) }}" class="text-[color:var(--pkde-card-content)] transition hover:text-[color:var(--pkde-card-accent)]">
        {{ $product->get_name() }}
      </a>
    </h2>
    @php(do_action('woocommerce_shop_loop_item_title'))

    <div class="text-sm text-[color:var(--pkde-card-content)] opacity-75">
      {!! $product->get_price_html() !!}
    </div>
    @php(do_action('woocommerce_after_shop_loop_item_title'))

    <div class="flex [&_a]:inline-flex [&_a]:rounded-full [&_a]:bg-neutral-950 [&_a]:px-4 [&_a]:py-2 [&_a]:text-xs [&_a]:font-semibold [&_a]:text-white [&_a]:transition [&_a:hover]:bg-brand-700">
      @php(do_action('woocommerce_after_shop_loop_item'))
    </div>
  </div>
</li>
