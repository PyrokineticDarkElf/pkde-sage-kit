<?php

// Relevanssi Live Ajax Search's results template (found through the relevanssi_live_search_results_template filter in app/search.php).
// The markup is the kit's Blade view; a theme's own relevanssi-live-ajax-search/search-results.php still takes precedence.
echo \view('pkde-kit::partials.live-search-results')->render();
