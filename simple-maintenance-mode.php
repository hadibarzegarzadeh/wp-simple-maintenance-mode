<?php
/**
 * Plugin Name: Simple Maintenance Mode
 * Description: Shows a maintenance page to visitors while logged-in administrators keep full access.
 * Version: 1.0.0
 * Author: hadibarzegarzadeh
 * License: GPL-2.0-or-later
 * Text Domain: simple-maintenance-mode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function smm_is_enabled() {
    return (bool) get_option( 'smm_enabled', 0 );
}

add_action( 'template_redirect', function () {
    if ( ! smm_is_enabled() || current_user_can( 'manage_options' ) ) {
        return;
    }
    $message = get_option( 'smm_message', 'We are performing scheduled maintenance. Please check back soon.' );
    wp_die( esc_html( $message ), esc_html__( 'Maintenance', 'simple-maintenance-mode' ), array( 'response' => 503 ) );
} );

add_action( 'admin_menu', function () {
    add_options_page( 'Maintenance Mode', 'Maintenance Mode', 'manage_options', 'smm-settings', 'smm_settings_page' );
} );

add_action( 'admin_init', function () {
    register_setting( 'smm_group', 'smm_enabled', array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'smm_group', 'smm_message', array( 'sanitize_callback' => 'sanitize_text_field' ) );
} );

function smm_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Maintenance Mode', 'simple-maintenance-mode' ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'smm_group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable', 'simple-maintenance-mode' ); ?></th>
                    <td><input type="checkbox" name="smm_enabled" value="1" <?php checked( 1, get_option( 'smm_enabled', 0 ) ); ?> /></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Message', 'simple-maintenance-mode' ); ?></th>
                    <td><input type="text" class="regular-text" name="smm_message" value="<?php echo esc_attr( get_option( 'smm_message', '' ) ); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
