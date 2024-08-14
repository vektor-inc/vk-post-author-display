<?php
/**
 * make admin page
 *
 * @package VK Post Author Display
 */

use VektorInc\VK_Admin\VkAdmin;
VkAdmin::init();


$admin_pages = array( 'settings_page_pad_plugin_options' );
VkAdmin::admin_scripts( $admin_pages );

/*
	Setting Page
/*-------------------------------------------*/
function pad_add_customSettingPage() {
	require_once __DIR__ . '/view.admin.php';
	$get_page_title = __( 'VK Post Author Display Main Setting', 'vk-post-author-display' );

	$get_logo_html = '';

	$get_menu_html  = '<li><a href="#post_author_box">' . __( 'Post Author Box Setting', 'vk-post-author-display' ) . '</a></li>';
	$get_menu_html .= '<li><a href="#disolay_post_types">' . __( 'Display post types', 'vk-post-author-display' ) . '</a></li>';
	$get_menu_html .= '<li><a href="' . get_admin_url() . 'profile.php" target="_blank">' . __( 'Edit your profile', 'vk-post-author-display' ) . '</a></li>';

	VkAdmin::admin_page_frame( $get_page_title, 'pad_the_admin_body', $get_logo_html, $get_menu_html );
}
