<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SAC_Disable_Emails {

    private $wpmail_replaced = false;

    public function init() {
        // Handle MU Plugin Actions always
        add_action('admin_init', array($this, 'handle_mu_actions'));

        $options = get_option('sac_disable_emails_options', array());
        $is_enabled = isset($options['enabled']) ? $options['enabled'] : 0;

        if ( ! $is_enabled ) {
            return;
        }

        $this->wpmail_replaced = class_exists('SAC_PHPMailer_Mock', false);

        add_filter('dashboard_glance_items', array($this, 'dashboard_status'), 99);

        // Indicators
        if ($this->wpmail_replaced) {
            $indicator = isset($options['indicator']) ? $options['indicator'] : 'toolbar';
            
            if ($indicator === 'toolbar' || $indicator === 'both') {
                add_action('admin_bar_menu', array($this, 'show_indicator_toolbar'), 500);
            }
            if ($indicator === 'notice' || $indicator === 'both') {
                add_action('admin_notices', array($this, 'show_indicator_notice'));
            }
        } else {
            add_action('admin_notices', array($this, 'show_warning_already_defined'));
        }

        // BuddyPress
        if (!empty($options['buddypress'])) {
            add_filter('bp_email_use_wp_mail', '__return_true');
        }

        // Events Manager
        if (!empty($options['events_manager'])) {
            add_filter('pre_option_dbem_rsvp_mail_send_method', array($this, 'force_events_manager_disable'));
            add_action('load-event_page_events-manager-options', array($this, 'cancel_events_manager_disable'));
        }
    }

    public function show_warning_already_defined() {
        echo '<div class="notice notice-error"><p><strong>Staging Access Control:</strong> Emails are not disabled! Something else has already declared wp_mail(), so we cannot stop emails being sent. Try activating the Must-Use plugin in the settings.</p></div>';
    }

    public function show_indicator_notice() {
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>Staging Access Control:</strong> Emails are currently disabled.</p></div>';
        }
    }

    public function show_indicator_toolbar($admin_bar) {
        if (current_user_can('manage_options')) {
            $admin_bar->add_node(array(
                'id'    => 'sac-disable-emails-indicator',
                'title' => '<span class="ab-icon dashicons dashicons-email-alt" style="margin-top:2px;"></span> <span class="ab-label">Emails Disabled</span>',
                'href'  => admin_url('options-general.php?page=staging-access-control&tab=disable_emails'),
            ));
        }
    }

    public function dashboard_status($glances) {
        if ($this->wpmail_replaced) {
            $glances[] = '<li style="float:none"><i class="dashicons dashicons-email" aria-hidden="true"></i> Emails are currently disabled by Staging Access Control</li>';
        }
        return $glances;
    }

    public function force_events_manager_disable($return_value) {
        return 'wp_mail';
    }

    public function cancel_events_manager_disable() {
        remove_filter('pre_option_dbem_rsvp_mail_send_method', array($this, 'force_events_manager_disable'));
    }

    public function handle_mu_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_GET['sac_mu_action'] ) && check_admin_referer( 'sac_mu_action_nonce' ) ) {
            $action = sanitize_text_field( $_GET['sac_mu_action'] );
            $mu_dir = WPMU_PLUGIN_DIR;
            $mu_file = $mu_dir . '/sac-disable-emails-mu.php';
            
            if ( $action === 'install' ) {
                if ( ! is_dir( $mu_dir ) ) {
                    wp_mkdir_p( $mu_dir );
                }
                
                $source = SAC_PLUGIN_DIR . 'mu-plugin/sac-disable-emails-mu.php';
                if ( file_exists( $source ) ) {
                    copy( $source, $mu_file );
                }
            } elseif ( $action === 'remove' ) {
                if ( file_exists( $mu_file ) ) {
                    unlink( $mu_file );
                }
            }
            
            wp_safe_redirect( admin_url( 'options-general.php?page=staging-access-control&tab=disable_emails' ) );
            exit;
        }
    }
}
