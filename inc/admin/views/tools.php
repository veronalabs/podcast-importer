<div class="ver-plugin-container">
    <div id="ver-form-container">
        <h2 class="ver-importer-header">
            <?php echo esc_html__('Import a Podcast', 'podcast-importer'); ?>
        </h2>

        <?php
        if (isset($_POST['update'])) :
            $post_id = sanitize_text_field($_POST['post_id']);
            \WP_PODCAST_IMPORTER\admin\Admin::updateProcess($post_id);
        endif;

        if (!isset($_POST['submit'])) :
            \WP_PODCAST_IMPORTER\admin\Admin::settingsForm(false);
        else :
            \WP_PODCAST_IMPORTER\Process::run();
        endif;
        ?>
    </div>
</div>