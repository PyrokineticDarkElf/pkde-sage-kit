{{--
  A category archive (WordPress: category.php). The category's name is the page title, its description (if it has one) sits under the title, then
  the posts. Add resources/views/category.blade.php (flat, or category-{slug}.blade.php) to change ONE category or all of them without touching
  the tag or the blog archive: the kit supplies this view only when the theme has no file of that name.
--}}
@extends('layouts.app')

@section('content')
  @include('pkde-kit::partials.page-header')
  @include('pkde-kit::partials.term-description')
  @include('pkde-kit::partials.post-grid')
@endsection
