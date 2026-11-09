{{--
  The 404 page: the breadcrumb, a large 404, the message, the search form and a link home. The kit supplies this view when the theme has no 404.blade.php
  (docs/view-folders.md); the colours and fonts are the site's tokens.
--}}
@extends('layouts.app')

@section('content')
  <div class="mx-auto max-w-content px-[var(--page-gutter,1.5rem)] pt-10">
    @include('pkde-kit::partials.breadcrumbs')
  </div>

  <div class="mx-auto max-w-content px-[var(--page-gutter,1.5rem)] py-20 lg:py-28">
  <div class="mx-auto flex max-w-2xl flex-col items-center text-center">
    {{-- The big numeral is decoration: drawn by CSS (::before), so it is not text a contrast check or a screen reader meets. --}}
    <p aria-hidden="true" class="pkde-404-numeral font-display font-semibold tracking-[-0.07em] text-neutral-200"></p>
    <h1 class="mt-5 font-display text-3xl font-semibold tracking-[-0.03em] text-neutral-950">
      {{ __('Page not found', 'sage') }}
    </h1>
    <p class="mt-4 leading-7 text-neutral-600">
      {{ __("Sorry, but the page you are trying to view does not exist.", 'sage') }}
    </p>

    <div class="mt-8 w-full max-w-md">
      {!! get_search_form(false) !!}
    </div>

    <a href="{{ home_url('/') }}" class="mt-8 inline-flex rounded-full bg-neutral-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 hover:text-brand-700-fg">
      {{ __('Back to homepage', 'sage') }}
    </a>
  </div>
  </div>
@endsection
