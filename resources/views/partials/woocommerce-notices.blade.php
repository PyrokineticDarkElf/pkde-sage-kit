{{-- Kept at the page level so WooCommerce feedback appears before the grid or
     product detail, rather than interrupting its content hierarchy. A page that prints a notice is marked uncacheable (App\no_page_cache(), MGR-76): notices are
     the visitor's own, so the page cache must never store them for the next visitor. --}}
<div class="woocommerce-notices-wrapper">
  @php(\App\print_notices_uncached())
</div>
