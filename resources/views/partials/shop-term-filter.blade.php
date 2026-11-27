{{-- GET-based multi-select taxonomy filter. Each applied selection is retained
     in the URL, so combinations are shareable and work without JavaScript.
     $group is the declared group (App\ShopFilters::groups()): its order, limit and hierarchy. --}}
@php
  $group = $group ?? \App\ShopFilters::group($taxonomy) ?? ['order' => 'name', 'limit' => null, 'hierarchical' => false];
  $termArgs = ['taxonomy' => $taxonomy, 'hide_empty' => true];
  if ($group['order'] === 'menu_order') {
      $termArgs['orderby'] = 'menu_order';
  }
  $terms = get_terms($termArgs);
  $queryKey = \App\ShopFilters::queryKey($taxonomy);
  $selected = \App\ShopFilters::activeTerms($taxonomy);
  $counts = \App\ShopFilters::termCounts($taxonomy);
  $hideEmpty = (bool) \App\ShopFilters::config()['hide_empty_terms'];
@endphp

@php
  $terms = is_array($terms) ? $terms : [];
  // hide_empty_terms: an option nobody can reach from here goes (a chosen one stays so it can be unticked)
  if ($hideEmpty) {
      $terms = array_values(array_filter($terms, fn ($t) => ($counts[$t->term_id] ?? 0) > 0 || in_array($t->slug, $selected, true)));
  }
  if ($group['order'] === 'count') {
      usort($terms, fn ($a, $b) => ($counts[$b->term_id] ?? 0) <=> ($counts[$a->term_id] ?? 0));
  }
  // Chosen options come first (today's behaviour); a hierarchical group lists each child under its parent instead, the chosen ones still marked.
  $depth = [];
  if ($group['hierarchical'] && is_taxonomy_hierarchical($taxonomy)) {
      $byParent = [];
      foreach ($terms as $t) {
          $byParent[(int) $t->parent][] = $t;
      }
      $ordered = [];
      $walk = function (int $parent, int $level) use (&$walk, &$ordered, &$depth, $byParent) {
          foreach ($byParent[$parent] ?? [] as $t) {
              $ordered[] = $t;
              $depth[$t->term_id] = $level;
              $walk((int) $t->term_id, $level + 1);
          }
      };
      $ids = array_map(fn ($t) => (int) $t->term_id, $terms);
      foreach ($terms as $t) {
          if ((int) $t->parent === 0 || ! in_array((int) $t->parent, $ids, true)) {
              $ordered[] = $t;
              $depth[$t->term_id] = 0;
              $walk((int) $t->term_id, 1);
          }
      }
      $terms = $ordered;
  } else {
      usort($terms, fn ($a, $b) => (int) in_array($b->slug, $selected, true) <=> (int) in_array($a->slug, $selected, true));
  }
  if ($group['limit']) {
      $kept = array_slice($terms, 0, (int) $group['limit']);
      foreach (array_slice($terms, (int) $group['limit']) as $t) {
          if (in_array($t->slug, $selected, true)) {
              $kept[] = $t;
          }
      }
      $terms = $kept;
  }
@endphp

{{-- A group none of whose options can be reached from here (and none selected) renders nothing, so the sidebar has no empty filter cards. --}}
@if (count($terms) && (array_sum($counts) > 0 || $selected))
  <fieldset class="space-y-3">
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-600">{{ $heading }}</p>
    <ul class="grid gap-2 text-sm">
      @foreach ($terms as $term)
        @php($isCurrent = in_array($term->slug, $selected, true))
        @php($count = $counts[$term->term_id] ?? 0)
        <li @if (($depth[$term->term_id] ?? 0) > 0) style="padding-inline-start: {{ ($depth[$term->term_id] ?? 0) * 1.25 }}rem" @endif>
          <label class="group flex cursor-pointer items-center gap-2.5 transition hover:text-neutral-950 {{ $count === 0 && ! $isCurrent ? 'text-neutral-600' : 'text-neutral-700' }}">
            <input type="checkbox" name="{{ $queryKey }}[]" value="{{ $term->slug }}" @checked($isCurrent) class="h-4 w-4 shrink-0 rounded-sm border-neutral-300 text-neutral-950 focus:ring-neutral-950">
            <span>{{ $term->name }}</span>
            <span class="text-xs text-neutral-600">{{ $count }}</span>
          </label>
        </li>
      @endforeach
    </ul>
  </fieldset>
@endif
