# Site details in the theme (WP-106)

The business's phone numbers, emails, addresses, opening hours and social profiles are set ONCE (Settings > Site details, the platform's `site-details` module, seeded from the site's `config/pkde-site-details.php`) and read everywhere from there. A site without the platform module keeps working: the kit reads its old `client_*` options the same way the footer always did.

| Use | How |
|---|---|
| In PHP | `\App\site_detail('business.trading_name')`, `\App\site_detail('phones.0.display')`, `\App\site_phones()`, `site_emails()`, `site_locations()`, `site_social_list(...)`, `site_hours_summary(...)` |
| In Blade | `<x-site-phone />`, `<x-site-phone :index="1" label />`, `<x-site-email />`, `<x-site-address />`, `<x-site-hours />`, `<x-social-links />` (also `<x-pkde-kit::site-phone />`) |
| In the editor | the blocks **Contact details**, **Social links** and **Opening hours**: nothing to type, a live preview, and the colours of the section they sit in |
| In text | `[site_phone]`, `[site_email]`, `[site_address]`, `[site_hours]` (an attribute `index="1"` picks the second phone, email or address) |
| The footer and the contact template | `contact_lines()` and `social_links()` now read Site details (the first phone, email and address; every profile in the order set), so they follow it without a change to the views |
| The header | an optional slot with the first phone and the social icons, **off by default**: `add_filter('pkde_kit/header_site_details', '__return_true')` |

- **Email addresses** are printed as character references (WordPress's `antispambot()`), so a simple scraper does not read them; a person sees them normally.
- **Phone numbers** link as `tel:` from the stored E.164 number and show as typed.
- **Opening hours** group consecutive days with the same hours ("Monday to Friday 09:00 to 17:30") and list the holidays still to come; a site with no hours prints nothing.
- **The icons** are one inline SVG set (Instagram, Facebook, LinkedIn, YouTube, TikTok, X, Pinterest, Threads, Google Business Profile, and a generic one for "Other"), each link with an accessible name, no third-party scripts.
- **Colours** follow the section (`--pkde-ink`, `--pkde-body`, `--pkde-link`), so the same block is right in "Brand Slate" and in "Brand Sage (light)"; `--pkde-site-gap` sets the spacing (`resources/css/site-details.css`).
