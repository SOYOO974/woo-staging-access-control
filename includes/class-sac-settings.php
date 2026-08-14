<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SAC_Settings {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    public function add_settings_page() {
        add_options_page(
            'Staging Access Control',
            'Staging Access Control',
            'manage_options',
            'staging-access-control',
            array( $this, 'render_settings_page' )
        );
    }

    public function enqueue_admin_scripts( $hook_suffix ) {
        if ( 'settings_page_staging-access-control' !== $hook_suffix ) {
            return;
        }
        wp_enqueue_media(); // Required for the custom logo uploader
    }

    public function register_settings() {
        // General Tab
        register_setting( 'sac_options_group', 'sac_production_url', 'esc_url_raw' );
        register_setting( 'sac_options_group', 'sac_button_text', 'sanitize_text_field' );
        register_setting( 'sac_options_group', 'sac_login_button_text', 'sanitize_text_field' );
        register_setting( 'sac_options_group', 'sac_button_color', 'sanitize_hex_color' );
        register_setting( 'sac_options_group', 'sac_heading_text', 'sanitize_text_field' );
        register_setting( 'sac_options_group', 'sac_maintenance_message', 'wp_kses_post' );
        register_setting( 'sac_options_group', 'sac_ip_whitelist', 'sanitize_textarea_field' );
        register_setting( 'sac_options_group', 'sac_custom_logo', 'absint' );
        register_setting( 'sac_options_group', 'sac_logo_max_width', 'absint' );

        // Password Page Tab
        register_setting( 'sac_password_options_group', 'sac_bypass_param', 'sanitize_text_field' );
        register_setting( 'sac_password_options_group', 'sac_bypass_password', 'sanitize_text_field' );
        register_setting( 'sac_password_options_group', 'sac_password_heading_text', 'sanitize_text_field' );
        register_setting( 'sac_password_options_group', 'sac_password_message', 'wp_kses_post' );
        register_setting( 'sac_password_options_group', 'sac_password_placeholder', 'sanitize_text_field' );
        register_setting( 'sac_password_options_group', 'sac_password_button_text', 'sanitize_text_field' );

        // Updates Tab
        register_setting( 'sac_updates_options_group', 'sac_github_token', 'sanitize_text_field' );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        ?>
        <style>
            .sac-wrap {
                max-width: 800px;
                margin: 20px 0;
                background: #fff;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.05);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .sac-form-table {
                width: 100%;
                border-collapse: collapse;
            }
            .sac-form-table th {
                width: 250px;
                padding: 20px 0;
                font-weight: 600;
                color: #2c3338;
                vertical-align: top;
            }
            .sac-form-table td {
                padding: 20px 0;
            }
            .sac-input-text, .sac-textarea {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid #dcdcde;
                border-radius: 6px;
                font-size: 14px;
                box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }
            .sac-input-text:focus, .sac-textarea:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
            }
            .sac-textarea {
                min-height: 120px;
            }
            .sac-button-primary {
                background: #2271b1;
                color: #fff;
                border: none;
                padding: 10px 24px;
                font-size: 15px;
                font-weight: 600;
                border-radius: 6px;
                cursor: pointer;
                transition: background 0.2s ease;
            }
            .sac-button-primary:hover {
                background: #135e96;
            }
            .sac-logo-preview {
                margin-top: 15px;
                max-width: 200px;
                border: 1px dashed #dcdcde;
                padding: 10px;
                border-radius: 6px;
                display: none;
            }
            .sac-logo-preview img {
                max-width: 100%;
                height: auto;
                display: block;
            }
            .sac-help-text {
                font-size: 13px;
                color: #646970;
                margin-top: 8px;
                display: block;
            }
        </style>

        <div class="wrap">
            <h1>Staging Access Control</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=staging-access-control&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
                <a href="?page=staging-access-control&tab=password" class="nav-tab <?php echo $active_tab == 'password' ? 'nav-tab-active' : ''; ?>">Password Page</a>
                <a href="?page=staging-access-control&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
                <a href="?page=staging-access-control&tab=updates" class="nav-tab <?php echo $active_tab == 'updates' ? 'nav-tab-active' : ''; ?>">Updates</a>
            </h2>

            <?php
            if ( $active_tab == 'general' ) {
                $this->render_general_tab();
            } else if ( $active_tab == 'password' ) {
                $this->render_password_tab();
            } else if ( $active_tab == 'logs' ) {
                $this->render_logs_tab();
            } else if ( $active_tab == 'updates' ) {
                $this->render_updates_tab();
            }
            ?>
        </div>
        <?php
    }

    private function render_general_tab() {
        ?>
        <div class="sac-wrap">
            <form method="post" action="options.php">
                <?php settings_fields( 'sac_options_group' ); ?>
                <table class="sac-form-table">
                    <tr>
                        <th scope="row"><label for="sac_production_url">Production URL</label></th>
                        <td>
                            <input type="url" name="sac_production_url" id="sac_production_url" class="sac-input-text" value="<?php echo esc_attr( get_option( 'sac_production_url' ) ); ?>" placeholder="https://production-site.com" />
                            <span class="sac-help-text">Visitors will be redirected here if they are not allowed access.</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_button_text">Button Text</label></th>
                        <td>
                            <input type="text" name="sac_button_text" id="sac_button_text" class="sac-input-text" value="<?php echo esc_attr( get_option( 'sac_button_text', 'Go to Production Site' ) ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_login_button_text">Login Button Text</label></th>
                        <td>
                            <input type="text" name="sac_login_button_text" id="sac_login_button_text" class="sac-input-text" value="<?php echo esc_attr( get_option( 'sac_login_button_text', 'Staging Login' ) ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_button_color">Button Color</label></th>
                        <td>
                            <input type="color" name="sac_button_color" id="sac_button_color" value="<?php echo esc_attr( get_option( 'sac_button_color', '#3b82f6' ) ); ?>" style="cursor: pointer; padding: 0; height: 35px; width: 60px;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_heading_text">Heading Text</label></th>
                        <td>
                            <input type="text" name="sac_heading_text" id="sac_heading_text" class="sac-input-text" value="<?php echo esc_attr( get_option( 'sac_heading_text', 'Staging Environment' ) ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_maintenance_message">Maintenance Message</label></th>
                        <td>
                            <?php 
                            $message = get_option( 'sac_maintenance_message', "This staging site is currently restricted." );
                            wp_editor( $message, 'sac_maintenance_message', array(
                                'textarea_name' => 'sac_maintenance_message',
                                'textarea_rows' => 6,
                                'media_buttons' => false,
                                'teeny' => true,
                                'editor_class' => 'sac-textarea'
                            ) );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Custom Logo</label></th>
                        <td>
                            <?php
                            $logo_id = get_option( 'sac_custom_logo' );
                            $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
                            ?>
                            <input type="hidden" name="sac_custom_logo" id="sac_custom_logo" value="<?php echo esc_attr( $logo_id ); ?>" />
                            <button type="button" class="button" id="sac_upload_logo_btn"><?php echo $logo_id ? 'Change Logo' : 'Upload Logo'; ?></button>
                            <?php if ( $logo_id ) : ?>
                                <button type="button" class="button" id="sac_remove_logo_btn">Remove</button>
                            <?php endif; ?>
                            
                            <div class="sac-logo-preview" id="sac_logo_preview" <?php echo $logo_id ? 'style="display:block;"' : ''; ?>>
                                <?php if ( $logo_url ) : ?>
                                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo Preview" />
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_logo_max_width">Logo Max Width (px)</label></th>
                        <td>
                            <input type="number" name="sac_logo_max_width" id="sac_logo_max_width" class="sac-input-text" style="width: 150px;" value="<?php echo esc_attr( get_option( 'sac_logo_max_width', '200' ) ); ?>" min="10" />
                            <span class="sac-help-text">Set the maximum width of the logo (e.g., 200). Default is 200.</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_ip_whitelist">IP Whitelist</label></th>
                        <td>
                            <textarea name="sac_ip_whitelist" id="sac_ip_whitelist" class="sac-textarea" placeholder="192.168.1.1&#10;10.0.0.1"><?php echo esc_textarea( get_option( 'sac_ip_whitelist' ) ); ?></textarea>
                            <span class="sac-help-text">Enter one IP address per line. These IPs will bypass the restriction.</span>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="submit" id="submit" class="sac-button-primary">Save Settings</button>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($){
            var mediaUploader;
            $('#sac_upload_logo_btn').click(function(e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'Choose Custom Logo',
                    button: {
                        text: 'Choose Logo'
                    },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#sac_custom_logo').val(attachment.id);
                    $('#sac_logo_preview').html('<img src="' + attachment.url + '" alt="Logo Preview" />').show();
                    $('#sac_upload_logo_btn').text('Change Logo');
                    if($('#sac_remove_logo_btn').length === 0) {
                        $('#sac_upload_logo_btn').after(' <button type="button" class="button" id="sac_remove_logo_btn">Remove</button>');
                    }
                });
                mediaUploader.open();
            });

            $(document).on('click', '#sac_remove_logo_btn', function(e){
                e.preventDefault();
                $('#sac_custom_logo').val('');
                $('#sac_logo_preview').hide().empty();
                $('#sac_upload_logo_btn').text('Upload Logo');
                $(this).remove();
            });
        });
        </script>
        <?php
    }

    private function render_password_tab() {
        ?>
        <div class="sac-wrap">
            <form method="post" action="options.php">
                <?php settings_fields( 'sac_password_options_group' ); ?>
                <table class="sac-form-table">
                    <tr>
                        <th scope="row"><label for="sac_bypass_param">Discreet Link Parameter</label></th>
                        <td>
                            <input type="text" name="sac_bypass_param" id="sac_bypass_param" class="sac-input-text" style="width: 150px;" value="<?php echo esc_attr( get_option( 'sac_bypass_param', 'unlock' ) ); ?>" />
                            <span class="sac-help-text">The secret word to add to your URL to see the password prompt (e.g., <code>?unlock=1</code>).</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_bypass_password">Access Password</label></th>
                        <td>
                            <input type="text" name="sac_bypass_password" id="sac_bypass_password" class="sac-input-text" style="width: 150px;" value="<?php echo esc_attr( get_option( 'sac_bypass_password', 'soyoo' ) ); ?>" />
                            <span class="sac-help-text">The password required to bypass the staging restriction. Default is <code>soyoo</code>.</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_password_heading_text">Heading Text</label></th>
                        <td>
                            <input type="text" name="sac_password_heading_text" id="sac_password_heading_text" class="sac-input-text" value="<?php echo esc_attr( get_option( 'sac_password_heading_text', 'Access Restricted' ) ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_password_message">Message</label></th>
                        <td>
                            <?php 
                            $password_message = get_option( 'sac_password_message', "Please enter the password to access this staging site." );
                            wp_editor( $password_message, 'sac_password_message', array(
                                'textarea_name' => 'sac_password_message',
                                'textarea_rows' => 6,
                                'media_buttons' => false,
                                'teeny' => true,
                                'editor_class' => 'sac-textarea'
                            ) );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_password_placeholder">Input Placeholder Text</label></th>
                        <td>
                            <input type="text" name="sac_password_placeholder" id="sac_password_placeholder" class="sac-input-text" value="<?php echo esc_attr( get_option( 'sac_password_placeholder', 'Enter password' ) ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sac_password_button_text">Button Text</label></th>
                        <td>
                            <input type="text" name="sac_password_button_text" id="sac_password_button_text" class="sac-input-text" value="<?php echo esc_attr( get_option( 'sac_password_button_text', 'Unlock Site' ) ); ?>" />
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="submit" id="submit" class="sac-button-primary">Save Settings</button>
                </p>
            </form>
        </div>
        <?php
    }

    private function render_logs_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sac_logs';
        
        // Handle clear logs action (completely separate form from options.php)
        if ( isset( $_POST['sac_clear_logs'] ) && check_admin_referer( 'sac_clear_logs_nonce' ) ) {
            $wpdb->query( "TRUNCATE TABLE $table_name" );
            echo '<div class="notice notice-success is-dismissible"><p>Logs cleared successfully.</p></div>';
        }

        // Pagination setup
        $per_page = 20;
        $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $paged - 1 ) * $per_page;

        // Fetch logs
        $logs = array();
        $total_items = 0;
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
            $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
            $logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
        }
        
        $total_pages = ceil( $total_items / $per_page );

        ?>
        <div style="margin-top: 20px;">
            <p style="display: flex; justify-content: space-between; align-items: center;">
                <span>Showing blocked access attempts. (<?php echo esc_html( $total_items ); ?> total)</span>
                <form method="post" style="display:inline-block;">
                    <?php wp_nonce_field( 'sac_clear_logs_nonce' ); ?>
                    <button type="submit" name="sac_clear_logs" class="button action" onclick="return confirm('Are you sure you want to clear all logs?');">Clear Logs</button>
                </form>
            </p>

            <?php 
            $page_links = paginate_links( array(
                'base' => add_query_arg( 'paged', '%#%' ),
                'format' => '',
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
                'total' => $total_pages,
                'current' => $paged
            ) );

            if ( $page_links ) : ?>
                <div class="tablenav top">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo esc_html( $total_items ); ?> items</span>
                        <span class="pagination-links"><?php echo $page_links; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 180px;">Timestamp</th>
                        <th style="width: 150px;">IP Address</th>
                        <th>Requested URL</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $logs ) ) : ?>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td><?php echo esc_html( $log->timestamp ); ?></td>
                                <td><?php echo esc_html( $log->ip_address ); ?></td>
                                <td><a href="<?php echo esc_url( $log->requested_url ); ?>" target="_blank"><?php echo esc_html( $log->requested_url ); ?></a></td>
                                <td><?php echo esc_html( $log->user_agent ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">No logs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $page_links ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo esc_html( $total_items ); ?> items</span>
                        <span class="pagination-links"><?php echo $page_links; ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_updates_tab() {
        ?>
        <div class="sac-wrap">
            <form method="post" action="options.php">
                <?php settings_fields( 'sac_updates_options_group' ); ?>
                <table class="sac-form-table">
                    <tr>
                        <th scope="row"><label for="sac_github_token">GitHub Personal Access Token</label></th>
                        <td>
                            <input type="password" name="sac_github_token" id="sac_github_token" class="sac-input-text" value="<?php echo esc_attr( get_option( 'sac_github_token', '' ) ); ?>" />
                            <span class="sac-help-text">Enter your GitHub Personal Access Token to allow automatic plugin updates from the private repository.</span>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="submit" id="submit" class="sac-button-primary">Save Settings</button>
                </p>
            </form>
        </div>
        <?php
    }
}
