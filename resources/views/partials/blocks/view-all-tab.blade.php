{{--
  The "View all" tab under a loop block's grid: a pill, or (the block's wrapper has the class has-pinned-tab) a tab that hangs from the Section's bottom edge (CSS: resources/css/pattern-layouts.css).
  Props: $url (string), $label (string).
--}}
<p class="pkde-tab-row"><a class="pkde-tab" href="{{ $url }}">{{ $label }} <span aria-hidden="true">&rsaquo;</span></a></p>
