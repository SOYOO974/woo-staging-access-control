<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SAC_Logger {
    
    const MAX_LOGS = 1000;
    
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'sac_logs';
    }

    public static function create_table() {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(100) NOT NULL,
            timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            requested_url varchar(255) NOT NULL,
            user_agent varchar(255) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public static function log_access_attempt( $ip, $url ) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $raw_ua     = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
        $user_agent = mb_substr( sanitize_text_field( $raw_ua ), 0, 255 );
        $clean_url  = mb_substr( esc_url_raw( $url ), 0, 255 );
        
        $wpdb->insert(
            $table_name,
            array(
                'ip_address'    => sanitize_text_field( $ip ),
                'timestamp'     => current_time( 'mysql' ),
                'requested_url' => $clean_url,
                'user_agent'    => $user_agent,
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        // Automatically purge older logs to prevent database bloat
        self::purge_old_logs();
    }

    public static function purge_old_logs( $limit = self::MAX_LOGS ) {
        global $wpdb;
        $table_name = self::get_table_name();

        $offset = max( 1, intval( $limit ) );
        $threshold_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1 OFFSET %d",
            $offset
        ) );

        if ( $threshold_id ) {
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE id <= %d",
                $threshold_id
            ) );
        }
    }
}
