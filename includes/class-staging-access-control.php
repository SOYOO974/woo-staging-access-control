<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Staging_Access_Control {

    public function init() {
        add_action( 'init', array( $this, 'handle_password_submission' ) );
        add_action( 'template_redirect', array( $this, 'check_access' ) );
    }

    public function handle_password_submission() {
        if ( isset( $_POST['sac_bypass_password_input'] ) && isset( $_POST['sac_bypass_nonce_field'] ) ) {
            if ( wp_verify_nonce( $_POST['sac_bypass_nonce_field'], 'sac_bypass_nonce' ) ) {
                $submitted_password = sanitize_text_field( $_POST['sac_bypass_password_input'] );
                $correct_password = get_option( 'sac_bypass_password', 'soyoo' );
                
                if ( $submitted_password === $correct_password ) {
                    // Set secure cookie for 24 hours
                    $token = wp_hash( 'sac_access_' . $correct_password );
                    setcookie( 'sac_bypass_token', $token, time() + 24 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
                    
                    // Redirect to clear POST data and optionally remove the bypass param from URL
                    $redirect_url = remove_query_arg( get_option( 'sac_bypass_param', 'unlock' ), wp_unslash( $_SERVER['REQUEST_URI'] ) );
                    wp_safe_redirect( $redirect_url );
                    exit;
                } else {
                    // Invalid password, set a flag to show error
                    global $sac_password_error;
                    $sac_password_error = true;
                }
            }
        }
    }

    public function is_staging() {
        $site_url = site_url();
        return ( strpos( $site_url, 'staging' ) !== false );
    }

    public function get_visitor_ip() {
        // Support for Cloudflare and reverse proxies
        if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            return sanitize_text_field( trim( $ips[0] ) );
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
        return '';
    }

    public function is_ip_whitelisted( $ip ) {
        $whitelist_raw = get_option( 'sac_ip_whitelist', '' );
        if ( empty( $whitelist_raw ) ) {
            return false;
        }

        $whitelisted_ips = array_map( 'trim', explode( "\n", $whitelist_raw ) );
        return in_array( $ip, $whitelisted_ips, true );
    }

    public function check_access() {
        // Only run on front-end
        if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
            return;
        }

        if ( ! $this->is_staging() ) {
            return; // Only restrict if it's a staging site
        }

        // Allow administrators
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        // Allow whitelisted IPs
        $visitor_ip = $this->get_visitor_ip();
        if ( $this->is_ip_whitelisted( $visitor_ip ) ) {
            return;
        }

        // Check for valid bypass cookie (24 hour session)
        if ( isset( $_COOKIE['sac_bypass_token'] ) ) {
            $correct_password = get_option( 'sac_bypass_password', 'soyoo' );
            $expected_token = wp_hash( 'sac_access_' . $correct_password );
            if ( $_COOKIE['sac_bypass_token'] === $expected_token ) {
                return; // Grant access smoothly
            }
        }

        // Block access: Log the attempt
        $requested_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        SAC_Logger::log_access_attempt( $visitor_ip, $requested_url );

        // Determine which page to show (Maintenance vs Password Prompt)
        $bypass_param = get_option( 'sac_bypass_param', 'unlock' );
        global $sac_password_error;
        
        if ( isset( $_GET[$bypass_param] ) || ! empty( $sac_password_error ) ) {
            $this->show_password_page();
        } else {
            $this->show_maintenance_page();
        }
        exit;
    }

    public function show_maintenance_page() {
        // Prevent caching
        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
        nocache_headers();
        status_header( 503 ); // 503 Service Unavailable is SEO friendly for temporary maintenance

        $template_path = SAC_PLUGIN_DIR . 'templates/maintenance-page.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            wp_die( 'This site is currently in staging mode and restricted.', 'Restricted Access' );
        }
    }

    public function show_password_page() {
        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
        nocache_headers();
        status_header( 401 ); // 401 Unauthorized
        
        global $sac_password_error;

        $template_path = SAC_PLUGIN_DIR . 'templates/password-page.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            wp_die( 'Please enter the password to access.', 'Restricted Access' );
        }
    }
}
