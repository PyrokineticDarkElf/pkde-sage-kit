// Shop filters (partials/sidebar-shop.blade.php): ticking a box or typing a price changes only the form; the visitor sends the choices once with "Apply
// filters", so a filter click no longer costs an uncached page request. This file keeps the button honest: it counts the choices that differ from what is
// applied now ("Apply filters (3)") and tells a screen reader. Nothing here can block a submit, and without JavaScript the form is a plain GET form with a
// submit button. The applied state is what the server rendered (the inputs' default values), not what a browser restored after Back.

/** Fields in, the number of pending changes out (pure). A field is {type: 'checkbox', checked, defaultChecked} or {type: 'text', value, defaultValue}. */
export function pendingCount(fields) {
  return fields.reduce((count, field) => {
    if (field.type === 'checkbox') return count + (field.checked !== field.defaultChecked ? 1 : 0);

    return count + (String(field.value ?? '').trim() !== String(field.defaultValue ?? '').trim() ? 1 : 0);
  }, 0);
}

/** "Apply filters (%d)" with the number in (pure). */
export function withCount(template, count) {
  return String(template || '').replace('%d', String(count));
}

/** The sentence a screen reader gets when the count changes (pure): none, one or many. */
export function statusText(count, { none = '', one = '', many = '' } = {}) {
  if (count <= 0) return none;

  return count === 1 ? one : withCount(many, count);
}

const describe = (input) => (input.type === 'checkbox'
  ? { type: 'checkbox', checked: input.checked, defaultChecked: input.defaultChecked }
  : { type: 'text', value: input.value, defaultValue: input.defaultValue });

if (typeof document !== 'undefined') {
  document.querySelectorAll('[data-shop-filters]').forEach((form) => {
    const apply = form.querySelector('[data-shop-apply]');
    if (!apply) return;

    const label = apply.querySelector('[data-shop-apply-label]') || apply;
    const status = form.querySelector('[data-shop-status]');
    const fields = [...form.querySelectorAll('input[type="checkbox"], [data-price-input]')];

    const update = () => {
      const count = pendingCount(fields.map(describe));
      apply.dataset.pending = String(count);
      label.textContent = count > 0 ? withCount(apply.dataset.pendingLabel, count) : apply.dataset.label;

      if (status) {
        status.textContent = statusText(count, { none: status.dataset.statusNone, one: status.dataset.statusOne, many: status.dataset.statusMany });
      }
    };

    fields.forEach((field) => {
      field.addEventListener('change', update);
      field.addEventListener('input', update);
    });
    // Reset with nothing applied is a native reset button (back to the rendered state, which is "nothing"); the values change after the event.
    form.addEventListener('reset', () => setTimeout(update, 0));
    // A browser may restore ticked boxes after Back/Forward: count them against the rendered state again.
    window.addEventListener('pageshow', update);
    update();
  });
}
