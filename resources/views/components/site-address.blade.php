{{--
  An address of Site details, one line per part, with a "View map" link when it has one: <x-site-address /> (the first), <x-site-address :index="1" label />. Prints nothing when there is none.
--}}
@props(['index' => 0, 'label' => false, 'map' => true])
@php($place = \App\site_locations()[$index] ?? null)
@php($lines = $place ? \App\site_address_lines($place) : [])
@if ($lines !== [])
  <address {{ $attributes->merge(['class' => 'pkde-site-address not-italic']) }}>
    @if ($label && ($place['label'] ?? '') !== '')<strong class="pkde-site-label">{{ $place['label'] }}</strong><br>@endif
    @foreach ($lines as $line){{ $line }}@if (! $loop->last)<br>@endif @endforeach
    @if ($map && ($place['map_url'] ?? '') !== '')<br><a href="{{ $place['map_url'] }}" rel="noopener">{{ __('View map', 'sage') }}</a>@endif
  </address>
@endif
