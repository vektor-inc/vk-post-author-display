<?php

/*
	Add user sns link
/*-------------------------------------------*/

function pad_sns_array() {
	$sns_array = array(
		'web'       => array(
			'name'       => __( 'Website', 'vk-post-author-display' ),
			'icon_class' => 'fa-solid fa-globe',
		),
		'mail'      => array(
			'name'       => __( 'Mail（Contact Page）', 'vk-post-author-display' ),
			'icon_class' => 'fa-solid fa-square-envelope',
		),
		'twitter'   => array(
			'name'       => __( 'X', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-square-x-twitter',
		),
		'bluesky'   => array(
			'name'       => __( 'Bluesky', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-square-bluesky',
		),
		'facebook'  => array(
			'name'       => __( 'Facebook', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-square-facebook',
		),
		'instagram' => array(
			'name'       => __( 'Instagram', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-square-instagram',
		),
		'threads'   => array(
			'name'       => __( 'Threads', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-square-threads',
		),
		'youtube'   => array(
			'name'       => __( 'YouTube', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-square-youtube',
		),
		'linkedin'  => array(
			'name'       => __( 'LinkedIn', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-linkedin',
		),
		'github'    => array(
			'name'       => __( 'GitHub', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-square-github',
		),
		'wordpress' => array(
			'name'       => __( 'WordPress', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-wordpress',
		),
		'amazon'    => array(
			'name'       => __( 'Amazon Wish List', 'vk-post-author-display' ),
			'icon_class' => 'fa-brands fa-amazon',
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

/**
 * Save the profile picture (attachment ID) after verifying the nonce and the user's permission to edit the user.
 * Do nothing when the request did not come from the profile form (REST API, WP-CLI, wp_update_user() called by other code, etc.).
 * nonce とユーザーの編集権限を確認したうえで、プロフィール写真（画像の ID）を保存する。
 * プロフィール編集フォーム以外からの更新（REST API・WP-CLI・他のコードが呼ぶ wp_update_user() など）では何もしない。
 *
 * @param int     $user_id       User ID. / ユーザー ID。
 * @param WP_User $old_user_data User data before the update. / 更新前のユーザー情報。
 * @return void
 */
function pad_update_user_profile_image( $user_id, $old_user_data ) {

	// Verify the nonce output by add_user_profile_image_form(). The action name must match the one used there.
	// The action name includes the user ID so that a nonce for one profile cannot be used for another user.
	// add_user_profile_image_form() が出力した nonce を確認する。action 名はそちらと必ずそろえること。
	// action 名にユーザー ID を含め、別のユーザーのプロフィール用の nonce では保存できないようにしている。
	if ( ! isset( $_POST['pad_user_profile_image_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pad_user_profile_image_nonce'] ) ), 'pad_update_user_profile_image_' . $user_id ) ) {
		return;
	}

	// Permission check: only a user who can edit this user may save the profile picture.
	// 権限の確認（このユーザーを編集できる利用者だけが保存できる）。
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	// Do nothing when the value is missing or is not a string (e.g. an array).
	// 値が送られていない、または文字列以外（配列など）のときは何もしない。
	if ( ! isset( $_POST['user_profile_image'] ) || ! is_string( $_POST['user_profile_image'] ) ) {
		return;
	}

	$user_profile_image = sanitize_text_field( wp_unslash( $_POST['user_profile_image'] ) );

	// An empty string means "Reset image" and is saved as is.
	// Otherwise only an attachment ID written as a positive integer is saved; any other value is ignored and the saved value stays.
	// absint() turns an empty string into 0, so the empty string is handled before it and the result is compared with the sent string.
	// 空文字は「写真を解除」の操作なので、そのまま保存する。
	// 空以外は、正の整数で書かれた画像の ID だけを保存し、それ以外の値は無視して保存済みの値を残す。
	// absint() は空文字を 0 にするため、先に空文字を判定し、整えた結果が送られた文字列と一致するかで確かめる。
	if ( '' !== $user_profile_image ) {
		$attachment_id = absint( $user_profile_image );
		if ( 0 === $attachment_id || (string) $attachment_id !== $user_profile_image ) {
			return;
		}
	}

	// Update only when the value differs from the saved one.
	// 保存済みの値と違うときだけ更新する。
	if ( (string) $old_user_data->user_profile_image !== $user_profile_image ) {
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
