<div class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)] py-20 lg:py-24', 'post-grid') }}">
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

    @include('pkde-kit::partials.pagination')
  @endif
</div>
