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

/**
 * 設定画面の「Display post types」一覧に、折り返し位置を整える CSS を追記する。
 * Add CSS that fixes the line wrapping of the "Display post types" list on the setting page.
 *
 * @return void
 */
function pad_add_post_type_list_wrap_style() {
	// 長音などが行頭に来る改行を防ぐ（投稿タイプ名が長い行をスマホ幅で開いたときの不具合への対応）.
	// Prevent a line from starting with a prolonged sound mark etc. (fix for long post type names on narrow screens).
	$css = '#disolay_post_types .form-table td li label{line-break:strict;}';
	// スラッグがハイフンの位置で割れて一部だけ次の行に落ちるのを防ぐ.
	// Prevent the slug from being broken at a hyphen and partly dropped to the next line.
	$css .= '#disolay_post_types .form-table td li .description{white-space:nowrap;}';

	// vk-admin のスタイルの直後に出力する.
	// Print right after the vk-admin style.
	wp_add_inline_style( 'vk-admin-style', $css );
}
add_action( 'admin_print_styles-settings_page_pad_plugin_options', 'pad_add_post_type_list_wrap_style' );

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
