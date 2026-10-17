<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_menu',
	function () {
		add_menu_page(
			__( 'Maintenance Mode', 'simple-maintenance-mode' ),
			__( 'Maintenance', 'simple-maintenance-mode' ),
			'manage_options',
			'smm-settings',
			'smm_settings_page',
			'dashicons-hammer',
			80
		);
	}
);

add_action(
	'admin_init',
	function () {
		register_setting( 'smm_group', 'smm_enabled', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'smm_group', 'smm_page_id', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'smm_group', 'smm_message', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}
);

add_action( 'admin_post_smm_create_page', 'smm_handle_create_page' );

function smm_handle_create_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'simple-maintenance-mode' ) );
	}
	check_admin_referer( 'smm_create_page' );

	$post_id = wp_insert_post(
		array(
			'post_type'   => SMM_CPT,
			'post_status' => 'draft',
			'post_title'  => __( 'Maintenance Page', 'simple-maintenance-mode' ),
		)
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		wp_die( esc_html__( 'Could not create the page.', 'simple-maintenance-mode' ) );
	}

	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_wp_page_template', 'elementor_canvas' );
		wp_safe_redirect( admin_url( 'post.php?post=' . $post_id . '&action=elementor' ) );
	} else {
		wp_safe_redirect( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) );
	}
	exit;
}

add_action( 'admin_post_smm_toggle', 'smm_handle_toggle' );

function smm_handle_toggle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'simple-maintenance-mode' ) );
	}
	check_admin_referer( 'smm_toggle' );

	update_option( 'smm_enabled', get_option( 'smm_enabled', 0 ) ? 0 : 1 );

	$back = wp_get_referer();
	if ( ! $back ) {
		$back = admin_url( 'admin.php?page=smm-settings' );
	}
	wp_safe_redirect( $back );
	exit;
}

add_action(
	'admin_bar_menu',
	function ( $bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$on  = (int) get_option( 'smm_enabled', 0 );
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=smm_toggle' ), 'smm_toggle' );
		$bar->add_node(
			array(
				'id'    => 'smm-status',
				'title' => $on ? esc_html__( 'Maintenance: ON (click to turn off)', 'simple-maintenance-mode' ) : esc_html__( 'Maintenance: OFF (click to turn on)', 'simple-maintenance-mode' ),
				'href'  => $url,
				'meta'  => array( 'class' => $on ? 'smm-on' : 'smm-off' ),
			)
		);
	},
	100
);

add_action(
	'admin_head',
	function () {
		echo '<style>#wp-admin-bar-smm-status.smm-on>a{background:#d63638!important;color:#fff!important}</style>';
	}
);

add_action(
	'wp_head',
	function () {
		if ( is_admin_bar_showing() ) {
			echo '<style>#wp-admin-bar-smm-status.smm-on>a{background:#d63638!important;color:#fff!important}</style>';
		}
	}
);
