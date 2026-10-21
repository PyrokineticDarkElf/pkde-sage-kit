{{--
  Shared by the latest-posts and featured-posts blocks — takes an already-built
  secondary WP_Query (not the main query) and renders it with the same blog
  card used everywhere else (partials/content.blade.php).

  No `sm:grid-cols-2` tablet tier on purpose — both blocks default `count`
  to 3 (block.json), and a 2-column tier leaves an odd count's last card
  alone on its own row. Jumping straight from 1 to 3 columns avoids that for
  the count these blocks actually ship with. (This is a different file from
  partials/post-grid.blade.php, which the blog archive uses at 12 posts per
  page — evenly divisible either way, so it keeps its own 2-then-3
  progression; only this one needed adjusting.)

  Props: $query (WP_Query), $emptyMessage (string, optional),
         $cardStyle (string, optional — 'white'/'tinted'/'bold', see
         partials/content.blade.php's own comment)
--}}
@if ($query->have_posts())
  <div class="grid gap-x-8 gap-y-12 lg:grid-cols-3">
    @while ($query->have_posts()) @php($query->the_post())
      @include('pkde-kit::partials.content', ['cardStyle' => $cardStyle ?? 'white'])
    @endwhile
  </div>
  @php(wp_reset_postdata())
@else
  <p class="text-center text-neutral-600">{{ $emptyMessage ?? __('Nothing to show yet.', 'sage') }}</p>
@endif
