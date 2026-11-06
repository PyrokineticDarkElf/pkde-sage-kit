{{--
  A page: the shared page header (title and breadcrumbs), then its content. The front page is not headed by this view (it carries its own hero).
  A site decides per page with `pkde_kit/page_header` (bool $show, WP_Post $page): return false for a page whose content brings its own title, such
  as one built from Section blocks. The kit supplies this view when the theme has no page.blade.php (docs/view-folders.md).
--}}
@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @if (apply_filters('pkde_kit/page_header', ! is_front_page(), get_post()))
      @include('pkde-kit::partials.page-header')
    @endif
    @includeFirst(['pkde-kit::partials.content-page', 'pkde-kit::partials.content'])
  @endwhile
@endsection
