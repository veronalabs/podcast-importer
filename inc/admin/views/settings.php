<form method="POST" action="" class="podcast_importer_form">
    <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_feed"
           class="ver-form-label"><?php echo esc_html__('Podcast Feed URL', 'podcast-importer'); ?>
    </label>
    <input required="required" class="podcast_feed"
           id="<?php echo esc_attr($post_id_to_update); ?>_podcast_feed" type="url" name="podcast_feed"
           value="<?php echo esc_url(get_post_meta($post_id_to_update, 'podcast_importer_rss_feed', true)); ?>"
           placeholder=""
    >
    <label for="<?php echo esc_attr($post_id_to_update); ?>_post_type_select"
           class="ver-form-label"><?php echo esc_html__('Post Type', 'podcast-importer'); ?>
    </label>

    <select id="<?php echo esc_attr($post_id_to_update); ?>_post_type_select" name="post_type_select"
            class="post_type_select">
        <?php \WP_PODCAST_IMPORTER\Helper::postTypeControl(get_post_meta($post_id_to_update, 'podcast_importer_post_type', true)); ?>
    </select>
    <div class="clearfix-ver"></div>

    <label for="<?php echo esc_attr($post_id_to_update); ?>_publish_option_select"
           class="ver-form-label"><?php echo esc_html__('Post Status', 'podcast-importer'); ?></label>
    <select id="<?php echo esc_attr($post_id_to_update); ?>_publish_option_select" name="publish_option_select">
        <?php $podcast_importer_publish = get_post_meta($post_id_to_update, 'podcast_importer_publish', true); ?>
        <option <?php echo $podcast_importer_publish === 'publish' ? ' selected' : ''; ?>
                value="publish"><?php echo esc_html__('Publish', 'podcast-importer') ?></option>
        <option <?php echo $podcast_importer_publish === 'draft' ? ' selected' : ''; ?>
                value="draft"><?php echo esc_html__('Save as Draft', 'podcast-importer') ?></option>
    </select>

    <div class="clearfix-ver"></div>

    <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_author"
           class="ver-form-label"><?php echo esc_html__('Post Author', 'podcast-importer'); ?></label>

    <?php wp_dropdown_users(array(
        'name' => 'podcast_importer_author',
        'selected' => get_post_meta($post_id_to_update, 'podcast_importer_author', true)
    )); ?>

    <div class="clearfix-ver"></div>

    <label for="<?php echo esc_attr($post_id_to_update); ?>_post_category_select"
           class="ver-form-label"><?php echo esc_html__('Post Category (or Categories)', 'podcast-importer'); ?></label>
    <select id="<?php echo esc_attr($post_id_to_update); ?>_post_category_select" name="post_category_select[]"
            multiple="multiple">
        <?php \WP_PODCAST_IMPORTER\Helper::listTerms(get_post_meta($post_id_to_update, 'podcast_importer_category', true)); ?>
    </select>

    <div class="clearfix-ver"></div>

    <div class="ver-checkbox-container">
        <?php $podcast_importer_images = get_post_meta($post_id_to_update, 'podcast_importer_images', true); ?>

        <input <?php echo $podcast_importer_images === 'on' ? ' checked' : ''; ?> type="checkbox"
                                                                                  id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_images"
                                                                                  name="podcast_importer_images"/>
        <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_images"><?php echo esc_html__('Import Episode Featured Images', 'podcast-importer') ?></label>
    </div>

    <div class="ver-checkbox-container">
        <?php $podcast_importer_embed_player = get_post_meta($post_id_to_update, 'podcast_importer_embed_player', true); ?>
        <input <?php echo $podcast_importer_embed_player === 'on' ? ' checked' : ''; ?> type="checkbox"
                                                                                        id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_embed_player"
                                                                                        name="podcast_importer_embed_player"/>
        <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_embed_player"><?php echo esc_html__('Use an embed audio player instead of the default WordPress player (depending on your podcast host)', 'podcast-importer') ?></label>
    </div>

    <div class="settings-wrapper">
        <h3 class="ver-importer-header advanced-options"><?php echo esc_html__('Advanced Options', 'podcast-importer'); ?></h3>
        <div class="advanced-settings-wrapper">
            <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_content_tag"
                   class="ver-form-label"><?php echo esc_html__('Imported Content Tag', 'podcast-importer'); ?></label>
            <select id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_content_tag"
                    name="podcast_importer_content_tag">
                <?php $podcast_importer_content_tag = get_post_meta($post_id_to_update, 'podcast_importer_content_tag', true); ?>
                <option value="select"><?php echo esc_html__('Select Option', 'podcast-importer') ?></option>
                <option <?php echo $podcast_importer_content_tag === 'content:encoded' ? ' selected' : ''; ?>
                        value="content:encoded"><?php echo esc_html__('content:encoded', 'podcast-importer') ?></option>
                <option <?php echo $podcast_importer_content_tag === 'description' ? ' selected' : ''; ?>
                        value="description"><?php echo esc_html__('description', 'podcast-importer') ?></option>
                <option <?php echo $podcast_importer_content_tag === 'itunes:summary' ? ' selected' : ''; ?>
                        value="itunes:summary"><?php echo esc_html__('itunes:summary', 'podcast-importer') ?></option>
            </select>

            <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_truncate_post"
                   class="ver-form-label">
                <?php echo esc_html__('Truncate Post Content', 'podcast-importer'); ?><br>
                <em>Optional: Will trim the post content when imported to the character amount below.</em>
            </label>
            <input id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_truncate_post" type="number"
                   name="podcast_importer_truncate_post"
                   value="<?php echo esc_attr(get_post_meta($post_id_to_update, 'podcast_importer_truncate_post', true)); ?>">

            <div class="ver-checkbox-container">
                <?php $podcast_importer_episode_number = get_post_meta($post_id_to_update, 'podcast_importer_episode_number', true); ?>
                <input <?php echo $podcast_importer_episode_number === 'on' ? ' checked' : ''; ?>
                        type="checkbox"
                        id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_episode_number"
                        name="podcast_importer_episode_number"/>
                <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_episode_number"><?php echo esc_html__('Append Episode Number to Post Title', 'podcast-importer') ?></label>
            </div>

            <label for="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_prepend_title"
                   class="ver-form-label">
                <?php echo esc_html__('Prepend Title', 'podcast-importer'); ?><br>
                <em>Optional: Add <code>[podcast_title]</code> to display the show name.</em>
            </label>
            <input type="text" id="<?php echo esc_attr($post_id_to_update); ?>_podcast_importer_prepend_title"
                   name="podcast_importer_prepend_title" placeholder="Ex: My Podcast"
                   value="<?php echo esc_attr(get_post_meta($post_id_to_update, 'podcast_importer_prepend_title', true)); ?>">
        </div>
    </div>

    <div class="clearfix-ver"></div>

    <input type="hidden" name="action" value="podcast_importer_initialize"/>

    <?php if ($post_id_to_update) : ?>
        <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id_to_update); ?>"/>

        <input class="button button-primary" type="submit" name="update" value="Update"/>
    <?php else : ?>
        <input class="button button-primary" type="submit" name="submit" value="Import Podcast"/>
    <?php endif; ?>

    <?php wp_nonce_field('podcast_importer_form', 'podcast_form_nonce'); ?>
</form>