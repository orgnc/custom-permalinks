<?php

/**
 * Use this to migrate permalink stored in post meta into dedicated table
 */
class Migrate_Custom_Permalinks {
    public function run($args, $assocArgs) {
        global $wpdb;

        $delete = $assocArgs['delete'] ?? false;

        $wpdb->query("
            create table if not exists wtapp_custom_permalinks (
                id            int auto_increment primary key,
                post_id       int        not null,
                meta_value    text       null,
                language_code varchar(2) not null,
                constraint post_id_language_code
                    unique (post_id, language_code),
                constraint wtapp_custom_permalinks_id_uindex
                    unique (id)
            );
        ");

        $wpdb->query("insert ignore into {$wpdb->prefix}custom_permalinks (post_id, meta_value)
            select post_id, meta_value from wtapp_postmeta where meta_key = 'custom_permalink';");

        if ($delete) {
            $wpdb->query("delete from $wpdb->postmeta where meta_key = 'custom_permalink'");
        }
    }
}

WP_CLI::add_command('migrate-custom-permalinks', 'Migrate_Custom_Permalinks');
