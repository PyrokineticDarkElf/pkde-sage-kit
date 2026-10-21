<div class="{{ \App\container_class('mx-auto max-w-7xl px-6 py-20 lg:py-24', 'post-grid') }}">
  @if (! have_posts())
    <x-pkde-kit::alert type="warning">
      {!! __('Sorry, no results were found.', 'sage') !!}
    </x-pkde-kit::alert>

    {!! get_search_form(false) !!}
  @else
    <div class="grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
      @while(have_posts()) @php(the_post())
        @includeFirst(['partials.content-' . get_post_type(), 'pkde-kit::partials.content'])
      @endwhile
    </div>

    <nav class="page-nav mt-16 flex items-center justify-between border-t border-neutral-200 pt-7 text-sm font-semibold [&_a]:transition [&_a:hover]:text-brand-700" aria-label="{{ __('Posts navigation', 'sage') }}">
      <div>{!! get_previous_posts_link('&larr; '.__('Newer', 'sage')) !!}</div>
      <div>{!! get_next_posts_link(__('Older', 'sage').' &rarr;') !!}</div>
    </nav>
  @endif
</div>
