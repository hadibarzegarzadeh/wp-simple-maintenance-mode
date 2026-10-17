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

define( 'SMM_PATH', plugin_dir_path( __FILE__ ) );

require_once SMM_PATH . 'includes/post-type.php';
require_once SMM_PATH . 'includes/helpers.php';
require_once SMM_PATH . 'includes/frontend.php';

require_once SMM_PATH . 'admin/admin.php';
require_once SMM_PATH . 'admin/settings-page.php';
