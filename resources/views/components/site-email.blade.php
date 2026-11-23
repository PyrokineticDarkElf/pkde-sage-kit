{{--
  An email address of Site details as a mailto: link, written as character references (WordPress's antispambot) so a simple scraper does not read it: <x-site-email />,
  <x-site-email :index="1" label />. Prints nothing when there is none.
--}}
@props(['index' => 0, 'label' => false])
@php($email = \App\site_emails()[$index] ?? null)
@if ($email && ($email['address'] ?? '') !== '')
  <a href="mailto:{!! antispambot($email['address']) !!}" {{ $attributes->merge(['class' => 'pkde-site-email']) }}>@if ($label && ($email['label'] ?? '') !== '')<span class="pkde-site-label">{{ $email['label'] }}:</span> @endif{!! antispambot($email['address']) !!}</a>
@endif
