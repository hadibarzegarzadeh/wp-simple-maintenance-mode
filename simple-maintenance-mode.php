<?php
/**
 * Plugin Name: Simple Maintenance Mode
 * Description: Shows a maintenance page to visitors while administrators keep full access. Design multiple maintenance pages with Elementor and choose which one is displayed.
 * Version: 2.0.0
 * Author: hadibarzegarzadeh
 * License: GPL-2.0-or-later
 * Text Domain: simple-maintenance-mode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SMM_CPT', 'smm_page' );

add_action( 'init', 'smm_register_cpt' );

function smm_register_cpt() {
	register_post_type(
		SMM_CPT,
		array(
			'labels'              => array(
				'name'               => __( 'Maintenance Pages', 'simple-maintenance-mode' ),
				'singular_name'      => __( 'Maintenance Page', 'simple-maintenance-mode' ),
				'add_new'            => __( 'Add New', 'simple-maintenance-mode' ),
				'add_new_item'       => __( 'Add New Maintenance Page', 'simple-maintenance-mode' ),
				'edit_item'          => __( 'Edit Maintenance Page', 'simple-maintenance-mode' ),
				'all_items'          => __( 'All Maintenance Pages', 'simple-maintenance-mode' ),
				'not_found'          => __( 'No maintenance pages found.', 'simple-maintenance-mode' ),
			),
			'public'              => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'smm-settings',
			'show_in_rest'        => true,
			'has_archive'         => false,
			'rewrite'             => array( 'slug' => 'maintenance-page' ),
			'supports'            => array( 'title', 'editor', 'thumbnail', 'elementor' ),
			'capability_type'     => 'page',
		)
	);

	smm_enable_elementor_support();
}

function smm_enable_elementor_support() {
	if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
		return;
	}
	$types = get_option( 'elementor_cpt_support' );
	if ( false === $types ) {
		$types = array( 'post', 'page' );
	}
	if ( is_array( $types ) && ! in_array( SMM_CPT, $types, true ) ) {
		$types[] = SMM_CPT;
		update_option( 'elementor_cpt_support', $types );
	}
}

add_action(
	'wp_insert_post',
	function ( $post_id, $post, $update ) {
		if ( ! $update && SMM_CPT === $post->post_type && defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'elementor_canvas' );
		}
	},
	10,
	3
);

function smm_is_elementor_page( $post_id ) {
	return defined( 'ELEMENTOR_VERSION' ) && class_exists( '\Elementor\Plugin' ) && 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
}

function smm_get_active_page() {
	$page_id = (int) get_option( 'smm_page_id', 0 );
	if ( ! $page_id ) {
		return null;
	}
	$page = get_post( $page_id );
	if ( $page && SMM_CPT === $page->post_type && 'publish' === $page->post_status ) {
		return $page;
	}
	return null;
}

add_action( 'template_redirect', 'smm_maybe_show_maintenance', 0 );

function smm_maybe_show_maintenance() {
	if ( ! get_option( 'smm_enabled', 0 ) ) {
		return;
	}
	if ( current_user_can( 'manage_options' ) || is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	$page = smm_get_active_page();

	status_header( 503 );
	nocache_headers();
	header( 'Retry-After: 3600' );

	if ( ! $page ) {
		$message = get_option( 'smm_message', '' );
		if ( '' === $message ) {
			$message = __( 'We are performing scheduled maintenance. Please check back soon.', 'simple-maintenance-mode' );
		}
		wp_die( esc_html( $message ), esc_html__( 'Maintenance', 'simple-maintenance-mode' ), array( 'response' => 503 ) );
	}

	if ( smm_is_elementor_page( $page->ID ) ) {
		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $page->ID, true );
	} else {
		$content = apply_filters( 'the_content', $page->post_content );
	}

	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( get_the_title( $page ) ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'smm-maintenance' ); ?>>
	<?php wp_body_open(); ?>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- trusted admin-authored content. ?>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
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

	if ( is_wp_error( $post_id ) ) {
		wp_die( esc_html( $post_id->get_error_message() ) );
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

add_action(
	'admin_bar_menu',
	function ( $bar ) {
		if ( ! get_option( 'smm_enabled', 0 ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'smm-status',
				'title' => esc_html__( 'Maintenance mode is ON', 'simple-maintenance-mode' ),
				'href'  => admin_url( 'admin.php?page=smm-settings' ),
			)
		);
	},
	100
);

function smm_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$pages = get_posts(
		array(
			'post_type'      => SMM_CPT,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$selected     = (int) get_option( 'smm_page_id', 0 );
	$has_elementor = defined( 'ELEMENTOR_VERSION' );
	$create_url   = wp_nonce_url( admin_url( 'admin-post.php?action=smm_create_page' ), 'smm_create_page' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Maintenance Mode', 'simple-maintenance-mode' ); ?></h1>

		<?php if ( ! $has_elementor ) : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'Elementor is not active. Install and activate Elementor to design maintenance pages visually. Until then the classic editor is used.', 'simple-maintenance-mode' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'smm_group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable maintenance mode', 'simple-maintenance-mode' ); ?></th>
					<td><input type="checkbox" name="smm_enabled" value="1" <?php checked( 1, get_option( 'smm_enabled', 0 ) ); ?> /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Page shown to visitors', 'simple-maintenance-mode' ); ?></th>
					<td>
						<select name="smm_page_id">
							<option value="0"><?php esc_html_e( 'Default message (no page)', 'simple-maintenance-mode' ); ?></option>
							<?php foreach ( $pages as $page ) : ?>
								<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $selected, $page->ID ); ?> <?php disabled( 'publish' !== $page->post_status ); ?>>
									<?php echo esc_html( $page->post_title . ( 'publish' === $page->post_status ? '' : ' (' . $page->post_status . ')' ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Only published pages can be selected. Publish a page from the Elementor editor first.', 'simple-maintenance-mode' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Fallback message', 'simple-maintenance-mode' ); ?></th>
					<td><input type="text" class="regular-text" name="smm_message" value="<?php echo esc_attr( get_option( 'smm_message', '' ) ); ?>" /></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'Your maintenance pages', 'simple-maintenance-mode' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( $create_url ); ?>" class="button button-primary">
				<?php echo $has_elementor ? esc_html__( 'Create new page with Elementor', 'simple-maintenance-mode' ) : esc_html__( 'Create new page', 'simple-maintenance-mode' ); ?>
			</a>
		</p>

		<table class="widefat striped" style="max-width:800px">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'simple-maintenance-mode' ); ?></th>
					<th><?php esc_html_e( 'Status', 'simple-maintenance-mode' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'simple-maintenance-mode' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $pages ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No maintenance pages yet.', 'simple-maintenance-mode' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $pages as $page ) : ?>
					<tr>
						<td>
							<?php echo esc_html( $page->post_title ); ?>
							<?php if ( (int) $page->ID === $selected ) : ?>
								<strong>(<?php esc_html_e( 'selected', 'simple-maintenance-mode' ); ?>)</strong>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $page->post_status ); ?></td>
						<td>
							<?php if ( $has_elementor ) : ?>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $page->ID . '&action=elementor' ) ); ?>"><?php esc_html_e( 'Edit with Elementor', 'simple-maintenance-mode' ); ?></a> |
							<?php endif; ?>
							<a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php esc_html_e( 'Edit', 'simple-maintenance-mode' ); ?></a> |
							<a href="<?php echo esc_url( get_delete_post_link( $page->ID ) ); ?>"><?php esc_html_e( 'Trash', 'simple-maintenance-mode' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
