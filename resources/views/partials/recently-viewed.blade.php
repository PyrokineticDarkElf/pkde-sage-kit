{{--
  Recently viewed products (app/woocommerce/recently-viewed.php, resources/js/recently-viewed.js). An empty, hidden placeholder, the same for every
  visitor; the script fills it from the browser's own list (and the account's, when logged in) and shows it only when there is something to show.
  $url / $syncUrl: the kit's WooCommerce endpoints; $count: products per batch; $max: ids kept.
--}}
<section class="pkde-recent" data-pkde-recent data-url="{{ $url }}" data-sync-url="{{ $syncUrl }}" data-count="{{ $count }}" data-max="{{ $max }}" aria-labelledby="pkde-recent-title" hidden>
  <h2 id="pkde-recent-title" class="pkde-recent__title">{{ __('Recently viewed', 'sage') }}</h2>
  <ul class="pkde-recent__list" data-pkde-recent-list></ul>
  <p class="pkde-recent__more" data-pkde-recent-more hidden>
    <button type="button" class="pkde-recent__button" data-pkde-recent-button>{{ __('Show more', 'sage') }}</button>
  </p>
  <p class="sr-only" role="status" aria-live="polite" data-pkde-recent-status></p>
</section>
