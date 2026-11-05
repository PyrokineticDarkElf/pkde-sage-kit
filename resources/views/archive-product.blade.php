{{--
  The shop and the fallback for any product archive without its own view: product categories, tags and brands have taxonomy-product_cat,
  _tag and _brand next to this file (app/woocommerce/template-resolution.php routes a taxonomy here when none exists). The kit supplies this
  view when the theme has no archive-product.blade.php. The layout is partials/shop-page, shop-archive and shop-archive-body; change its look
  with the --pkde-shop-* variables and its small choices with the pkde_kit/shop_* filters and actions (docs/woocommerce-templates.md in the
  kit), not by copying the partials or this view.
--}}
@extends('layouts.app')

@section('content')
  @include('pkde-kit::partials.shop-page')
@endsection
