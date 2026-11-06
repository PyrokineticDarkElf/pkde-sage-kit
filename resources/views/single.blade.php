{{--
  A single post (and any post type without its own single-{type} view): the kit's blog single, partials/content-single (docs/blog-single.md). The kit
  supplies this view when the theme has no single.blade.php; a post type can have its own partial, partials/content-single-{type}.
--}}
@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @includeFirst(['partials.content-single-' . get_post_type(), 'pkde-kit::partials.content-single'])
  @endwhile
@endsection
