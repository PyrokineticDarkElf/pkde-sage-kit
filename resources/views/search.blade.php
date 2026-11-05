{{--
  Search results: the page header, then the posts with numeric pagination
  (partials/post-grid). The kit supplies this view when the theme has none; add resources/views/archive.blade.php to change it for the site.
--}}
@extends('layouts.app')

@section('content')
  @include('pkde-kit::partials.page-header')
  @include('pkde-kit::partials.post-grid')
@endsection
