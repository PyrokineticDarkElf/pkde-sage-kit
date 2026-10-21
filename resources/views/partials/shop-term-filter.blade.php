{{-- GET-based multi-select taxonomy filter. Each applied selection is retained
     in the URL, so combinations are shareable and work without JavaScript. --}}
@php($terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]))
@php($queryKey = \App\ShopFilters::queryKey($taxonomy))
@php($selected = \App\ShopFilters::activeTerms($taxonomy))
@php($counts = \App\ShopFilters::termCounts($taxonomy))

{{-- Chosen options come first. A group none of whose options can be reached from here (and none selected) renders nothing, so
     the sidebar has no empty filter cards. --}}
@php(is_array($terms) && usort($terms, fn ($a, $b) => (int) in_array($b->slug, $selected, true) <=> (int) in_array($a->slug, $selected, true)))

@if (! is_wp_error($terms) && count($terms) && (array_sum($counts) > 0 || $selected))
  <fieldset class="space-y-3">
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ $heading }}</p>
    <ul class="grid gap-2 text-sm">
      @foreach ($terms as $term)
        @php($isCurrent = in_array($term->slug, $selected, true))
        @php($count = $counts[$term->term_id] ?? 0)
        <li>
          <label class="group flex cursor-pointer items-center gap-2.5 transition hover:text-neutral-950 {{ $count === 0 && ! $isCurrent ? 'text-neutral-500' : 'text-neutral-700' }}">
            <input type="checkbox" name="{{ $queryKey }}[]" value="{{ $term->slug }}" @checked($isCurrent) class="h-4 w-4 shrink-0 rounded border-neutral-300 text-neutral-950 focus:ring-neutral-950">
            <span>{{ $term->name }}</span>
            <span class="text-xs text-neutral-500">{{ $count }}</span>
          </label>
        </li>
      @endforeach
    </ul>
  </fieldset>
@endif
