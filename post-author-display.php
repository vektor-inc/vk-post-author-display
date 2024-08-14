<?php
/*
Plugin Name: VK Post Author Display
Plugin URI: http://wordpress.org/extend/plugins/vk-post-author-display/
Description: Show post author information at post bottom.
Version: 1.25.0
Author: Vektor,Inc.
Author URI: https://ex-unit.nagoya/
Text Domain: vk-post-author-display
Domain Path: /languages
License: GPL2

/*
	Copyright 2013-2020 Hidekazu Ishikawa ( email : kurudrive@gmail.com )

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

/*
Setting & load file
-------------------------------------------*/
/*
	vk post author text domain load
-------------------------------------------*/
/*
	Display post author unit
-------------------------------------------*/
/*
	front display css
-------------------------------------------*/
/*
	init
-------------------------------------------*/
/*
	メニューに追加
-------------------------------------------*/
/*
	Add Short code
-------------------------------------------*/

/*
Setting & load file
-------------------------------------------*/

// load composer
require_once __DIR__ . '/vendor/autoload.php';

$data = get_file_data(
	__FILE__,
	array(
		'version'    => 'Version',
		'textdomain' => 'Text Domain',
	)
);
define( 'VK_PAD_VERSION', $data['version'] );
define( 'VK_PAD_BASENAME', plugin_basename( __FILE__ ) );
define( 'VK_PAD_URL', plugin_dir_url( __FILE__ ) );
define( 'VK_PAD_DIR', plugin_dir_path( __FILE__ ) );

/*
	vk post author text domain load
-------------------------------------------*/
// function pad_text_domain() {
// load_plugin_textdomain( 'vk-post-author-display', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
// }
// add_action( 'init', 'pad_text_domain' );

require_once VK_PAD_DIR . 'inc/term-color/term-color-config.php';
require_once VK_PAD_DIR . 'inc/font-awesome/config.php';
require_once VK_PAD_DIR . 'inc/template-tags/template-tags-config.php';
require_once VK_PAD_DIR . 'admin/admin.php';
require_once VK_PAD_DIR . 'admin/admin-profile.php';
require_once VK_PAD_DIR . 'view.post-author.php';
require_once VK_PAD_DIR . 'hide_controller.php';

// Add a link to this plugin's settings page
function pad_set_plugin_meta( $links ) {
	$settings_link = '<a href="options-general.php?page=pad_plugin_options">' . __( 'Setting', 'vk-post-author-display' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . VK_PAD_BASENAME, 'pad_set_plugin_meta', 10, 1 );



function pad_display_post_types() {
	$options = pad_get_plugin_options();

	// Reason of use function_exists that template tags lib was not version management.
	if ( function_exists( 'vk_the_post_type_check_list_saved_array_convert' ) ) {
		$post_types = vk_the_post_type_check_list_saved_array_convert( $options['post_types'] );
	} else {
		$post_types = array( 'post' );
	}
	$post_types = apply_filters( 'pad_display_post_types', $post_types );
	return $post_types;
}

/*
	Display post author unit
/*-------------------------------------------*/
add_filter( 'the_content', 'pad_add_author' );
function pad_add_author( $content ) {

	global $is_pagewidget;
	// 固定ページ本文ウィジェットだったら
	if ( $is_pagewidget ) {
		return $content;
	}

	$option = pad_get_plugin_options();

	// 非表示指定されてたら表示しない
	if ( $option['auto_display'] == 'no' ) {
		return $content;
	}

	$post_types = pad_display_post_types();
	if ( is_singular( $post_types ) ) {
		foreach ( $post_types as $key => $value ) {
			if ( get_post_type() == $value ) {
				global $post;
				$hidden = apply_filters(
					'pad_hide_post_author_custom',
					get_post_meta( $post->ID, 'pad_hide_post_author', true )
				);
				if ( ! $hidden ) {
					$author_unit = Vk_Post_Author_Box::pad_get_author_box();
					$content     = $content . $author_unit;
				}
			}
		}
	}
	return $content;
}

/*
	front display css
/*-------------------------------------------*/
add_action( 'wp_enqueue_scripts', 'pad_set_css' );
function pad_set_css() {
	$post_types = pad_display_post_types();

	// Cope with use short code in page
	$post_types = apply_filters( 'pad_css_post_types', $post_types );
	// Example
	// add_filter(
	// 'pad_css_post_types',
	// function( $post_types ) {
	// $post_types[] = 'page';
	// return $post_types;
	// }
	// );

	$cssPath = apply_filters( 'pad-stylesheet', plugins_url( 'assets/css/vk-post-author.css', __FILE__ ) );

	if ( is_singular( $post_types ) || is_author() ) {
		wp_enqueue_style( 'set_vk_post_autor_css', $cssPath, false, VK_PAD_VERSION );
	}
}

/*
	init
/*-------------------------------------------*/
function pad_get_default_options() {
	$display_author_options = array(
		'author_box_title'        => __( 'Author Profile', 'vk-post-author-display' ),
		'author_box_title_tag'    => 'h4',
		'author_picture_style'    => 'square',
		'list_box_title'          => __( 'Latest entries', 'vk-post-author-display' ),
		'list_box_title_tag'      => 'h5',
		'author_archive_link'     => 'hide',
		'author_archive_link_txt' => __( 'Author Archives', 'vk-post-author-display' ),
		'show_thumbnail'          => 'display',
		'auto_display'            => 'yes',
		'post_types'              => array( 'post' => 'true' ),
	);
	return apply_filters( 'pad_default_options', $display_author_options );
}

function pad_plugin_options_Custom_init() {
	if ( false === pad_get_plugin_options() ) {
		add_option( 'pad_plugin_options', pad_get_default_options() );
	}
	register_setting(
		'pad_plugin_options',
		'pad_plugin_options',
		'pad_plugin_options_validate'
	);
}
add_action( 'admin_init', 'pad_plugin_options_Custom_init' );

/*
	functionsで毎回呼び出して$options_padに入れる処理を他でする。
/*-------------------------------------------*/
function pad_get_plugin_options() {
	// デフォルト値を取得
	$default = pad_get_default_options();

	// オプション値を取得（無い場合はデフォルト値を入れて設定）
	$options = get_option( 'pad_plugin_options', $default );

	$options = wp_parse_args( $options, $default );

	// 値が空で既に保存されているものがあり、管理画面で保存値のアクティブが効かないため、
	// 値が空の場合は display を代入して返す
	if ( empty( $options['show_thumbnail'] ) ) {
		$options['show_thumbnail'] = 'display';
	}

	return $options;
}

/*
	メニューに追加
/*-------------------------------------------*/
function pad_add_customSetting() {
	$custom_page = add_options_page(
		__( 'VK Post Author Display setting', 'vk-post-author-display' ),       // Name of page
		_x( 'VK Post Author Display', 'label in admin menu', 'vk-post-author-display' ),                // Label in menu
		'edit_theme_options',               // Capability required　このメニューページを閲覧・使用するために最低限必要なユーザーレベルまたはユーザーの種類と権限。
		'pad_plugin_options',               // ユニークなこのサブメニューページの識別子
		'pad_add_customSettingPage'         // メニューページのコンテンツを出力する関数
	);
	if ( ! $custom_page ) {
		return;
	}
}
add_action( 'admin_menu', 'pad_add_customSetting' );

/*
	Setting page
-------------------------------------------*/
// 第１引数で、どのページで適応するのかを指定。この場合後半（settings_page_pad_plugin_options ）がどのページかを判断する hook_suffix になっている。
// hook_suffix は、body のclass名などから確認する事が出来る。
// 第２引数は処理するfunction名
// add_action( 'admin_print_styles-settings_page_pad_plugin_options ', 'pad_custom_enqueue_scripts' );
// function pad_custom_enqueue_scripts( $hook_suffix ) {
// wp_enqueue_style( 'pad_plugin_options', get_template_directory_uri() . '/inc/theme-options.css', false, '2012-11-17' );
// }

function pad_plugin_options_validate( $input ) {
	$output = $defaults = pad_get_default_options();

	$output['author_box_title']        = wp_kses_post( $input['author_box_title'] );
	$output['author_box_title_tag']    = esc_html( $input['author_box_title_tag'] );
	$output['author_picture_style']    = esc_html( $input['author_picture_style'] );
	$output['list_box_title']          = wp_kses_post( $input['list_box_title'] );
	$output['list_box_title_tag']      = esc_html( $input['list_box_title_tag'] );
	$output['author_archive_link']     = esc_html( $input['author_archive_link'] );
	$output['author_archive_link_txt'] = wp_kses_post( $input['author_archive_link_txt'] );
	$output['show_thumbnail']          = esc_html( $input['show_thumbnail'] );
	$output['auto_display']            = esc_html( $input['auto_display'] );
	if ( function_exists( 'vk_sanitize_array' ) ) {
		$output['post_types'] = vk_sanitize_array( $input['post_types'] );
	} else {
		$output['post_types'] = $input['post_types'];
	}

	return apply_filters( 'pad_plugin_options_validate', $output, $input, $defaults );
}

/*
	optionの値を単純に引っ張る
/*-------------------------------------------*/
function get_pad_options( $optionLabel ) {
	$options_pad = pad_get_plugin_options();
	if ( $options_pad[ $optionLabel ] ) {
		return $options_pad[ $optionLabel ];
	}
}


/*
	Unset pad custom size thumbnail
/*
-------------------------------------------*/
// function pad_plugin_disable_thumbnail( $sizes ) {
// if ( isset( $sizes['pad_thumb'] ) ){
// unset( $sizes['pad_thumb'] );
// }
// }

/*
	Add Short code
-------------------------------------------*/
add_shortcode( 'pad', 'pad_short_code' );
function pad_short_code() {
	if ( class_exists( 'Vk_Post_Author_Box' ) && is_singular() ) {
		return Vk_Post_Author_Box::pad_get_author_box();
	}
}
