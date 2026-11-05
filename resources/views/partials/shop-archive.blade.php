{{--
  The standard product archive (categories, tags, brands, Sale/New views and product search): the title as H1, the notices, the term's own
  description in a closed "read more" toggle, then the filters and the products (partials/shop-archive-body). Layout and look are
  resources/css/shop-pages.css (the .shop-archive family); a site changes them with the --pkde-shop-* variables, not by overriding this file.

  Hooks (all optional, a site adds them in its own code):
    pkde_kit/shop_term_toggle   (bool $show, ?WP_Term $term)  filter  false hides the description toggle; default true.
    pkde_kit/shop_filters_title (string $title)               filter  the heading over the filters; '' removes it.
    pkde_kit/shop_before_results, pkde_kit/shop_after_results action  print inside the results column, before and after the toolbar and products.
--}}
@php
  $viewTitle = ['sale' => __('Sale', 'sage'), 'new' => __('New', 'sage')][sanitize_key(is_string($_GET['sage_view'] ?? null) ? $_GET['sage_view'] : '')] ?? null;
  $term = function_exists('is_product_taxonomy') && is_product_taxonomy() ? get_queried_object() : null;
  $term = $term instanceof \WP_Term ? $term : null;
  $showToggle = (bool) apply_filters('pkde_kit/shop_term_toggle', true, $term);
  // The toggle is printed here, above the layout; the copy WooCommerce would print inside the product list is removed so it shows once.
  remove_action('woocommerce_archive_description', 'App\print_term_toggle', 10);
  $termToggle = $showToggle ? \App\term_toggle($term, '<h2>' . esc_html(sprintf(__('Read more about %s', 'sage'), $term?->name ?? '')) . '</h2>') : '';
@endphp
@include('pkde-kit::partials.page-header', $viewTitle ? ['title' => $viewTitle] : [])

<div class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'shop-archive') }}">
  @include('pkde-kit::partials.woocommerce-notices')
</div>

@if ($termToggle !== '')
  <div class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'shop-archive') }}">
    {!! $termToggle !!}
  </div>
@endif

@include('pkde-kit::partials.shop-archive-body', ['nested' => false])
