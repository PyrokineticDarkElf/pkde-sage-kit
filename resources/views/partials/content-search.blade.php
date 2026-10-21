<article @php(post_class('border-b border-neutral-200 py-6'))>
  <header>
    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-400">{{ get_post_type_object(get_post_type())->labels->singular_name ?? '' }}</p>
    <h2 class="entry-title font-display text-xl font-bold leading-snug">
      <a href="{{ get_permalink() }}" class="text-neutral-900 hover:text-neutral-600">
        {!! $title !!}
      </a>
    </h2>

    @includeWhen(get_post_type() === 'post', 'pkde-kit::partials.entry-meta')
  </header>

  <div class="entry-summary mt-2 text-sm text-neutral-600">
    @php(the_excerpt())
  </div>
</article>
