{{--
  The filters and the product list. Inside a page row ($nested, the Shop products block) the row already provides the page measure and
  side gutter. The list carries no "woocommerce" class of its own, so ".woocommerce" form styling meant for other pages never reaches
  its sort select (TCS-72). Hooks: pkde_kit/shop_filters_title (filter), pkde_kit/shop_before_results and pkde_kit/shop_after_results (actions).
--}}
@php($filtersTitle = (string) apply_filters('pkde_kit/shop_filters_title', __('Filter your search', 'sage')))
<div @class(['shop-archive', \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'shop-archive-body') => ! ($nested ?? false)])>
  <div class="shop-archive__filters">
    @if ($filtersTitle !== '')
      <h3 class="shop-archive__filters-title">{{ $filtersTitle }}</h3>
    @endif
    @include('pkde-kit::partials.sidebar-shop')
  </div>

  <div class="woocommerce-content shop-archive__results">
    @php(do_action('pkde_kit/shop_before_results'))
    @php(woocommerce_content())
    @php(do_action('pkde_kit/shop_after_results'))
  </div>
</div>
