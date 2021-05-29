<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://veronalabs.com/
 * @since      1.0.0
 *
 * @package    Podcast_Importer
 */

class Podcast_Importer_Deactivator {

	/**
	 * Remove cron event on plugin deactivation.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		$podcast_importer_next_scheduled = wp_next_scheduled( 'podcast_importer_cron' );
		wp_unschedule_event($podcast_importer_next_scheduled, 'hourly', 'podcast_importer_cron');
	}

}
