{{--
  A custom, GET-based faceted filter form — not WooCommerce's bundled
  WC_Widget_* classes. Each taxonomy (category/tag/brand/every global
  attribute, from \App\ShopFilters::taxonomies()) renders as a checkbox
  multi-select via partials/shop-term-filter.blade.php, plus a price
  min/max pair, all submitted as query args and restored into the form via
  wc_query_string_form_fields() — so a filtered URL is shareable and works
  with JS disabled. Reload-based (a real GET request), not live AJAX, and sent once with the "Apply filters" button (no request per tick).
  Shared by the shop page and every product taxonomy archive (category,
  tag, brand) so the filter set lives in one place.

  Per-term counts (\App\ShopFilters::termCounts()) are faceted, not
  sitewide: a category's count reflects the currently-active tag/brand/
  attribute selections (and vice versa), so it always shows what a visitor
  can actually reach from here, not how many products exist in total.

  Collapsed by default below lg (a "Filters" toggle button expands it) so it
  doesn't push the product grid below the fold on mobile; always open above
  lg. app/js/app.js's [data-filter-toggle] handler does the actual toggling
  — a no-op with no JS at all beyond the button just not doing anything.
--}}
{{-- Filters applied now, each a chip that removes just that choice, plus "Clear all". The form posts back to this same archive
     (the category or brand being viewed, else the shop), so a filter never jumps to another page. --}}
@php($filterAction = ($term = get_queried_object()) instanceof \WP_Term ? get_term_link($term) : wc_get_page_permalink('shop'))
@php($chips = \App\ShopFilters::activeChips($filterAction))
@if ($chips)
  <div class="mb-4 rounded-2xl border border-neutral-200 p-4">
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ __('Active filters', 'sage') }}</p>
    <ul class="mt-3 flex flex-wrap gap-2">
      @foreach ($chips as $chip)
        <li class="list-none">
          <a href="{{ $chip['url'] }}" aria-label="{{ sprintf(__('Remove filter %s', 'sage'), html_entity_decode(wp_strip_all_tags($chip['label']), ENT_QUOTES | ENT_HTML5, 'UTF-8')) }}" class="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-800 transition hover:bg-neutral-200">
            {!! $chip['label'] !!} <span aria-hidden="true">&times;</span>
          </a>
        </li>
      @endforeach
    </ul>
    <a href="{{ $filterAction }}" class="mt-3 inline-flex text-xs font-medium text-neutral-500 transition hover:text-neutral-950">{{ __('Clear all filters', 'sage') }}</a>
  </div>
@endif
<button
  type="button"
  data-filter-toggle
  aria-expanded="false"
  aria-controls="shop-filters"
  class="mb-4 flex w-full items-center justify-between rounded-2xl border border-neutral-200 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-neutral-900 lg:hidden"
>
  {{ __('Filters', 'sage') }}
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
  </svg>
</button>

<aside
  id="shop-filters"
  class="hidden space-y-8 lg:block [&_ul]:mt-3 [&_ul]:text-sm [&_li]:list-none [&_a]:text-neutral-600 [&_a:hover]:text-neutral-900 [&_.count]:ml-1 [&_.count]:text-neutral-400"
>
  @php($filterTaxonomies = \App\ShopFilters::taxonomies())
  <form method="get" action="{{ $filterAction }}" class="space-y-4" data-shop-filters>
    {!! wc_query_string_form_fields(null, array_merge(array_map(fn ($taxonomy) => \App\ShopFilters::queryKey($taxonomy), array_keys($filterTaxonomies)), ['min_price', 'max_price', 'paged']), '', true) !!}

    <div class="rounded-2xl border border-neutral-200 p-5">
      <fieldset class="grid gap-3">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ __('Price', 'sage') }}</p>
        <div class="grid grid-cols-2 gap-2">
          <label class="grid gap-1 text-xs text-neutral-500">{{ __('Min', 'sage') }}<input type="number" name="min_price" value="{{ esc_attr(is_scalar($_GET['min_price'] ?? null) ? (string) $_GET['min_price'] : '') }}" min="0" step="0.01" placeholder="0" data-price-input class="w-full rounded-xl border border-neutral-300 px-3 py-2 text-sm text-neutral-900"></label>
          <label class="grid gap-1 text-xs text-neutral-500">{{ __('Max', 'sage') }}<input type="number" name="max_price" value="{{ esc_attr(is_scalar($_GET['max_price'] ?? null) ? (string) $_GET['max_price'] : '') }}" min="0" step="0.01" placeholder="Any" data-price-input class="w-full rounded-xl border border-neutral-300 px-3 py-2 text-sm text-neutral-900"></label>
        </div>
      </fieldset>
    </div>

    @foreach ($filterTaxonomies as $taxonomy => $heading)
      @php($group = trim(view('pkde-kit::partials.shop-term-filter', ['taxonomy' => $taxonomy, 'heading' => $heading])->render()))
      @if ($group !== '')
        <div class="rounded-2xl border border-neutral-200 p-5">
          {!! $group !!}
        </div>
      @endif
    @endforeach

    {{-- Nothing is sent until "Apply filters" (WP-89): a tick only changes the form. The button is a plain submit, so the form works without JavaScript; with
         it the button shows how many choices differ from what is applied now. "Reset" is a link to the archive without the form's choices when some are
         applied, else a native reset button (back to the rendered, unfiltered state, no request). --}}
    @php($filterQuery = \App\ShopFilters::currentQuery())
    <div class="pkde-filter-actions" data-shop-actions>
      <button type="submit" class="pkde-filter-apply" data-shop-apply data-pending="0" data-label="{{ esc_attr__('Apply filters', 'sage') }}" data-pending-label="{{ esc_attr__('Apply filters (%d)', 'sage') }}">
        <span data-shop-apply-label>{{ __('Apply filters', 'sage') }}</span>
      </button>
      @if (\App\ShopFilters::hasAppliedFilters($filterQuery))
        <a href="{{ \App\ShopFilters::resetUrl($filterAction, $filterQuery) }}" class="pkde-filter-reset" data-shop-reset aria-label="{{ esc_attr__('Reset filters', 'sage') }}">{{ __('Reset', 'sage') }}</a>
      @else
        <button type="reset" class="pkde-filter-reset" data-shop-reset aria-label="{{ esc_attr__('Reset filters', 'sage') }}">{{ __('Reset', 'sage') }}</button>
      @endif
      <p class="sr-only" role="status" aria-live="polite" data-shop-status data-status-none="" data-status-one="{{ esc_attr__('1 filter change not applied yet.', 'sage') }}" data-status-many="{{ esc_attr__('%d filter changes not applied yet.', 'sage') }}"></p>
    </div>
  </form>

  {{-- Recently viewed (opt-in: pkde_kit/recently_viewed): an empty placeholder the script fills, so the page stays the same for every visitor. --}}
  {!! \App\recently_viewed_box() !!}
</aside>
