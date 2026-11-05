{{--
  Serves the dedicated "posts page" (Settings > Reading > Posts page) when a
  static front page is also set — see front-page.blade.php for the home page
  design itself. Falls back to index.blade.php otherwise.
--}}
@extends('layouts.app')

@section('content')
  @include('pkde-kit::partials.page-header')
  @include('pkde-kit::partials.post-grid')
@endsection
