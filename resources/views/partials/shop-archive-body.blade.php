{{--
  The filters and the product list. The filters are one <search> landmark named by their heading (the heading is inside it, id shop-filters-title); the
  recently viewed box, when a site turns it on, is its own <aside> after it, not part of the search. Inside a page row ($nested, the Shop products block) the row already provides the page measure and
  side gutter. The list carries no "woocommerce" class of its own, so ".woocommerce" form styling meant for other pages never reaches
  its sort select. Hooks: pkde_kit/shop_filters_title (filter), pkde_kit/shop_before_results and pkde_kit/shop_after_results (actions).
--}}
@php($filtersTitle = (string) apply_filters('pkde_kit/shop_filters_title', __('Filter your search', 'sage')))
<div @class(['shop-archive', \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'shop-archive-body') => ! ($nested ?? false)])>
  <div class="shop-archive__filters">
    <search class="pkde-shop-search" @if ($filtersTitle !== '') aria-labelledby="shop-filters-title" @else aria-label="{{ esc_attr__('Filters', 'sage') }}" @endif>
      @if ($filtersTitle !== '')
        <h2 id="shop-filters-title" class="shop-archive__filters-title">{{ $filtersTitle }}</h2>
      @endif
      @include('pkde-kit::partials.sidebar-shop')
    </search>
    @if (($recentBox = \App\recently_viewed_box()) !== '')
      {{-- opens and closes with the filters below lg, as it did inside them (the toggle script follows [data-filter-follow]) --}}
      <div id="shop-recent" class="mt-8 hidden lg:block [&_ul]:mt-3 [&_ul]:text-sm [&_li]:list-none [&_a]:text-neutral-600 [&_a:hover]:text-neutral-900" data-filter-follow>{!! $recentBox !!}</div>
    @endif
  </div>

  <div class="woocommerce-content shop-archive__results">
    @php(do_action('pkde_kit/shop_before_results'))
    @php(woocommerce_content())
    @php(do_action('pkde_kit/shop_after_results'))
  </div>
</div>
