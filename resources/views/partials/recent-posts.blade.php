{{--
  The latest posts, beside the related ones: the newest published posts other than this one (and, in the related list, not repeated).
  Count: pkde_kit/recent_posts_count (default 3). Off: pkde_kit/single_show 'recent'.
--}}
@php
  $recentCount = (int) apply_filters('pkde_kit/recent_posts_count', 3);
  $recent = $recentCount > 0 ? new WP_Query([
      'post_type' => 'post',
      'post__not_in' => array_filter(array_merge([get_the_ID()], \App\related_post_ids())),
      'posts_per_page' => $recentCount,
      'ignore_sticky_posts' => true,
      'no_found_rows' => true,
  ]) : null;
@endphp
@if ($recent && $recent->have_posts())
  <div class="post-recent">
    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('Recent posts', 'sage') }}</p>
    <div class="mt-3 space-y-4">
      @while ($recent->have_posts()) @php($recent->the_post())
        @include('pkde-kit::partials.related-post-card')
      @endwhile
    </div>
  </div>
@endif
@php(wp_reset_postdata())
