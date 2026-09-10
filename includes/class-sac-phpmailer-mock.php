<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as phpmailerException;

class SAC_EmailAddress {
    public $name;
    public $address;

    public function __construct($email_address) {
        if (preg_match('/(.*)<(.+)>/', $email_address, $matches) && count($matches) === 3) {
            $this->name    = rtrim($matches[1], ' ');
            $this->address = $matches[2];
        } else {
            $this->name    = '';
            $this->address = $email_address;
        }
    }
}

class SAC_PHPMailer_Mock {
    private $phpmailer;
    private $allowed_calls;

    public function __construct() {
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        $this->phpmailer = new PHPMailer( true );

        // Map of allowed function calls
        $this->allowed_calls = array_flip([
            'isHTML', 'addAddress', 'addCC', 'addBCC', 'addReplyTo', 'setFrom',
            'addrAppend', 'addrFormat', 'wrapText', 'utf8CharBoundary', 'setWordWrap',
            'createHeader', 'getMailMIME', 'getSentMIMEMessage', 'createBody', 'headerLine',
            'textLine', 'addAttachment', 'getAttachments', 'encodeString', 'encodeHeader',
            'hasMultiBytes', 'base64EncodeWrapMB', 'encodeQP', 'encodeQ', 'addStringAttachment',
            'addEmbeddedImage', 'addStringEmbeddedImage', 'inlineImageExists', 'attachmentExists',
            'alternativeExists', 'clearAddresses', 'clearCCs', 'clearBCCs', 'clearReplyTos',
            'clearAllRecipients', 'clearAttachments', 'clearCustomHeaders', 'setError',
            'rfcDate', 'isError', 'fixEOL', 'addCustomHeader', 'msgHTML', 'html2text',
            'filenameToType', 'mb_pathinfo', 'set', 'secureHeader', 'normalizeBreaks',
            'sign', 'DKIM_QP', 'DKIM_Sign', 'DKIM_HeaderC', 'DKIM_BodyC', 'DKIM_Add',
            'validateAddress',
        ]);
    }

    public function wpmail($to, $subject, $message, $headers, $attachments) {
        // get the site domain and get rid of www.
        if (isset($_SERVER['SERVER_NAME'])) {
            $sitename = strtolower( $_SERVER['SERVER_NAME'] );
            if ( substr( $sitename, 0, 4 ) === 'www.' ) {
                $sitename = substr( $sitename, 4 );
            }
        } else {
            $sitename = 'server-name.invalid';
        }

        // set default From name and address
        $this->phpmailer->FromName = 'WordPress';
        $this->phpmailer->From = 'wordpress@' . $sitename;

        // Fetch options
        $options = get_option('sac_disable_emails_options', array());
        $sim_wp_mail = isset($options['wp_mail']) ? $options['wp_mail'] : 1;
        $sim_wp_mail_from = isset($options['wp_mail_from']) ? $options['wp_mail_from'] : 1;
        $sim_wp_mail_from_name = isset($options['wp_mail_from_name']) ? $options['wp_mail_from_name'] : 1;
        $sim_wp_mail_content_type = isset($options['wp_mail_content_type']) ? $options['wp_mail_content_type'] : 1;
        $sim_wp_mail_charset = isset($options['wp_mail_charset']) ? $options['wp_mail_charset'] : 1;
        $sim_phpmailer_init = isset($options['phpmailer_init']) ? $options['phpmailer_init'] : 1;

        if ($sim_wp_mail) {
            $args = apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments'));
            if (is_array($args)) {
                extract($args, EXTR_IF_EXISTS);
            } else {
                return false;
            }
        }

        try {
            $this->_addTo($to);
        } catch (phpmailerException $e) {}

        $this->phpmailer->Subject = $subject;
        $this->phpmailer->Body = $message;

        // headers
        if ( !empty( $headers ) ) {
            if ( !is_array( $headers ) ) {
                $headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
            }

            foreach ( $headers as $header ) {
                if ( strpos($header, ':') === false ) {
                    continue;
                }

                list( $name, $content ) = explode( ':', trim( $header ), 2 );
                $name    = trim( $name    );
                $content = trim( $content );

                try {
                    switch ( strtolower( $name ) ) {
                        case 'from':
                            $this->_setFrom( $content );
                            break;
                        case 'cc':
                            $this->_addCC( explode( ',', $content ) );
                            break;
                        case 'bcc':
                            $this->_addBCC( explode( ',', $content ) );
                            break;
                        case 'content-type':
                            $this->_setContentType( $content );
                            break;
                        default:
                            $this->phpmailer->AddCustomHeader( "$name: $content" );
                            break;
                    }
                } catch (phpmailerException $e) {
                    continue;
                }
            }
        }

        // attachments
        if ( !empty( $attachments ) ) {
            foreach ( $attachments as $attachment ) {
                try {
                    $this->phpmailer->AddAttachment($attachment);
                } catch ( phpmailerException $e ) {
                    continue;
                }
            }
        }

        if ($sim_wp_mail_from) {
            $this->phpmailer->From = apply_filters( 'wp_mail_from', $this->phpmailer->From );
        }
        if ($sim_wp_mail_from_name) {
            $this->phpmailer->FromName = apply_filters( 'wp_mail_from_name', $this->phpmailer->FromName );
        }
        if ($sim_wp_mail_content_type) {
            $this->phpmailer->ContentType = apply_filters( 'wp_mail_content_type', $this->phpmailer->ContentType );
        }
        if ($sim_wp_mail_charset) {
            $this->phpmailer->CharSet = apply_filters( 'wp_mail_charset', $this->phpmailer->CharSet );
        }
        if ($sim_phpmailer_init) {
            do_action('phpmailer_init', $this->phpmailer);
        }

        return true;
    }

    protected function _setFrom($from) {
        $recipient = new SAC_EmailAddress($from);
        $this->phpmailer->FromName = trim($recipient->name);
        $this->phpmailer->From = $recipient->address;
    }

    protected function _addTo($to) {
        $recipients = is_array($to) ? $to : explode(',', $to);
        foreach ($recipients as $address) {
            $recipient = new SAC_EmailAddress($address);
            $this->phpmailer->addAddress($recipient->address, $recipient->name);
        }
    }

    protected function _addCC($addresses) {
        foreach ($addresses as $address) {
            try {
                $recipient = new SAC_EmailAddress($address);
                $this->phpmailer->addCC($recipient->address, $recipient->name);
            } catch ( phpmailerException $e ) {
                continue;
            }
        }
    }

    protected function _addBCC($addresses) {
        foreach ($addresses as $address) {
            try {
                $recipient = new SAC_EmailAddress($address);
                $this->phpmailer->addBCC($recipient->address, $recipient->name);
            } catch ( phpmailerException $e ) {
                continue;
            }
        }
    }

    protected function _setContentType($content_type) {
        if ( strpos( $content_type, ';' ) !== false ) {
            list( $type, $charset ) = explode( ';', $content_type );
            $this->phpmailer->ContentType = trim( $type );
            if ( false !== stripos( $charset, 'charset=' ) ) {
                $this->phpmailer->CharSet = trim( str_ireplace( ['charset=', '"'], '', $charset ) );
            }
        } else {
            $this->phpmailer->ContentType = trim( $content_type );
        }
    }

    public function __set($name, $value) {
        $this->phpmailer->$name = $value;
    }

    public function __get($name) {
        return $this->phpmailer->$name;
    }

    public function __call($name, $args) {
        if (isset($this->allowed_calls[$name])) {
            switch (count($args)) {
                case 1: return $this->phpmailer->$name($args[0]);
                case 2: return $this->phpmailer->$name($args[0], $args[1]);
                case 3: return $this->phpmailer->$name($args[0], $args[1], $args[2]);
                case 4: return $this->phpmailer->$name($args[0], $args[1], $args[2], $args[3]);
                case 5: return $this->phpmailer->$name($args[0], $args[1], $args[2], $args[3], $args[4]);
                case 6: return $this->phpmailer->$name($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
                default: return $this->phpmailer->$name();
            }
        }
        return false;
    }

    public static function __callStatic($name, $args) {
        return false;
    }
}
