<?php
/*
Plugin Name: VK Post Author Display
Plugin URI: http://wordpress.org/extend/plugins/vk-post-author-display/
Description: Show post author information at post bottom.
Version: 1.3.9
Author: Vektor,Inc.
Author URI: http://ex-unit.vektor-inc.co.jp/
Text Domain : post-author-display
Domain Path : /languages/
License: GPL2

/*  Copyright 2013-2016 Hidekazu Ishikawa ( email : kurudrive@gmail.com )

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*-------------------------------------------*/
/* Setting & load file
/*-------------------------------------------*/
/*	vk post author text domain load
/*-------------------------------------------*/
/*	Display post author unit
/*-------------------------------------------*/
/*	front display css
/*-------------------------------------------*/
/*	init
/*-------------------------------------------*/
/*	メニューに追加
/*-------------------------------------------*/

/*-------------------------------------------*/
/* Setting & load file
/*-------------------------------------------*/

$data = get_file_data( __FILE__, array( 'version' => 'Version','textdomain' => 'Text Domain' ) );
define( 'VK_PAD_VERSION', $data['version'] );
define( 'VK_PAD_BASENAME', plugin_basename( __FILE__ ) );
define( 'VK_PAD_URL', plugin_dir_url( __FILE__ ) );
define( 'VK_PAD_DIR', plugin_dir_path( __FILE__ ) );

/*-------------------------------------------*/
/*	vk post author text domain load
/*-------------------------------------------*/
function pad_text_domain() {
	load_plugin_textdomain( 'post-author-display', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'pad_text_domain' );

require_once( VK_PAD_DIR . 'inc/term-color-config.php' );
require_once( VK_PAD_DIR . 'inc/vk-admin-config.php' );
require_once( VK_PAD_DIR . 'view.post-author.php' );
require_once( VK_PAD_DIR . 'admin-profile.php' );
require_once( VK_PAD_DIR . 'hide_controller.php' );


// _e('aaaaa','post-author-display');

	// Add a link to this plugin's settings page
function pad_set_plugin_meta( $links ) { 
    $settings_link             = '<a href="options-general.php?page=pad_plugin_options">'.__( 'Setting', 'post-author-display' ).'</a>';
    array_unshift($links, $settings_link);
    return $links;
}
 add_filter('plugin_action_links_'.VK_PAD_BASENAME , 'pad_set_plugin_meta', 10, 1);



function pad_display_post_types(){
	// $post_types = get_post_types( $args, $output, $operator );
	$post_types = array('post');
	$post_types = apply_filters( 'pad_display_post_types', $post_types );
	return $post_types;
}

// custom example
// add_filter( 'pad_display_post_types','add_pad_custom_post_types' );
// function add_pad_custom_post_types($post_types){
// 	$post_types[] = 'page';
// 	return $post_types;
// }

/*-------------------------------------------*/
/*	Display post author unit
/*-------------------------------------------*/
add_filter( 'the_content', 'pad_add_author');
function pad_add_author($content){
	// if ( ( is_single() || is_page() ) && !is_front_page() ){
	if ( is_single() ){
		$post_types = pad_display_post_types();
		foreach ($post_types as $key => $value) {
			if ( get_post_type() == $value ){
				global $post;
				$hidden = apply_filters( 
					'pad_hide_post_author_custom', 
					get_post_meta( $post->ID,'pad_hide_post_author',true )
					);
				if ( !$hidden ){
					$author_unit = Vk_Post_Author_Box::pad_get_author_box();
					$content = $content.$author_unit;
				}
			}
		}
	}
	return $content;
}
/*-------------------------------------------*/
/*	front display css
/*-------------------------------------------*/
add_action('wp_enqueue_scripts', 'pad_set_css');
function pad_set_css(){
	$cssPath = apply_filters( "pad-stylesheet", plugins_url("css/vk-post-author.css", __FILE__) );
	if ( get_post_type() == 'post'){
		wp_enqueue_style( 'set_vk_post_autor_css', $cssPath , false, VK_PAD_VERSION);
		wp_enqueue_style( 'font-awesome', VK_PAD_URL . '/libraries/font-awesome/css/font-awesome.min.css', array(), '4.6.3', 'all' );
	}
}

/*-------------------------------------------*/
/*	init
/*-------------------------------------------*/
function pad_get_default_options() {
	$display_author_options = array(
		'author_box_title'        => __( 'Author Profile', 'post-author-display' ),
		'author_picture_style'   => 'square',
		'list_box_title'          => __( 'Latest entries', 'post-author-display' ),
		'author_archive_link'     => 'hide',
		'author_archive_link_txt' => __( 'Author Archives', 'post-author-display' ),
		'show_thumbnail'          => 'display',
		'generate_thumbnail'      => 'yes'
	);
	return apply_filters( 'pad_default_options', $display_author_options );
}

function pad_plugin_options_Custom_init() {
	if ( false === pad_get_plugin_options() )
	add_option( 'pad_plugin_options', pad_get_default_options() );
	register_setting(
		'pad_plugin_options',
		'pad_plugin_options',
		'pad_plugin_options_validate'
	);
}
add_action( 'admin_init', 'pad_plugin_options_Custom_init' );

/*-------------------------------------------*/
/*	functionsで毎回呼び出して$options_padに入れる処理を他でする。
/*-------------------------------------------*/
function pad_get_plugin_options() {
	return get_option( 'pad_plugin_options', pad_get_default_options() );
}

/*-------------------------------------------*/
/*	メニューに追加
/*-------------------------------------------*/
function pad_add_customSetting() {
	$custom_page = add_options_page(
		__( 'VK Post Author Display setting', 'post-author-display' ),		// Name of page
		_x( 'VK Post Author Display', 'label in admin menu', 'post-author-display' ),				// Label in menu
		'edit_theme_options',				// Capability required　このメニューページを閲覧・使用するために最低限必要なユーザーレベルまたはユーザーの種類と権限。
		'pad_plugin_options',				// ユニークなこのサブメニューページの識別子
		'pad_add_customSettingPage'			// メニューページのコンテンツを出力する関数
	);
	if ( ! $custom_page )
	return;
}
add_action( 'admin_menu', 'pad_add_customSetting' );

/*-------------------------------------------*/
/*	Setting page
/*-------------------------------------------*/
// 第１引数で、どのページで適応するのかを指定。この場合後半（settings_page_pad_plugin_options ）がどのページかを判断する hook_suffix になっている。
// hook_suffix は、body のclass名などから確認する事が出来る。
// 第２引数は処理するfunction名
// add_action( 'admin_print_styles-settings_page_pad_plugin_options ', 'pad_custom_enqueue_scripts' );
// function pad_custom_enqueue_scripts( $hook_suffix ) {
// 	wp_enqueue_style( 'pad_plugin_options', get_template_directory_uri() . '/inc/theme-options.css', false, '2012-11-17' );
// }

function pad_plugin_options_validate( $input ) {
	$output = $defaults = pad_get_default_options();

	$output['author_box_title']         = $input['author_box_title'];
	$output['author_picture_style']    = $input['author_picture_style'];
	$output['list_box_title']           = $input['list_box_title'];
	$output['author_archive_link']      = $input['author_archive_link'];
	$output['author_archive_link_txt']  = $input['author_archive_link_txt'];
	$output['show_thumbnail']           = $input['show_thumbnail'];
	$output['generate_thumbnail']       = $input['generate_thumbnail'];

	return apply_filters( 'pad_plugin_options_validate', $output, $input, $defaults );
}

/*-------------------------------------------*/
/*	optionの値を単純に引っ張る
/*-------------------------------------------*/
function get_pad_options($optionLabel) {
	$options_pad = pad_get_plugin_options();
	if ($options_pad[$optionLabel]){
		return $options_pad[$optionLabel];
	}
}

/*-------------------------------------------*/
/*	vk post author display custom size thumbnail
/*-------------------------------------------*/
function pad_plugin_special_thumbnail() {

	$options 		 = pad_get_plugin_options();
	// $default_options = pad_get_default_options();

	// Case of use PAD image size 
	if( isset( $options['generate_thumbnail'] ) && $options['generate_thumbnail'] == 'yes' ) {

		if ( function_exists( 'add_theme_support' ) ) {
			add_theme_support( 'post-thumbnails' );
			//custom thumbnail for pad plugin
			add_image_size( 'pad_thumb', 240, 135, array('center', 'center') );
		}
	} 
	// else {
	// 	apply_filters('intermediate_image_sizes', 'pad_plugin_disable_thumbnail');
	// }
}
add_action('after_setup_theme', 'pad_plugin_special_thumbnail');

/*-------------------------------------------*/
/*	Unset pad custom size thumbnail
/*-------------------------------------------*/
// function pad_plugin_disable_thumbnail( $sizes ) {
// 	if ( isset( $sizes['pad_thumb'] ) ){
// 		unset( $sizes['pad_thumb'] );
// 	}
// }
