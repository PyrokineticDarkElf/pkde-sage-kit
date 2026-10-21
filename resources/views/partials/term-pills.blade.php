{{-- A single item's own terms (not a sitewide list — see sidebar-terms.blade.php for that). --}}
@if (! empty($terms))
  <div class="flex flex-wrap gap-2">
    @foreach ($terms as $term)
      <a
        href="{{ get_term_link($term) }}"
        class="rounded-full border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-600 transition hover:border-neutral-900 hover:text-neutral-900"
      >
        {{ $term->name }}
      </a>
    @endforeach
  </div>
@endif
