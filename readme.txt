=== Podcast Importer ===
Contributors: veronalabs
Donate link: https://veronalabs.com
Tags: podcast, import, podcasting, feed, audio, rss, episodes, embed
Requires at least: 4.8
Tested up to: 5.7
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A simple podcast import tool for WordPress.

== Description ==

Sync your Podcast RSS feed with WordPress website. The Podcast Importer plugin helps to easily import podcasts into WordPress. You can import your podcast into the regular WordPress posts or into a custom post type (if you have an existing one). 

The plugin supports importing episodes into existing custom post types, assign categories, import featured images and more. Additionally, the plugin enables continuous import or "Sync" of podcast RSS feeds, so every time you release a new podcast episode, it could be automatically created within WordPress. You can also set multiple import schedules and import different podcasts from separate sources at the same time. (For example, when importing separate podcasts from separate feeds into one website)

To use the plugin, simply run a new import under "Tools -> Podcast Importer" via the main menu that appears in your WordPress dashboard. Set the different options and if you need a continuous import process for future episodes, make sure to hit that checkbox before running the import process.
You can disable a schedueld import at any time by simply deleting the import entry. 

The plugin also supports automatic import of native / embed audio players from 15+ podcast hosting providers, including: Buzzsprout, Megaphone, Pinecast, Captivate, Transistor, Anchor.fm, Simplecast, Podbean, Whooshkaa, Omny, Ausha, Spreaker, Audioboom, Fireside, Libsyn and more.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/podcast-importer` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Run a new import via the "Tools -> Podcast Importer" section in your WordPress admin panel.
4. If needed, delete any of the scheduled import processes. 

== Frequently Asked Questions ==

= The import failed or takes too much time to process? =
You can run the importer multiple times, as it will never import the same post twice. Once all episodes are imported, only future ones would be imported, assuming you selected the continuous import option.

= Do you support podcast feeds from any host? =
Sure. All types of podcast feeds can be imported, as long as they are in an RSS/XML format. If you feel something is missing, please reach out and we will ensure to look into it.

= The import does not work for my podcast feed =
First of all, make sure you are filling in a valid URL, of a valid podcast RSS feed. Second, make sure your server is up to modern requirements - we recommend PHP 7 or above.

== Screenshots ==

1. Import your podcast episodes based on multiple options.
2. Add multiple continuous import processes of separate podcasts.

== Changelog ==

= 1.0 =
* Initial Release.
