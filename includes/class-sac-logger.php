<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SAC_Logger {
    
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
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        
        $wpdb->insert(
            $table_name,
            array(
                'ip_address'    => sanitize_text_field( $ip ),
                'timestamp'     => current_time( 'mysql' ),
                'requested_url' => esc_url_raw( $url ),
                'user_agent'    => $user_agent,
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
    }
}
