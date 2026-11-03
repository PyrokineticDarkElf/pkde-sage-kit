// The header's basket count after a change on the basket page (app/woocommerce/cart-count.php). WooCommerce's cart script refreshes only the
// basket table and totals, so on its jQuery events ask WooCommerce for the fresh cart fragments and swap the count in. An AJAX add to basket
// elsewhere needs nothing: WooCommerce's add-to-cart script swaps the fragments itself.
const $ = window.jQuery;
const params = window.wc_cart_params;

if ($ && params?.wc_ajax_url && document.querySelector('.pkde-cart-count')) {
  $(document.body).on('updated_cart_totals wc_cart_emptied', () => {
    fetch(params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'), { method: 'POST', credentials: 'same-origin' })
      .then((response) => response.json())
      .then((data) => {
        const html = data?.fragments?.['span.pkde-cart-count'];

        if (html) document.querySelectorAll('.pkde-cart-count').forEach((count) => { count.outerHTML = html; });
      })
      .catch(() => {
        // The count shows the right number again on the next page load.
      });
  });
}
