@php
  $categories = get_the_category();
  $primaryCategory = $categories[0] ?? null;
  // $cardStyle only ever arrives from partials/blocks/post-grid.blade.php
  // (a themed loop block's own `cardStyle` attribute/render.php) — every
  // other consumer of this partial (the blog archive, front page, a plain
  // page's the_content()) doesn't pass one, so 'white' — no marker class,
  // the plain card look this always had — is the correct default there.
  // See app.css's .pkde-section :where(article, ...) rule and
  // docs/planning/themed-cards-reference.md for what the marker class does.
  $cardStyle ??= 'white';
@endphp

<article @php(post_class(trim('group relative flex flex-col ' . ($cardStyle !== 'white' ? "pkde-card-{$cardStyle}" : ''))))>
  @if ($primaryCategory)
    <a href="{{ get_term_link($primaryCategory) }}" class="absolute left-3 top-3 z-10 rounded-full bg-white/90 px-3 py-1 text-[11px] font-medium text-neutral-700 backdrop-blur transition hover:bg-white hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-600">
      {{ $primaryCategory->name }}
    </a>
  @endif

  <div class="absolute right-3 top-3 z-10 flex items-center gap-2">
    @if (is_sticky(get_the_ID()))
      <span
        class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-700 text-white"
        role="img"
        title="{{ __('Featured post', 'sage') }}"
        aria-label="{{ __('Featured post', 'sage') }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
          <path d="M10 1.5l2.59 5.25 5.79.84-4.19 4.09.99 5.77L10 14.77l-5.18 2.68.99-5.77L1.62 7.6l5.79-.84L10 1.5z" />
        </svg>
      </span>
    @endif

    @if (\App\RecentContent::isNew(get_the_ID()))
      <span class="rounded-full bg-brand-600 px-3 py-1 text-[11px] font-semibold text-brand-600-fg">
        {{ __('New', 'sage') }}
      </span>
    @endif
  </div>

  {{-- The picture link repeats the title link below it: hide it from assistive tech and the tab order so there is one named link per card (axe link-name). --}}
  <a href="{{ get_permalink() }}" class="relative block overflow-hidden rounded-2xl bg-neutral-100" aria-hidden="true" tabindex="-1">
    @if (has_post_thumbnail())
      <div class="aspect-[4/3] overflow-hidden bg-neutral-100">
        {!! get_the_post_thumbnail(null, 'medium_large', ['class' => 'h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.03]']) !!}
      </div>
    @else
      <x-pkde-kit::placeholder-image ratio="aspect-[4/3]" label="{{ __('Featured image', 'sage') }}" />
    @endif
  </a>

  <div class="flex flex-1 flex-col gap-3 px-1 pt-5">
    <header>
      <h2 class="entry-title font-display text-xl font-semibold leading-snug tracking-[-0.02em]">
        <a href="{{ get_permalink() }}" class="text-[color:var(--pkde-card-content)] transition hover:text-[color:var(--pkde-card-accent)]">
          {!! $title !!}
        </a>
      </h2>

      @include('pkde-kit::partials.entry-meta')
    </header>

    <div class="entry-summary text-sm text-[color:var(--pkde-card-content)] opacity-75">
      @php(the_excerpt())
    </div>

    <a href="{{ get_permalink() }}" class="mt-auto inline-flex items-center gap-2 pt-2 text-sm font-semibold text-[color:var(--pkde-card-content)] transition hover:text-[color:var(--pkde-card-accent)]">
      {{ __('Read more', 'sage') }} <span aria-hidden="true">&rarr;</span>
    </a>
  </div>
</article>
