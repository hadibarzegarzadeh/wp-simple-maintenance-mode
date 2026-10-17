<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
	<?php echo $content; ?>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
	exit;
}
