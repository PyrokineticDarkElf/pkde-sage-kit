{{--
  Author info comes first, prominently — the same visual language as the
  About page's intro — with "posts by this author" as a secondary section
  below, reusing the existing post-grid partial (already correctly scoped
  to this author by WordPress's own main query, no custom WP_Query needed).
--}}
@extends('layouts.app')

@section('content')
  @php
    $authorId = get_queried_object_id();
    $authorName = get_the_author_meta('display_name', $authorId);
    $bio = get_the_author_meta('description', $authorId);
    $website = get_the_author_meta('user_url', $authorId);
  @endphp

  <section class="border-b border-neutral-200 bg-neutral-50">
    <div class="mx-auto max-w-content px-[var(--page-gutter,1.5rem)] py-16"><div class="mx-auto max-w-3xl text-center">
      @include('pkde-kit::partials.breadcrumbs')

      {!! get_avatar($authorId, 120, '', '', ['class' => 'mx-auto rounded-full']) !!}

      <h1 class="mt-6 font-display text-3xl font-bold text-neutral-900 sm:text-4xl">
        {{ $authorName }}
      </h1>

      @if ($bio)
        <p class="mt-4 text-neutral-600">{{ $bio }}</p>
      @endif

      @if ($website)
        <a href="{{ esc_url($website) }}" class="mt-4 inline-block text-sm font-semibold uppercase tracking-wide text-neutral-900 hover:text-neutral-600">
          {{ __('Visit website', 'sage') }} &rarr;
        </a>
      @endif
    </div></div>
  </section>

  <div class="mx-auto max-w-content px-[var(--page-gutter,1.5rem)] pt-16">
    <h2 class="font-display text-2xl font-bold text-neutral-900">
      {{ sprintf(__('Posts by %s', 'sage'), $authorName) }}
    </h2>
  </div>

  @include('pkde-kit::partials.post-grid')
@endsection
