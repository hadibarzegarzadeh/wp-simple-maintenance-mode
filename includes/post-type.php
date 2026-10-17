<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
