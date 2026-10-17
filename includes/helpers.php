<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
