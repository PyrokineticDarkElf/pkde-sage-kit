{{--
  Shared by both categories and tags — pass ['taxonomy' => 'category'|'post_tag', 'heading' => ...].
--}}
@php($terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]))

@if (! is_wp_error($terms) && count($terms))
  <div>
    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ $heading }}</p>
    <ul class="mt-3 flex flex-wrap gap-x-3 gap-y-3 text-sm">
      @foreach ($terms as $term)
        <li class="m-0 list-none">
          <a
            href="{{ get_term_link($term) }}"
            class="inline-flex rounded-full border border-neutral-200 px-3 py-1 text-neutral-600 transition hover:border-neutral-900 hover:text-neutral-900"
          >
            {{ $term->name }} <span class="text-neutral-400">({{ $term->count }})</span>
          </a>
        </li>
      @endforeach
    </ul>
  </div>
@endif
