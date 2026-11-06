{{--
  The product page. The kit supplies it when the theme has no single-product.blade.php.
  woocommerce_content() renders WooCommerce's real single-product template
  (gallery, price, add-to-cart, tabs) including its own product title — no
  separate page-header here, same reasoning as single.blade.php not using one.
--}}
@extends('layouts.app')

@section('content')
  <div class="mx-auto max-w-content px-[var(--page-gutter,1.5rem)] pt-8">
    @include('pkde-kit::partials.breadcrumbs')
  </div>

  <div class="woocommerce mx-auto max-w-content px-[var(--page-gutter,1.5rem)] pt-8">
    @include('pkde-kit::partials.woocommerce-notices')
  </div>

  <div class="woocommerce woocommerce-content mx-auto max-w-content px-[var(--page-gutter,1.5rem)] pb-16">
    @php(woocommerce_content())
  </div>
@endsection
