{{--
  A phone number of Site details as a tel: link: <x-site-phone /> (the first), <x-site-phone :index="1" label /> (the second, with its label). Prints nothing when there is none.
--}}
@props(['index' => 0, 'label' => false])
@php($phone = \App\site_phones()[$index] ?? null)
@if ($phone)
  <a href="{{ \App\site_tel_href($phone['number']) }}" {{ $attributes->merge(['class' => 'pkde-site-phone']) }}>@if ($label && ($phone['label'] ?? '') !== '')<span class="pkde-site-label">{{ $phone['label'] }}:</span> @endif{{ ($phone['display'] ?? '') !== '' ? $phone['display'] : $phone['number'] }}</a>
@endif
