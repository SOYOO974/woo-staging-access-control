<?php
/**
 * Plugin Name: Staging Access Control - Disable Emails (MU)
 * Description: Ensures that the Disable Emails feature of Staging Access Control loads before all other plugins.
 * Version: 1.1.7
 * Author: Soyoo.re
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load the pluggable override if the option is enabled
$sac_email_options = get_option('sac_disable_emails_options', array());
$sac_emails_enabled = isset($sac_email_options['enabled']) ? $sac_email_options['enabled'] : 0;

if ( $sac_emails_enabled ) {
    $sac_plugin_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
    
    // Check known potential directory names
    $possible_paths = array(
        $sac_plugin_dir . '/woo-staging-access-control/includes/sac-pluggable.php',
        $sac_plugin_dir . '/staging-access-control/includes/sac-pluggable.php',
    );

    $pluggable_file = '';
    foreach ( $possible_paths as $path ) {
        if ( file_exists( $path ) ) {
            $pluggable_file = $path;
            break;
        }
    }

    // Dynamic glob fallback if installed under a custom directory name
    if ( empty( $pluggable_file ) && is_dir( $sac_plugin_dir ) ) {
        $matched = glob( $sac_plugin_dir . '/*staging-access-control*/includes/sac-pluggable.php' );
        if ( ! empty( $matched ) && file_exists( $matched[0] ) ) {
            $pluggable_file = $matched[0];
        }
    }

    if ( ! empty( $pluggable_file ) && file_exists( $pluggable_file ) ) {
        require_once $pluggable_file;
    }
}
