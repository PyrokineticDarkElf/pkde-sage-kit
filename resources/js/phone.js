/**
 * Phone fields of Contact Form 7 forms: a country picker (intl-tel-input, bundled by Vite and self-hosted, no CDN) so a visitor can type a number the
 * way they usually do. It only HELPS: the guarantee that a phone is valid and stored as E.164 is on the server (pkde/platform's phone-e164 module),
 * which re-reads whatever was posted. Nothing here runs unless that module printed `window.pkdePhone` (opt-in by PHONE_DEFAULT_REGION), and the
 * library is a dynamic import, so a page without a phone field downloads none of it (its 260 KB of number data loads only after the page, too).
 *
 * Which country reads the number: the "+" and code typed (always wins); the country chosen in the picker, sent as the hidden field `<name>-country`;
 * the visitor's country (Cloudflare's same-origin /cdn-cgi/trace, when the site is behind it); else the site default (window.pkdePhone.region).
 * After a successful submit, the normalised phone and email the server returned go to the data layer (generate_lead), for Google Ads Enhanced Conversions.
 */
const config = typeof window !== 'undefined' ? window.pkdePhone : undefined;

async function visitorCountry(fallback) {
  try {
    const response = await fetch('/cdn-cgi/trace', { credentials: 'omit' });
    const match = (await response.text()).match(/^loc=([A-Z]{2})$/m);

    return (match ? match[1] : fallback).toLowerCase();
  } catch {
    return fallback.toLowerCase();
  }
}

async function attach(inputs) {
  const [{ default: intlTelInput }] = await Promise.all([import('intl-tel-input'), import('intl-tel-input/styles')]);

  inputs.forEach((input) => {
    if (input.dataset.pkdePhone) return;
    input.dataset.pkdePhone = 'on';

    const iti = intlTelInput(input, {
      initialCountry: config.region,
      initialCountryLookup: () => visitorCountry(config.region),
      countryOrder: config.preferred,
      countrySearch: true,
      loadUtils: () => import('intl-tel-input/utils'),
      strictMode: false,
      nationalMode: true,
    });

    // The country the server reads a national number with. CF7 builds its request from the form's fields, so a hidden field in the form travels with it.
    const country = Object.assign(document.createElement('input'), { type: 'hidden', name: `${input.name}-country` });
    input.form.appendChild(country);
    const update = () => { country.value = (iti.getSelectedCountry()?.iso2 ?? config.region).toUpperCase(); };

    input.addEventListener('countrychange', update);
    input.addEventListener('blur', update);
    iti.promise.then(update);
    update();
  });
}

function dataLayerPush(event) {
  const user = event.detail?.apiResponse?.pkde_user_data;

  if (!user) return;
  (window.dataLayer = window.dataLayer || []).push({ event: 'generate_lead', user_data: user });
}

if (config) {
  const inputs = [...document.querySelectorAll('.wpcf7 input[type="tel"]')];

  if (inputs.length) attach(inputs);
  document.addEventListener('wpcf7mailsent', dataLayerPush);
}
