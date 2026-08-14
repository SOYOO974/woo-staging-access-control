<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get Settings
$logo_id = get_option( 'sac_custom_logo' );
$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
$logo_max_width = get_option( 'sac_logo_max_width', '200' );

$heading_text = get_option( 'sac_heading_text', 'Staging Environment' );
$message = get_option( 'sac_maintenance_message', 'This staging site is currently restricted.' );
$production_url_base = rtrim( get_option( 'sac_production_url', '' ), '/' );

$button_text = get_option( 'sac_button_text', 'Go to Production Site' );
$login_button_text = get_option( 'sac_login_button_text', 'Staging Login' );
$button_color = get_option( 'sac_button_color', '#3b82f6' );

// Calculate shadow color (slightly darker, 20% opacity)
$r = 59; $g = 130; $b = 246; // default blue
if ( preg_match( '/^#?([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})$/', $button_color, $matches ) ) {
    $r = hexdec( $matches[1] );
    $g = hexdec( $matches[2] );
    $b = hexdec( $matches[3] );
}
// Darken by 20%
$shadow_r = round( max( 0, $r * 0.8 ) );
$shadow_g = round( max( 0, $g * 0.8 ) );
$shadow_b = round( max( 0, $b * 0.8 ) );
$shadow_color = "rgba({$shadow_r}, {$shadow_g}, {$shadow_b}, 0.2)";

// Calculate production link based on current URI
$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
$production_link = '';
if ( ! empty( $production_url_base ) ) {
    $production_link = $production_url_base . $request_uri;
}

// Calculate login link
$bypass_param = get_option( 'sac_bypass_param', 'unlock' );
$login_link = add_query_arg( $bypass_param, '1', $request_uri );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $heading_text ); ?> | <?php bloginfo( 'name' ); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --text-color: #f8fafc;
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.1);
            --accent-color: <?php echo esc_attr( $button_color ); ?>;
            --accent-hover: <?php echo esc_attr( $button_color ); ?>;
            --glow: <?php echo esc_attr( $shadow_color ); ?>;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background Gradients */
        .bg-shape {
            position: absolute;
            filter: blur(100px);
            z-index: 0;
            animation: float 20s infinite ease-in-out alternate;
        }
        .shape-1 {
            width: 40vw;
            height: 40vw;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            top: -10vw;
            left: -10vw;
            border-radius: 50%;
            opacity: 0.3;
        }
        .shape-2 {
            width: 30vw;
            height: 30vw;
            background: linear-gradient(to right, #ec4899, #f43f5e);
            bottom: -5vw;
            right: -10vw;
            border-radius: 50%;
            opacity: 0.2;
            animation-delay: -10s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(5vw, 5vh) scale(1.1); }
        }

        /* Glassmorphism Card */
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            position: relative;
            z-index: 1;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(40px);
        }

        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            max-width: <?php echo esc_attr( $logo_max_width ); ?>px;
            height: auto;
            margin-bottom: 30px;
            animation: fadeIn 1s ease 0.3s forwards;
            opacity: 0;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        h1 {
            font-size: 32px;
            font-weight: 800;
            margin: 0 0 15px;
            background: linear-gradient(to right, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .message {
            font-size: 16px;
            line-height: 1.6;
            color: #94a3b8;
            margin-bottom: 40px;
        }

        .message p {
            margin: 0 0 15px;
        }
        .message p:last-child {
            margin-bottom: 0;
        }

        .btn {
            display: inline-block;
            background: var(--accent-color);
            color: #fff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px 0 var(--glow);
            position: relative;
            overflow: hidden;
            /* Ensure the button gets darker on hover instead of just matching the accent color if it was default */
            filter: brightness(100%);
        }

        .btn:hover {
            filter: brightness(90%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 var(--glow);
        }

        .btn:active {
            transform: translateY(1px);
        }

        /* Subtle shine effect on button */
        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
            transform: skewX(-25deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { left: -100%; }
            20% { left: 200%; }
            100% { left: 200%; }
        }

        .btn-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .discreet-link {
            color: #94a3b8;
            font-size: 11px;
            text-decoration: underline;
            transition: color 0.3s ease, text-shadow 0.3s ease;
        }

        .discreet-link:hover {
            color: #fff;
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 600px) {
            .card {
                padding: 40px 25px;
            }
            h1 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="card">
        <?php if ( $logo_url ) : ?>
            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="logo" />
        <?php endif; ?>

        <h1><?php echo esc_html( $heading_text ); ?></h1>
        
        <div class="message">
            <?php echo wp_kses_post( wpautop( $message ) ); ?>
        </div>

        <div class="btn-container">
            <?php if ( $production_link ) : ?>
                <a href="<?php echo esc_url( $production_link ); ?>" class="btn"><?php echo esc_html( $button_text ); ?></a>
            <?php endif; ?>
            <a href="<?php echo esc_url( $login_link ); ?>" class="discreet-link"><?php echo esc_html( $login_button_text ); ?></a>
        </div>
    </div>
</body>
</html>
