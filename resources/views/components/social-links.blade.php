{{--
  The social profiles of Site details as round icon links: <x-social-links /> (the kit partial pkde-kit::partials.social-links; pass class="..." to change the ring and the colour).
--}}
@props(['class' => 'flex flex-wrap gap-3 pt-1'])
@include('pkde-kit::partials.social-links', ['class' => $class])
