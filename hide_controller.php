<?php
/*-------------------------------------------*/
/*  著者情報非表示用のメタボックスを追加
/*-------------------------------------------*/
add_action( 'admin_menu', 'pad_add_custom_field_user_view_group' );

// add meta_box
function pad_add_custom_field_user_view_group() {

	$args = array(
		'public' => true,
	);

	$output   = 'names'; // names or objects, note names is the default
	$operator = 'and'; // 'and' or 'or'

	$post_types = pad_display_post_types();

	if ( empty( $post_types ) || ! is_array( $post_types ) ) {
		return;
	}

	// WordPress 7.0 RTC compatibility:
	// ビルドファイルが存在する場合はネイティブサイドバーパネルで代替するため、
	// レガシーメタボックスを非表示にする（__back_compat_meta_box => true）。
	// ビルドファイルがない場合は従来通りメタボックスを表示する（フォールバック）。
	$base_dir    = defined( 'VK_PAD_DIR' ) ? VK_PAD_DIR : plugin_dir_path( __FILE__ );
	$asset_path  = $base_dir . 'build/index.asset.php';
	$has_build   = file_exists( $asset_path );
	$callback_args = $has_build
		? array( '__back_compat_meta_box' => true )
		: array();

	foreach ( $post_types  as $post_type ) {
		add_meta_box(
			'post_author_display',
			__( 'Post Author Display', 'vk-post-author-display' ),
			'pad_content_meta_fields',
			$post_type,
			'side',
			'default',
			$callback_args
		);
	}
}

/*-------------------------------------------*/
/*  入力フィールドの生成
/*-------------------------------------------*/

/**
 * Output the "Don't display post author" checkbox and its nonce field in the meta box.
 * メタボックスに、「著者を非表示にする」のチェックボックスと nonce 欄を出力する。
 *
 * @return void
 */
function pad_content_meta_fields() {

	//CSRF対策の設定（フォームにhiddenフィールドとして追加するためのnonceを「'noncename__pad_hide」として設定）
	// The action name must match the one verified in pad_save_hide_items().
	// action 名は pad_save_hide_items() で確認するものと必ずそろえること。
	wp_nonce_field( 'pad_save_hide_items', 'noncename__pad_hide' );

	global $post;
	$checked = ( get_post_meta( $post->ID, 'pad_hide_post_author', true ) ) ? ' checked' : '';
	$label   = __( 'Don\'t display post author', 'vk-post-author-display' );

	echo '<ul>';
	echo '<li><label>' . '<input type="checkbox" id="pad_hide_post_author" name="pad_hide_post_author" value="true"' . $checked . '> ' . $label . '</label></li>';
	echo '</ul>';

}

/*-------------------------------------------*/
/*  入力された値の保存
/*-------------------------------------------*/
add_action( 'save_post', 'pad_save_hide_items' );

/**
 * Save the "Don't display post author" setting after verifying the nonce and the user's permission to edit the post.
 * Do nothing when the request did not come from the meta box form (REST API, quick edit, WP-CLI, cron, etc.).
 * nonce と投稿の編集権限を確認したうえで、「著者を非表示にする」の設定を保存する。
 * メタボックスのフォーム以外からの保存（REST API・クイック編集・WP-CLI・cron など）では何もしない。
 *
 * @param int $post_id Post ID. / 投稿 ID。
 * @return int|void The post ID when nothing was saved. / 保存せずに抜けたときは投稿 ID。
 */
function pad_save_hide_items( $post_id ) {
	global $post;

	// Verify the nonce and do nothing when it is missing or invalid.
	// Saves that do not go through the meta box form carry no nonce, so they return here.
	// nonce を確認し、無い・正しくないときは何もしない（CSRF対策）。
	// メタボックスのフォームを通らない保存には nonce が付かないため、ここで抜ける。
	if ( ! isset( $_POST['noncename__pad_hide'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['noncename__pad_hide'] ) ), 'pad_save_hide_items' ) ) {
		return $post_id;
	}

	//自動保存ルーチンかどうかチェック。そうだった場合は何もしない（記事の自動保存処理として呼び出された場合の対策）
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id; }

	// Permission check: only a user who can edit this post may change the setting.
	// 権限の確認（この投稿を編集できる利用者だけが変更できる）。
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	// Save only 'true' and treat any other value (including an array) as an empty string, the same rule as the sanitize_callback registered in inc/register-meta.php.
	// An array is dropped here so that the callback's (string) conversion does not raise "Array to string conversion".
	// 'true' だけを保存し、それ以外の値（配列を含む）は空文字として扱う。inc/register-meta.php に登録した sanitize_callback と同じ規則（'true' か空文字だけ）。
	// 配列はここで落とし、その sanitize_callback の (string) 変換で「Array to string conversion」の警告が出ないようにする。
	$field       = 'pad_hide_post_author';
	$field_value = ( isset( $_POST[ $field ] ) && 'true' === sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) ) ? 'true' : '';
	// データが空だったら入れる
	if ( get_post_meta( $post_id, $field ) == '' ) {
		add_post_meta( $post_id, $field, $field_value, true );
		// 今入ってる値と違ってたらアップデートする
	} elseif ( $field_value != get_post_meta( $post_id, $field, true ) ) {
		update_post_meta( $post_id, $field, $field_value );
		// 入力がなかったら消す
	} elseif ( $field_value == '' ) {
		delete_post_meta( $post_id, $field, get_post_meta( $post_id, $field, true ) );
	}

}

/*-------------------------------------------*/
/*  非表示を実行
/*-------------------------------------------*/
