<?php
/**
 * Plugin Name: Staging Access Control - Disable Emails (MU)
 * Description: Ensures that the Disable Emails feature of Staging Access Control loads before all other plugins.
 * Version: 1.1.4
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
    $pluggable_file = $sac_plugin_dir . '/staging-access-control/includes/sac-pluggable.php';
    if ( file_exists( $pluggable_file ) ) {
        require_once $pluggable_file;
    }
}
