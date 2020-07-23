<?php

/**
 * This file is part of WpAlgolia plugin.
 * (c) Antoine Girard for Mill3 Studio <antoine@mill3.studio>
 * @version 0.0.2
 * @since 0.0.2
 */


namespace WpAlgolia;

interface RegisterInterface
{
    public function get_post_type();

    public function save_post($postId, $post);

    public function delete_post($postId, $post);

    public function update_taxonomy_posts($term_id, $tt_id);

    public function register_bulk_update($bulk_actions);

    public function handle_bulk_update($redirect_to, $doaction, $post_ids);

    public function manage_admin_columns($columns);

    public function manage_admin_check_algolia_status();

    public function manage_admin_column($column, $post_id);

    public function cli_reindex();

    public function cli_set_settings($locale);

}
