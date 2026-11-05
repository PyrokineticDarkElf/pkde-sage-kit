{{--
  A product tag archive (WordPress: taxonomy-product_tag.php). It lists the products of one tag in the shop's standard layout (filters, sorting, cards);
  WooCommerce prints the tag's own description inside the product list. This file exists so a site changes the tag archive WITHOUT touching the
  shop, the other product taxonomies or the blog: add resources/views/taxonomy-product_tag.blade.php (flat, or taxonomy-product_tag-{slug}.blade.php)
  and it wins over this one (the kit supplies this view only when the theme has no file of that name).
--}}
@extends('layouts.app')

@section('content')
  @include('pkde-kit::partials.shop-archive')
@endsection
