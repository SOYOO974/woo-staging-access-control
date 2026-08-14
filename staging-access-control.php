<?php
/**
 * Plugin Name: Staging Access Control
 * Plugin URI:  https://www.soyoo.re/
 * Description: Automatically restricts access to staging environments while allowing administrators and whitelisted IPs.
 * Version:     1.1.1
 * Author:      Soyoo.re
 * Author URI:  https://www.soyoo.re/
 * Text Domain: staging-access-control
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/SOYOO974/woo-staging-access-control',
	__FILE__,
	'staging-access-control'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

// If a GitHub token is provided in the settings, use it for authentication
$github_token = get_option( 'sac_github_token', '' );
if ( ! empty( $github_token ) ) {
    $myUpdateChecker->setAuthentication( $github_token );
}

define( 'SAC_VERSION', '1.1.1' );
define( 'SAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Require core classes.
require_once SAC_PLUGIN_DIR . 'includes/class-sac-logger.php';
require_once SAC_PLUGIN_DIR . 'includes/class-sac-settings.php';
require_once SAC_PLUGIN_DIR . 'includes/class-staging-access-control.php';

// Activation Hook
register_activation_hook( __FILE__, 'sac_activate_plugin' );
function sac_activate_plugin() {
    SAC_Logger::create_table();
}

// Initialize the plugin
function sac_init_plugin() {
    $sac = new Staging_Access_Control();
    $sac->init();
    
    if ( is_admin() ) {
        $settings = new SAC_Settings();
        $settings->init();
    }
}
add_action( 'plugins_loaded', 'sac_init_plugin' );
