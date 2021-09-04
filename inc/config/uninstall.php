<?php

namespace WP_PODCAST_IMPORTER\config;

class uninstall
{
    public static function run_uninstall()
    {
        $podcast_importer_next_scheduled = wp_next_scheduled('podcast_importer_cron');
        wp_unschedule_event($podcast_importer_next_scheduled, 'hourly', 'podcast_importer_cron');
    }
}
