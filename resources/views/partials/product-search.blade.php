{{--
  The product search form, with live results when Relevanssi Live Ajax Search is active (the data-rlv* attributes are inert otherwise, and
  the form is a plain product search). Put it in the header bar or in the panel the search icon opens (partials/header-search.blade.php);
  $id keeps each copy's field and results apart, $class adds to the form.
--}}
@php($id = $id ?? 'site-search')
<form role="search" method="get" action="{{ home_url('/') }}" @class(['site-search', $class ?? null])>
  <label class="sr-only" for="{{ $id }}-field">{{ __('Search products', 'sage') }}</label>
  <input id="{{ $id }}-field" type="search" name="s" value="{{ is_search() ? get_search_query(false) : '' }}" placeholder="{{ __('Search', 'sage') }}" class="site-search__input" autocomplete="off" data-rlvlive="true" data-rlvparentel="#{{ $id }}-results" data-rlvconfig="default" />
  <input type="hidden" name="post_type" value="product" />
  {{-- Live results open in here, so Tab reaches them; the instructions are for screen readers. --}}
  <span class="sr-only">{{ __('When results appear, use the up and down arrows to move through them and Enter to open one.', 'sage') }}</span>
  <div id="{{ $id }}-results" class="site-search__results"></div>
</form>
