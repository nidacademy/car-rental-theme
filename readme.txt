=== Exotic Cars ===
Contributors: Nid Academy
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Version: 1.2.1
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete car rental website: cars, online booking, deposits, customer emails, dashboard,
7 languages and full design control from the Customizer.

== Installation ==

1. In WordPress go to Appearance -> Themes -> Add New -> Upload Theme, choose exotic-cars.zip and click Install Now, then Activate.
2. Go to Appearance -> Import demo.
3. Click "Install & activate" next to Elementor and GTranslate.
4. Click "Import the demo". After about a minute your site looks like the demo.

== After the import: make it yours ==

* Site name: Settings -> General -> Site Title (used in the header, emails and page descriptions).
* Logo, colours, header, footer, welcome and language pages: Appearance -> Customize.
* Page texts and photos: open a page and click "Edit with Elementor".
* Cars, prices, discounts, locations and bookings: the "Your Cars" menu.
* Card deposits with Stripe: Your Cars -> Online payments (paste your own Stripe keys).
* Email sender (optional): set the option "orego_mail_from" to a mailbox on your own domain.

== Notes ==

* The demo photos and car brand logos are examples. Replace them with your own photos and
  check that you are allowed to use any brand logo you keep.
* Importing the demo again replaces the demo pages with their original version.

== Updates ==

New versions are published on GitHub (github.com/NIDACADEMY/car-rental-theme) and show
under Dashboard -> Updates like any other theme.

== External services ==

* GitHub (api.github.com): checks for a new theme release at most every 12 hours. Only the
  repository name is sent. https://docs.github.com/site-policy/privacy-policies/github-general-privacy-statement
* Stripe (api.stripe.com): only when you turn on card deposits and paste your own Stripe keys.
  Booking amounts and the customer's card details go to Stripe. https://stripe.com/privacy
* OpenStreetMap: the map page loads map tiles from tile.openstreetmap.org and looks up your
  pick-up locations with nominatim.openstreetmap.org (the location names are sent).
  The map script (Leaflet) loads from cdnjs.cloudflare.com. https://osmfoundation.org/wiki/Privacy_Policy
* Google Fonts: when you choose a Google font in the Customizer, it loads from fonts.googleapis.com
  and fonts.gstatic.com. The default fonts are bundled with the theme. https://policies.google.com/privacy
* Google Maps: "Directions" links open google.com/maps in the visitor's browser.

== Changelog ==

See CHANGELOG.md.

== Credits ==

* Fonts: IBM Plex Sans Arabic, Cairo, Assistant, Playfair Display (SIL Open Font License 1.1).
* Car brand logos are trademarks of their owners and are included as examples only.
