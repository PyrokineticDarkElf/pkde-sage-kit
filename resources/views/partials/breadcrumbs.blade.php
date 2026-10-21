{{--
  RankMath's own output already includes a <nav aria-label="breadcrumbs"> wrapper
  (see the rank-math-breadcrumb class below) — style it directly rather than
  nesting a second <nav> landmark around it.
--}}
@php
  // Rank Math's trail when its breadcrumb module is on; otherwise WooCommerce's on shop pages ("Home / Category / ...").
  $trail = function_exists('rank_math_get_breadcrumbs') ? trim((string) rank_math_get_breadcrumbs()) : '';
  if ($trail === '' && function_exists('woocommerce_breadcrumb') && (is_woocommerce() || is_product_taxonomy())) {
    ob_start();
    woocommerce_breadcrumb(['wrap_before' => '<nav class="woocommerce-breadcrumb" aria-label="breadcrumbs">', 'wrap_after' => '</nav>', 'delimiter' => ' / ']);
    $trail = trim(ob_get_clean());
  }
@endphp
@if ($trail !== '')
  <div class="breadcrumbs text-xs text-neutral-600 [&_.rank-math-breadcrumb]:block [&_a]:hover:text-neutral-900">
    {!! $trail !!}
  </div>
@endif
