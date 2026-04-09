<?php
/**
 * Register post meta for REST API access.
 * ブロックエディタからREST API経由でメタデータを読み書きするために登録する。
 *
 * @package vk-post-author-display
 */

/**
 * Register pad_hide_post_author meta key for the REST API.
 * pad_hide_post_author メタキーをREST APIに登録する。
 *
 * @return void
 */
function pad_register_post_meta() {
	$post_types = pad_display_post_types();

	if ( empty( $post_types ) || ! is_array( $post_types ) ) {
		return;
	}

	foreach ( $post_types as $post_type ) {
		register_post_meta(
			$post_type,
			'pad_hide_post_author',
			array(
				'type'              => 'string',
				'description'       => 'Hide post author display flag / 著者表示非表示フラグ',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					// フラグ用途のため 'true' / '' のみ許可する。
					// sanitize_text_field だと任意文字列が残り、
					// truthiness 判定で意図しない非表示が起きうる。
					return ( 'true' === (string) $value ) ? 'true' : '';
				},
				'show_in_rest'      => true,
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'pad_register_post_meta' );
