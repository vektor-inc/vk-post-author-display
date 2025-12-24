<?php

/*
	Add user sns link
/*-------------------------------------------*/

function pad_sns_array() {
	$sns_array = array(
		'web'       => array(
			'name'     => __( 'Website', 'vk-post-author-display' ),
			'icon' => '<i class="fa-solid fa-globe"></i>',
		),
		'mail'      => array(
			'name'     => __( 'Mail（Contact Page）', 'vk-post-author-display' ),
			'icon' => '<i class="fa-solid fa-square-envelope"></i>',
		),
		'twitter'   => array(
			'name'     => __( 'X', 'vk-post-author-display' ),
			'icon' => '<i class="fa-brands fa-square-x-twitter"></i>',
		),
		'facebook'  => array(
			'name'     => __( 'Facebook', 'vk-post-author-display' ),
			'icon' => '<i class="fa-brands fa-square-facebook"></i>',
		),
		'instagram' => array(
			'name'     => __( 'Instagram', 'vk-post-author-display' ),
			'icon' => '<i class="fa-brands fa-square-instagram"></i>',
		),
		'youtube'   => array(
			'name'     => __( 'YouTube', 'vk-post-author-display' ),
			'icon' => '<i class="fa-brands fa-square-youtube"></i>',
		),
		'linkedin'  => array(
			'name'     => __( 'LinkedIn', 'vk-post-author-display' ),
			'icon' => '<i class="fa-brands fa-linkedin"></i>',
		),
		'wordpress' => array(
			'name'     => __( 'WordPress', 'vk-post-author-display' ),
			'icon' => '<i class="fa-brands fa-wordpress"></i>',
		),
		'amazon'    => array(
			'name'     => __( 'Amazon Wish List', 'vk-post-author-display' ),
			'icon' => '<i class="fa-brands fa-amazon"></i>',
		),
	);
	return $sns_array;
}

function pad_update_profile_fields( $contactmethods ) {

	// 項目の追加
	$contactmethods['pad_caption'] = __( 'Caption<br>(VK Post Author Display)', 'vk-post-author-display' );

	$sns_array = pad_sns_array();
	foreach ( $sns_array as $key => $value ) {
		$contactmethods[ 'pad_' . $key ] = $value['name'] . ' URL <br>(VK Post Author Display)';
	}

	return $contactmethods;
}
add_filter( 'user_contactmethods', 'pad_update_profile_fields', 10, 1 );

/*
	vk post author profile image
/*-------------------------------------------*/
require_once __DIR__ . '/view.admin-profile.php';
add_action( 'show_password_fields', 'add_user_profile_image_form' );

function pad_update_user_profile_image( $user_id, $old_user_data ) {
	if ( isset( $_POST['user_profile_image'] ) && $old_user_data->user_profile_image != $_POST['user_profile_image'] ) {
		$user_profile_image = sanitize_text_field( $_POST['user_profile_image'] );
		$user_profile_image = wp_filter_kses( $user_profile_image );
		$user_profile_image = _wp_specialchars( $user_profile_image );
		update_user_meta( $user_id, 'user_profile_image', $user_profile_image );
	}
}
add_action( 'profile_update', 'pad_update_user_profile_image', 10, 2 );

/*
	$admin_pages の配列にいれる識別値は下記をコメントアウト解除すればブラウザのコンソールで確認出来る
/*-------------------------------------------*/

// add_action("admin_head", 'suffix2console');
// function suffix2console() {
// global $hook_suffix;
// if (is_user_logged_in()) {
// $str = "<script type=\"text/javascript\">console.log('%s')</script>";
// printf($str, $hook_suffix);
// }
// }

function pad_admin_enqueue_scripts() {
	wp_enqueue_media();
	wp_enqueue_script( 'pad-profile-media-js', VK_PAD_URL . 'assets/js/media.js', array(), VK_PAD_VERSION );
	wp_enqueue_style( 'pad-profile-style', VK_PAD_URL . 'assets/css/pad-admin-profile.css', array(), VK_PAD_VERSION );
}
add_action( 'admin_print_styles-profile.php', 'pad_admin_enqueue_scripts' );
add_action( 'admin_print_styles-user-edit.php', 'pad_admin_enqueue_scripts' );
