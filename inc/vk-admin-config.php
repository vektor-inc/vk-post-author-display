<?php

/*-------------------------------------------*/
/*  Load modules
/*-------------------------------------------*/
if ( ! class_exists( 'Vk_Admin' ) )
{
	require_once( 'vk-admin/class.vk-admin.php' );
}

$admin_pages = array( 'settings_page_pad_plugin_options' );
Vk_Admin::admin_scripts( $admin_pages );

/*-------------------------------------------*/
/*	Setting Page
/*-------------------------------------------*/
function pad_add_customSettingPage() {
	require_once( VK_PAD_DIR . 'view.admin.php' );
	$get_page_title = __( 'VK Post Author Display Main Setting', 'post-author-display' );
	$get_logo_html = '';
	$get_menu_html = '<li><a href="#post_author_box">'.__('Post Author Box Setting', 'post-author-display').'</a></li>';
	$get_menu_html .='<li><a href="'.get_admin_url().'profile.php" target="_blank">'.__( 'Edit your profile', 'post-author-display' ).'</a></li>';
	Vk_Admin::admin_page_frame( $get_page_title, 'pad_the_admin_body', $get_logo_html, $get_menu_html );
}