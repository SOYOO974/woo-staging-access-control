<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if email disabling is enabled
$sac_email_options = get_option('sac_disable_emails_options', array());
$sac_emails_enabled = isset($sac_email_options['enabled']) ? $sac_email_options['enabled'] : 0;

if ( $sac_emails_enabled && ! function_exists( 'wp_mail' ) ) {

    require_once dirname( __FILE__ ) . '/class-sac-phpmailer-mock.php';

    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        global $phpmailer;

        // Create mock PHPMailer object
        $phpmailer = new SAC_PHPMailer_Mock();
        return $phpmailer->wpmail( $to, $subject, $message, $headers, $attachments );
    }

}
