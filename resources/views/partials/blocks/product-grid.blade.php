{{--
  Shared by recent-products, best-selling-products, and featured-products —
  takes an already-built secondary WP_Query and renders it through
  WooCommerce's own loop functions (woocommerce_product_loop_start/end +
  wc_get_template_part), the same mechanism WC's own [products] shortcode
  uses internally. That, plus the theme-root content-product.php bridge
  (see app/woocommerce.php's comments), gets our real
  woocommerce/content-product.blade.php card for free — but the
  `.woocommerce ul.products` grid CSS is scoped to a `.woocommerce` ancestor
  (see archive-product.blade.php, which adds that class by hand around
  woocommerce_content() for exactly this reason), which
  woocommerce_product_loop_start() alone never adds. Without it here too,
  every block using this partial renders WooCommerce's bare, unstyled
  `<ul class="products">` — no grid at all.

  `loop_shop_columns` (app/woocommerce/display.php) is hardcoded to 3
  site-wide — correct for the Shop archive, which shares its row with a
  filter sidebar, but these blocks have no sidebar and default their own
  `count` attribute to 4 (see each block's block.json). Left alone, that's 4
  items forced into a 3-wide grid — an orphaned 4th card alone on its own
  row. `.product-grid--4up` overrides the column count at the same
  breakpoints specifically for this shared partial, without touching the
  Shop archive's own (correctly different) column count.

  Props: $query (WP_Query), $emptyMessage (string, optional),
         $cardStyle (string, optional — 'white'/'tinted'/'bold', see
         woocommerce/content-product.blade.php's own comment)
--}}
<div class="woocommerce product-grid--4up">
  @if ($query->have_posts())
    {{--
      wc_get_template_part() (unlike a plain Blade @include) has no `$args`
      parameter in this WooCommerce version to pass $cardStyle through — a
      $GLOBALS bridge is the plain, reliable way across that gap, since
      `global`/$GLOBALS work identically regardless of how WC's own template
      loader and Acorn's Blade compiler pass (or don't pass) the rest of
      each other's variables. Read back in content-product.blade.php; reset
      to null after the loop so it can't leak into an unrelated later render
      on the same page.
    --}}
    @php($GLOBALS['pkde_product_card_style'] = $cardStyle ?? 'white')
    @php(woocommerce_product_loop_start())
    @while ($query->have_posts()) @php($query->the_post())
      @php(wc_get_template_part('content', 'product'))
    @endwhile
    @php(woocommerce_product_loop_end())
    @php(wp_reset_postdata())
    {{-- A plain reassignment, not unset() — Blade's @php(...) single-line
         shorthand doesn't compile unset() cleanly (a real, confirmed parse
         error, not a style preference). null reads back identically to
         "not set" via content-product.blade.php's own `?? 'white'`. --}}
    @php($GLOBALS['pkde_product_card_style'] = null)
  @else
    <p class="text-center text-neutral-600">{{ $emptyMessage ?? __('Nothing to show yet.', 'sage') }}</p>
  @endif
</div>
