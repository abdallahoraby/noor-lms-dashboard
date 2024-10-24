<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add File Restriction option to Content Restriction settings
 *
 */
class PMS_Files_Restriction{

    public function __construct(){

        add_action( 'pms_content_restriction_extra_settings', array( $this, 'pms_file_restriction_options_add' ), 10, 1 );
        add_action('update_option_pms_content_restriction_settings', array( $this, 'pms_file_restriction_options_save' ), 10, 3 );
//        add_filter('mod_rewrite_rules', array( $this, 'pms_file_restriction_create_htaccess_rule' ) );
        add_filter('mod_rewrite_rules', array( $this, 'pms_file_restriction_insert_htaccess_rule' ) );
    }

    /**
     * Detect web_server in use
     *
     */
    public static function pms_get_web_server() {

        if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {

            $server_software = strtolower( sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) );

            if ( strpos( $server_software, 'apache' ) !== false ) {
                return 'apache';
            } elseif ( strpos( $server_software, 'nginx' ) !== false ) {
                return 'nginx';
            } else {
                return 'unknown';
            }

        }

        return 'unknown';
    }

    public function pms_file_restriction_options_add( $pms_content_restriction_settings ){
        if( !empty( $pms_content_restriction_settings['file_restriction'] ) && $pms_content_restriction_settings['file_restriction'] === 'yes' ){
            $file_restriction_activated = 'yes';
        }
        else $file_restriction_activated = 'no';

        $web_server = ucfirst( $this->pms_get_web_server() );
        ?>

        <!-- File Restriction -->
        <h4 class="cozmoslabs-subsection-title" style="margin-top: 20px !important;"><?php esc_html_e( 'Files Restriction', 'paid-member-subscriptions' ); ?></h4>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="restricted-file-types"><?php esc_html_e( 'Web Server', 'paid-member-subscriptions' ); ?></label>
            <p class="cozmoslabs-description"><?php echo esc_html( ucfirst( $this->pms_get_web_server() ) ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="file-restriction-enable"><?php esc_html_e( 'Protect ALL File Types', 'paid-member-subscriptions' ); ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" name="pms_content_restriction_settings[file_restriction]" id="file-restriction-enable" value="yes" <?php echo ( $file_restriction_activated == 'yes' ) ? 'checked' : ''; ?> >
                <label class="cozmoslabs-toggle-track" for="file-restriction-enable"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="file-restriction-enable" class="cozmoslabs-description"><?php printf( esc_html__( 'Protect every file type from your Media Library. This option can cause performance issues, enable at your own risk. %1$sLearn more.%2$s', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/files-restriction/#Protect_All_File_Types" target="_blank">', '</a>'); ?></label>
            </div>
        </div>

        <!-- Restricted File Types -->
        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="restricted-file-types"><?php esc_html_e( 'Protected File Types', 'paid-member-subscriptions' ); ?></label>
                <div id="pms-restricted-file-types" style="flex-basis: 260px">
                    <select name="pms_content_restriction_settings[restricted_file_types][]" class="pms-chosen" id="restricted-file-types" multiple>
                            <?php

                            $default_restricted_files         = array( 'zip', 'gz', 'tar', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'xlsm', 'csv', 'pdf', 'mp4', 'm4v', 'mp3', 'ts', 'key', 'm3u8' );
                            $pms_content_restriction_settings = get_option( 'pms_content_restriction_settings', 'not_found' );
                            $selected_file_types              = !empty( $pms_content_restriction_settings['restricted_file_types'] ) ? $pms_content_restriction_settings['restricted_file_types'] : $default_restricted_files;

                            // create the file extensions list for the Restricted File Types selector (include any extra extensions added by users)
                            $file_types_list = array_merge( $default_restricted_files, $selected_file_types );
                            $file_types_list = array_unique( $file_types_list );
                            $file_types_list = array_values( $file_types_list );

                            foreach ( $file_types_list as $file_type ) {
                                echo '<option value="'. esc_attr( $file_type ) .'"' . ( ( !empty( $selected_file_types )  && in_array( $file_type, $selected_file_types ) ) ? ' selected' : '' ) . '>' . esc_html( $file_type ) . '</option>';
                            }

                            ?>
                    </select>
                </div>
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Choose what type of files you would like to protect. New file types that should be restricted can be created by typing them.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <!-- Additional Information about Nginx -->
    <?php
        if( $web_server == 'Nginx' ){
        ?>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="restricted-file-types"><?php esc_html_e( 'Nginx Configuration', 'paid-member-subscriptions' ); ?></label>
            <div class="cozmoslabs-description cozmoslabs-description-align-right" style="position: relative; padding-left: 0; cursor: copy;">
                <div title='Click to copy' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input" style="overflow-wrap: anywhere;"><?php echo
                    '# Paid Member Subscriptions PRO: Files Restriction <br>
                     include ' . esc_html( plugin_dir_path(__FILE__) ) . 'file-restriction-nginx.conf;'; ?>
                </div>
                <span style="display: none; position: absolute; top: 5px; right: 10px; font-weight: bold;" class='pms-copy-message'><?php esc_html_e( 'Nginx include directive copied!', 'paid-member-subscriptions' ); ?></span>
            </div>
            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <?php esc_html_e( 'This code snippet needs to be added into the Nginx configuration file on your server, for the File Restriction feature to work correctly. After the code snippet was inserted, the Nginx web-server also needs to be restarted.', 'paid-member-subscriptions' ); ?>
            </p>
            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <?php esc_html_e( 'NOTE: If the restriction settings get changed, the Nginx web-server needs to be restarted for the new rules to take effect.', 'paid-member-subscriptions' ); ?>
            </p>
        </div>
        <?php
        }

    // check if the user disabled the rewrite process for the File Restriction .htaccess rules
    $rewrite_rules_enabled = apply_filters( 'pms_file_restriction_rewrite_apache_rule', true );

    if ( $web_server === 'Apache' && ( !is_writable( ABSPATH . '.htaccess' ) || !$rewrite_rules_enabled ) ):
        ?>

        <!-- Apache Information -->
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="restrict-all-files-enable"><?php esc_html_e( 'Apache Configuration', 'paid-member-subscriptions' ); ?></label>

            <div style="flex-basis: 250px; flex-grow: 1; position: relative; padding-left: 0; cursor: copy;">
                <div title='Click to copy' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input" style="overflow-wrap: anywhere;">
                    <?php echo nl2br( esc_html( $this->pms_file_restriction_create_htaccess_rule('') ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <span style='display: none; position: absolute; top: 5px; right: 10px;' class='pms-copy-message'><strong><?php esc_html_e( 'Apache directive copied!', 'paid-member-subscriptions' ); ?></strong></span>
            </div>

            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <?php esc_html_e( 'It appears that the .htaccess file in your WordPress root directory is not writable.', 'paid-member-subscriptions' ); ?>
                <br>
                <?php esc_html_e( 'This means that WordPress is unable to automatically update the file with necessary rules or changes.', 'paid-member-subscriptions' ); ?>
                <br>
                <?php esc_html_e( 'To fix this issue follow the steps bellow.', 'paid-member-subscriptions' ); ?>
            </p>

            <?php if ( !$rewrite_rules_enabled ): ?>
                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php printf( esc_html__( '%1$sNOTE:%2$s The %3$s filter hook is preventing File Restriction rules from being written to the %4$s file!', 'paid-member-subscriptions' ), '<strong>', '</strong>', '<em>pms_file_restriction_rewrite_apache_rule</em>', '<em>.htaccess</em>' ); ?>
                </p>
            <?php endif; ?>


            <div class="cozmoslabs-description cozmoslabs-description-space-left">

                <p><?php esc_html_e( 'Check File Permissions:', 'paid-member-subscriptions' ); ?></p>
                <ul style="padding-left: 40px; list-style-type: square;">
                    <li><?php esc_html_e( 'Using FTP/SFTP:', 'paid-member-subscriptions' ); ?>
                        <ul style="padding-left: 40px; list-style-type: square;">
                            <li><?php esc_html_e( 'Connect to your server using an FTP/SFTP client.', 'paid-member-subscriptions' ); ?></li>
                            <li><?php esc_html_e( 'Navigate to the root directory of your WordPress installation (where the .htaccess file is located).', 'paid-member-subscriptions' ); ?></li>
                            <li><?php esc_html_e( 'Right-click on the .htaccess file and select "File Permissions" or "Properties."', 'paid-member-subscriptions' ); ?></li>
                            <li><?php esc_html_e( 'Ensure the file permissions are set to 644 or 664. This allows the file to be readable and writable by the owner, and readable by the group and others.', 'paid-member-subscriptions' ); ?></li>
                        </ul>
                    </li>
                    <li><?php esc_html_e( 'Using Hosting Control Panel:', 'paid-member-subscriptions' ); ?>
                        <ul style="padding-left: 40px; list-style-type: square;">
                            <li><?php esc_html_e( 'Log in to your hosting control panel (e.g., cPanel, Plesk).', 'paid-member-subscriptions' ); ?></li>
                            <li><?php esc_html_e( 'Open the File Manager and navigate to the root directory of your WordPress installation.', 'paid-member-subscriptions' ); ?></li>
                            <li><?php esc_html_e( 'Find the .htaccess file and check its permissions. Set them to 644 or 664 if necessary.', 'paid-member-subscriptions' ); ?></li>
                        </ul>
                    </li>
                </ul>

                <p><?php esc_html_e( 'Manually Edit .htaccess:', 'paid-member-subscriptions' ); ?></p>
                <ul style="padding-left: 40px; list-style-type: square;">
                    <li><?php esc_html_e( 'The .htaccess file is located in the root directory of your WordPress installation.', 'paid-member-subscriptions' ); ?></li>
                    <li><?php esc_html_e( 'Use a text editor to open the .htaccess file. Ensure you back up the file before making changes.', 'paid-member-subscriptions' ); ?></li>
                    <li><?php esc_html_e( 'Include the directive provided above.', 'paid-member-subscriptions' ); ?></li>
                </ul>

            </div>

            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <?php esc_html_e( 'If you are unsure about making these changes or if the issue persists, please contact the support team of your hosting provider for assistance.', 'paid-member-subscriptions' ); ?>
            </p>

        </div>

    <?php endif;
    }

    /**
     * Handle File Restriction option save
     *
     * - update File Restriction option
     * - rewrite .htaccess rules accordingly
     *
     */

    public function pms_file_restriction_options_save( $old_data, $new_data, $meta_key ){
        $rewrite_rules = false;

        if ( isset( $new_data['file_restriction'] ) ) {
            if ( !isset( $old_data['file_restriction'] ) || $old_data['file_restriction'] !== $new_data['file_restriction'] ) {
                $rewrite_rules = true;
            }
        } elseif ( isset( $old_data['file_restriction'] ) && $old_data['file_restriction'] === 'yes' ) {
            $rewrite_rules = true;
        }


        if ( isset( $new_data['restricted_file_types'] ) ) {
            if ( !isset( $old_data['restricted_file_types'] ) || $old_data['restricted_file_types'] !== $new_data['restricted_file_types'] ) {
                $rewrite_rules = true;
            }
        } elseif ( isset( $old_data['restricted_file_types'] ) ) {
            $rewrite_rules = true;
        }

        if ( $rewrite_rules ) {
            $web_server = $this->pms_get_web_server();

            if ( $web_server === 'apache' ) {
                // reload the .htaccess file to update the File Restriction rules
                flush_rewrite_rules();
            }
            elseif ( $web_server === 'nginx' ) {
                // regenerate the File Restriction nginx config file
                $this->pms_file_restriction_rewrite_nginx_rules();
            }
        }
    }

    /** APACHE  */
    /**
     * Create .htaccess file rules according to the File Restriction settings
     *
     */
    public function pms_file_restriction_create_htaccess_rule( $rules ) {

        // get WordPress root directory
        $wp_root = basename(ABSPATH);
        $file_path = '/'. $wp_root . '/wp-content/plugins/' . basename(PMS_PAID_PLUGIN_DIR) . '/add-ons-pro/files-restriction/includes/file-restriction-engine.php';

        $pms_content_restriction_settings = get_option( 'pms_content_restriction_settings', 'not_found' );
        $pms_add_ons_settings = get_option( 'pms_add_ons_settings', array() );
        $slug = 'pms-add-on-files-restriction/index.php';
        $htaccess_rule = '';

        if ( !empty( $pms_add_ons_settings[$slug] ) &&  $pms_add_ons_settings[$slug] == true ) {
            if ( !empty( $pms_content_restriction_settings['file_restriction'] ) && $pms_content_restriction_settings['file_restriction'] === 'yes' ) {
                $htaccess_rule = <<<EOD

                          # Paid Member Subscriptions PRO: Files Restriction
                          <IfModule mod_rewrite.c>
                              RewriteBase /
                              RewriteRule ^wp-content/uploads/(.*)$ $file_path [L]
                          </IfModule>

                          EOD;
            }
            elseif ( !empty( $content_restriction_settings['restricted_file_types'] ) ) {
                $counter              = 0;
                $number_of_extensions = !empty( $pms_content_restriction_settings['restricted_file_types'] ) ? count( $pms_content_restriction_settings['restricted_file_types'] ) : 0;
                $file_types_rule      = '';

                foreach ( $pms_content_restriction_settings['restricted_file_types'] as $file_type ) {
                    $counter++;
                    $file_types_rule .= '\.' . $file_type;

                    if ( $counter < $number_of_extensions ) {
                        $file_types_rule = $file_types_rule . '|';
                    }
                }

                if ( !empty( $file_types_rule ) ) {
                    $htaccess_rule = <<<EOD
                              
                              # Paid Member Subscriptions PRO: File Restriction
                              <IfModule mod_rewrite.c>
                                  RewriteBase /
                                  RewriteRule ^wp-content/uploads/.*($file_types_rule)$ $file_path [L]
                              </IfModule>

                              EOD;
                }
            }
        }

        return $rules . $htaccess_rule;
    }

    /**
     * Update .htaccess file rules
     *
     */
    public function pms_file_restriction_insert_htaccess_rule( $rules ) {

        // check if the user disabled the rewrite process for the File Restriction .htaccess rules
        $rewrite_rules_enabled = apply_filters( 'pms_file_restriction_rewrite_apache_rule', true );

        if ( !$rewrite_rules_enabled ) {
            return $rules;
        }

        return $this->pms_file_restriction_create_htaccess_rule( $rules );
    }

    /** NGINX  */

    /**
     * Generate Nginx rules based on File Restriction settings.
     *
     * @return string The Nginx configuration rules.
     */
    public function pms_file_restriction_generate_nginx_rules() {
        $content_restriction_settings = get_option('pms_content_restriction_settings', 'not_found');
        $files_restriction_add_on_active = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-files-restriction/index.php' );
        $nginx_rule = '';

        if ( $files_restriction_add_on_active ) {
            if ( !empty( $content_restriction_settings['file_restriction'] ) && $content_restriction_settings['file_restriction'] === 'yes' ) {
                $nginx_rule = <<<EOD
                            # Paid Member Subscriptions PRO: File Restriction
                            location ~* ^/wp-content/uploads/(.*)$ {
                                rewrite ^/wp-content/uploads/(.*)$ /wp-content/plugins/paid-member-subscriptions-dev/add-ons-pro/files-restriction/includes/file-restriction-engine.php last;
                            }
                            EOD;
            } elseif ( !empty( $content_restriction_settings['restricted_file_types'] ) ) {
                $counter = 0;
                $number_of_extensions = count( $content_restriction_settings['restricted_file_types'] );
                $file_types_rule = '';

                foreach ( $content_restriction_settings['restricted_file_types'] as $file_type ) {
                    $counter++;
                    $file_types_rule .= $file_type;

                    if ( $counter < $number_of_extensions ) {
                        $file_types_rule .= '|';
                    }
                }

                if ( !empty( $file_types_rule ) ) {
                    $nginx_rule = <<<EOD
                                # Paid Member Subscriptions PRO: File Restriction
                                location ~* ^/wp-content/uploads/.*\.($file_types_rule)$ {
                                    rewrite ^/wp-content/uploads/.*\.($file_types_rule)$ /wp-content/plugins/paid-member-subscriptions-dev/add-ons-pro/files-restriction/includes/file-restriction-engine.php last;
                                }
                                EOD;
                }
            }
        }

        return $nginx_rule;
    }

    /**
     * Rewrite Nginx rules to the configuration file
     *
     */
    public function pms_file_restriction_rewrite_nginx_rules() {
        $nginx_rules = $this->pms_file_restriction_generate_nginx_rules();

        // File Restriction nginx config file path
        $nginx_conf_file_path = plugin_dir_path(__FILE__) . 'file-restriction-nginx.conf';

        // Write the File Restriction nginx rules
        file_put_contents( $nginx_conf_file_path, $nginx_rules );

        // Reset the admin notice informing the user to restart the Nginx web server (File Restriction rules have been updated)
        delete_user_meta( get_current_user_id(), 'pms_file_restriction_nginx_restart_dismiss_notification' );

        // Remove 'pms_dismiss_admin_notification' argument from url for the Nginx restart notification to show up again when needed
        // --> in case the File Restriction options get to be changed and saved again, right after the Nginx restart notification was dismissed and the "pms_dismiss_admin_notification" is still present, the notice remains dismissed and will not show up again
        if ( isset( $_POST['_wp_http_referer'] ) && strpos( sanitize_text_field( $_POST['_wp_http_referer'] ), 'pms_dismiss_admin_notification=pms_file_restriction_nginx_restart' ) !== false ) {
            $redirect_url = remove_query_arg( 'pms_dismiss_admin_notification', sanitize_text_field( $_POST['_wp_http_referer'] ) );
            wp_safe_redirect( $redirect_url );
            exit();
        }
    }
}
new PMS_Files_Restriction();