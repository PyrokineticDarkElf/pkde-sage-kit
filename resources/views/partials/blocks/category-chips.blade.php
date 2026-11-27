{{--
  Product categories as a row of chips: the category names as pill links with their product counts. Props: $terms, $labels, $extra, $emptyMessage.
--}}
@if (! empty($terms))
  <ul class="pkde-chips">
    @foreach ($terms as $term)
      @php($label = trim((string) (($labels ?? [])[$term->slug] ?? '')) ?: $term->name)
      <li>
        <a class="pkde-chip" href="{{ get_term_link($term) }}">
          {{ $label }}
          @if ($term->count)
            <span class="pkde-chip__count">{{ $term->count }}</span>
          @endif
        </a>
      </li>
    @endforeach
  </ul>
@else
  <p class="text-center text-neutral-600">{{ $emptyMessage ?? __('Nothing to show yet.', 'sage') }}</p>
@endif
