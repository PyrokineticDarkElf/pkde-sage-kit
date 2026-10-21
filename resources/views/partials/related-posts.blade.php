@php
  $relatedCategories = wp_get_post_categories(get_the_ID());
  $related = null;

  if (! empty($relatedCategories)) {
      $related = new WP_Query([
          'category__in' => $relatedCategories,
          'post__not_in' => [get_the_ID()],
          'posts_per_page' => 3,
          'ignore_sticky_posts' => true,
          'no_found_rows' => true,
      ]);
  }
@endphp

@if ($related && $related->have_posts())
  <div>
    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('Related posts', 'sage') }}</p>
    <div class="mt-3 space-y-4">
      @while ($related->have_posts()) @php($related->the_post())
        @include('pkde-kit::partials.related-post-card')
      @endwhile
    </div>
  </div>
@endif
{{-- Restores the main query's global $post — the loop above uses a secondary WP_Query. --}}
@php(wp_reset_postdata())
