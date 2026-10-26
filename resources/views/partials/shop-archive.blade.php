{{--
  The standard product archive (categories, tags, brands, Sale/New views and product search): the title as H1, the notices, then
  the filters and the products. A category's own description is printed by WooCommerce inside the product list.
--}}
@php($viewTitle = ['sale' => __('Sale', 'sage'), 'new' => __('New', 'sage')][sanitize_key(is_string($_GET['sage_view'] ?? null) ? $_GET['sage_view'] : '')] ?? null)
@include('pkde-kit::partials.page-header', $viewTitle ? ['title' => $viewTitle] : [])

<div class="{{ \App\container_class('mx-auto max-w-content px-6 pt-8', 'shop-archive') }}">
  @include('pkde-kit::partials.woocommerce-notices')
</div>

@include('pkde-kit::partials.shop-archive-body', ['nested' => false])
