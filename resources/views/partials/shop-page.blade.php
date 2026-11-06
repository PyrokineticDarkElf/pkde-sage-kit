{{--
  The body of a site's archive-product view: the shop and the fallback for every product archive without its own view. The plain shop is the
  Shop page's own content ($shopPageContent, from App\View\Composers\ProductArchive), under the page header, with the product list where its
  "Shop products" block sits (or after the content when the block is missing); every other archive uses partials/shop-archive.
  A site's archive-product.blade.php is then only: @extends('layouts.app') @section('content') @include('pkde-kit::partials.shop-page') @endsection
  Hook: pkde_kit/shop_page_header (filter, bool): false leaves the page header off the Shop page, for a Shop page whose content brings its own title.
--}}
@if ($shopPageContent !== null)
  @php
    $showHeader = (bool) apply_filters('pkde_kit/shop_page_header', true);
    $hasNotices = function_exists('wc_notice_count') && wc_notice_count() > 0;
  @endphp
  @if ($showHeader)
    @include('pkde-kit::partials.page-header')
  @endif
  <div class="page-content">
    {{-- The notices row (and its top padding) only exists where it has something to show: under the page header, or with a notice to print.
         A Shop page without the header and without notices starts at its own content, with no empty strip above it. The (empty) notices
         wrapper stays either way: WooCommerce's add-to-cart script puts its messages into it. --}}
    @if ($showHeader || $hasNotices)
      <div class="mx-auto max-w-content px-[var(--page-gutter,1.5rem)] pt-8">
        @include('pkde-kit::partials.woocommerce-notices')
      </div>
    @else
      @include('pkde-kit::partials.woocommerce-notices')
    @endif
    {!! $shopPageContent !!}
  </div>
@else
  @include('pkde-kit::partials.shop-archive')
@endif
