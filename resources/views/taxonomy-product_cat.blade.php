{{--
  A product category archive (WordPress: taxonomy-product_cat.php). It lists the products of one category in the shop's standard layout (filters, sorting, cards);
  WooCommerce prints the category's own description inside the product list. This file exists so a site changes the category archive WITHOUT touching the
  shop, the other product taxonomies or the blog: add resources/views/taxonomy-product_cat.blade.php (flat, or taxonomy-product_cat-{slug}.blade.php)
  and it wins over this one (the kit supplies this view only when the theme has no file of that name).
--}}
@extends('layouts.app')

@section('content')
  @include('pkde-kit::partials.shop-archive')
@endsection
