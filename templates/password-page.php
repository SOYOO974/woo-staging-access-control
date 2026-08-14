<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get Settings
$logo_id = get_option( 'sac_custom_logo' );
$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
$logo_max_width = get_option( 'sac_logo_max_width', '200' );

$heading_text = get_option( 'sac_password_heading_text', 'Access Restricted' );
$message = get_option( 'sac_password_message', 'Please enter the password to access this staging site.' );
$placeholder = get_option( 'sac_password_placeholder', 'Enter password' );
$button_text = get_option( 'sac_password_button_text', 'Unlock Site' );
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

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unlock Staging | <?php bloginfo( 'name' ); ?></title>
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
            margin-bottom: 30px;
        }

        .password-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        .password-input {
            width: 100%;
            padding: 15px 20px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            outline: none;
            transition: all 0.3s ease;
            text-align: center;
        }

        .password-input:focus {
            border-color: var(--accent-color);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 4px var(--glow);
        }

        .btn {
            display: inline-block;
            background: var(--accent-color);
            color: #fff;
            text-decoration: none;
            padding: 15px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px 0 var(--glow);
            position: relative;
            overflow: hidden;
            border: none;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
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

        .error-message {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: <?php echo isset( $sac_password_error ) && $sac_password_error ? 'block' : 'none'; ?>;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
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
        
        <div class="error-message">Incorrect password. Please try again.</div>

        <div class="message">
            <?php echo wp_kses_post( wpautop( $message ) ); ?>
        </div>

        <form method="post" action="" class="password-form">
            <input type="password" name="sac_bypass_password_input" class="password-input" placeholder="<?php echo esc_attr( $placeholder ); ?>" required autofocus>
            <?php wp_nonce_field( 'sac_bypass_nonce', 'sac_bypass_nonce_field' ); ?>
            <button type="submit" class="btn"><?php echo esc_html( $button_text ); ?></button>
        </form>
    </div>
</body>
</html>
