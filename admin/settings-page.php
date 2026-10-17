<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
