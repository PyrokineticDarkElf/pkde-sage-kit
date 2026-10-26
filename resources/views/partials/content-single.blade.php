<article @php(post_class('h-entry'))>
  {{--
    Capturing the filtered content as a string (rather than the usual
    the_content() echo) lets TableOfContents inject id="" anchors onto every
    heading and hand back the matching list for the sidebar ToC below.
  --}}
  @php($toc = \App\TableOfContents::fromContent(apply_filters('the_content', get_the_content())))

  <div class="{{ \App\container_class('mx-auto max-w-content px-6 py-12 lg:grid lg:grid-cols-12 lg:gap-12 lg:py-16', 'content-single') }}">
    <div class="lg:col-span-8">
      <header>
        @include('pkde-kit::partials.breadcrumbs')

        @if (has_post_thumbnail())
          <div class="mt-6 aspect-[16/9] overflow-hidden rounded-2xl bg-neutral-100">
            {!! get_the_post_thumbnail(null, 'large', ['class' => 'h-full w-full object-cover']) !!}
          </div>
        @else
          <x-pkde-kit::placeholder-image ratio="aspect-[16/9]" label="{{ __('Featured image', 'sage') }}" class="mt-6 w-full" />
        @endif

        <h1 class="p-name mt-8 font-display text-4xl font-semibold leading-[1.08] tracking-[-0.04em] text-neutral-950 sm:text-5xl">
          {!! $title !!}
        </h1>

        <div class="mt-5">
          @include('pkde-kit::partials.entry-meta', ['showReadingTime' => true])
        </div>
      </header>

      <div class="e-content prose-content mt-12">
        {!! $toc['content'] !!}
      </div>

      @if ($pagination())
        <footer class="mt-10">
          <nav class="page-nav" aria-label="Page">
            {!! $pagination !!}
          </nav>
        </footer>
      @endif

      @include('pkde-kit::partials.post-navigation')
    </div>

    <div class="mt-12 lg:col-span-4 lg:mt-0">
      @include('pkde-kit::partials.sidebar-single', ['headings' => $toc['headings']])
    </div>
  </div>
</article>
