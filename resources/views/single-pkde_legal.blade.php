{{--
  A legal document (privacy, cookies, terms ...), the platform's `pkde_legal` post type (docs/legal-documents.md): the title, a line with the dates and version, the text in a
  readable column with a contents list from its h2 and h3 headings (from three of them), the breadcrumbs, and print styles. No public version history. It uses the blog
  single's type (--pkde-post-* variables), so a site's typography applies; the kit supplies this view when the theme has no single-pkde_legal.blade.php.
--}}
@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @php($post = get_post())
    @php($toc = \App\legal_toc(apply_filters('the_content', get_the_content())))
    @php($format = fn (string $ymd): string => wp_date(get_option('date_format'), strtotime($ymd . ' 12:00:00') ?: 0))
    @php($line = \App\legal_meta_parts([
      'effective' => (string) get_post_meta($post->ID, '_pkde_legal_effective', true),
      'reviewed' => (string) get_post_meta($post->ID, '_pkde_legal_reviewed', true),
      'version' => (string) get_post_meta($post->ID, '_pkde_legal_version', true),
    ], get_the_modified_date('Y-m-d', $post), $format))
    <article @php(post_class('pkde-legal'))>
      <header class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'content-single') }} post-header pkde-legal__header">
        @include('pkde-kit::partials.breadcrumbs')
        <h1>{!! get_the_title() !!}</h1>
        @if ($line !== [])
          <p class="pkde-legal__meta">{{ implode(' · ', $line) }}</p>
        @endif
      </header>

      <div @class([
        \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'content-single'),
        'post-body pkde-legal__body',
        'pkde-legal__body--no-toc' => ! $toc['show'],
      ])>
        <div class="post-body__main">
          <div class="prose-content post-body__text pkde-legal__text">
            {!! $toc['content'] !!}
          </div>
        </div>
        @if ($toc['show'])
          <div class="post-rail">
            @include('pkde-kit::partials.toc', ['headings' => $toc['headings']])
          </div>
        @endif
      </div>
    </article>
  @endwhile
@endsection
