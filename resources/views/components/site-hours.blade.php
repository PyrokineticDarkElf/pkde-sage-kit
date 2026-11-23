{{--
  The opening hours of Site details: consecutive days with the same hours are one row ("Monday to Friday 09:00 to 17:00"), then the exceptions still to come. Prints nothing when no
  day has hours. <x-site-hours /> or <x-site-hours :exceptions="false" />.
--}}
@props(['exceptions' => true])
@php($hours = (array) \App\site_detail('hours', []))
@if (\App\site_has_hours($hours))
  <div {{ $attributes->merge(['class' => 'pkde-site-hours']) }}>
    <dl class="pkde-site-hours__rows">
      @foreach (\App\site_hours_summary($hours) as $row)
        <div><dt>{{ $row['days'] }}</dt><dd>{{ $row['time'] }}</dd></div>
      @endforeach
    </dl>
    @if ($exceptions && ($coming = \App\site_hours_exceptions($hours, wp_date('Y-m-d'))))
      <ul class="pkde-site-hours__exceptions">
        @foreach ($coming as $x)
          <li><time datetime="{{ $x['date'] }}">{{ wp_date(get_option('date_format'), strtotime($x['date'] . ' 12:00:00')) }}</time>@if ($x['label'] !== '') ({{ $x['label'] }})@endif: {{ $x['time'] }}</li>
        @endforeach
      </ul>
    @endif
  </div>
@endif
