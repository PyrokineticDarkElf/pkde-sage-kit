{{-- The filters and the product list. Inside a page ($nested, the Shop products block) the page already provides the width. --}}
<div @class(['woocommerce lg:grid lg:grid-cols-12 lg:gap-12', \App\container_class('mx-auto max-w-6xl px-6 py-16', 'shop-archive-body') => ! ($nested ?? false)])>
  <div class="lg:col-span-4">
    @include('pkde-kit::partials.sidebar-shop')
  </div>

  <div class="woocommerce-content mt-8 lg:col-span-8 lg:mt-0">
    @php(woocommerce_content())
  </div>
</div>
