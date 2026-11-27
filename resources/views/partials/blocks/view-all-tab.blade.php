{{--
  The "View all" under a loop block's grid: a pill, or (the block's wrapper has the class has-pinned-tab) a tab that hangs from the Section's bottom edge, or ($link) a plain
  text link with an arrow (CSS: resources/css/pattern-layouts.css). Props: $url (string), $label (string), $link (bool, optional).
--}}
<p class="pkde-tab-row"><a @class(['pkde-tab', 'pkde-tab--link' => $link ?? false]) href="{{ $url }}">{{ $label }} <span aria-hidden="true">{{ ($link ?? false) ? '↗' : '›' }}</span></a></p>
