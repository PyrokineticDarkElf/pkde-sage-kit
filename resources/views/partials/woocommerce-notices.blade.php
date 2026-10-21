{{-- Kept at the page level so WooCommerce feedback appears before the grid or
     product detail, rather than interrupting its content hierarchy. --}}
<div class="woocommerce-notices-wrapper">
  @php(wc_print_notices())
</div>
