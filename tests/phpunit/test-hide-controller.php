<?php
/**
 * Class HideControllerTest
 *
 * @package vk-post-author-display
 */

class HideControllerTest extends WP_UnitTestCase {

	/**
	 * Globals changed by the tests. They are restored in tearDown().
	 * テストで書き換えるグローバルの退避先。tearDown() で戻す。
	 *
	 * @var array
	 */
	private $original_globals = array();

	/**
	 * Back up the globals the tests change and register the meta key again.
	 * The test base class unregisters every meta key after each test, so the key registered on init is gone from the second test on.
	 * テストで書き換えるグローバルを退避し、メタキーを登録し直す。
	 * テスト基底クラスは各テストの後に全メタキーを登録解除するため、init で登録したキーは 2 つ目のテストから無くなっている。
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_globals = array(
			'post'           => isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null,
			'wp_rest_server' => isset( $GLOBALS['wp_rest_server'] ) ? $GLOBALS['wp_rest_server'] : null,
			'post_data'      => $_POST,
			'request_uri'    => isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null,
			'user_id'        => get_current_user_id(),
		);

		pad_register_post_meta();

		// Build a new REST server after the meta key is registered, so that its schema includes the meta key.
		// メタキーの登録後に REST サーバーを作り直し、スキーマにメタキーが含まれるようにする。
		$GLOBALS['wp_rest_server'] = null;
	}

	/**
	 * Restore the globals changed by the tests.
	 * テストで書き換えたグローバルを元に戻す。
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$_POST = $this->original_globals['post_data'];
		wp_set_current_user( $this->original_globals['user_id'] );

		if ( null !== $this->original_globals['request_uri'] ) {
			$_SERVER['REQUEST_URI'] = $this->original_globals['request_uri'];
		} else {
			unset( $_SERVER['REQUEST_URI'] );
		}

		foreach ( array( 'post', 'wp_rest_server' ) as $global_name ) {
			if ( null !== $this->original_globals[ $global_name ] ) {
				$GLOBALS[ $global_name ] = $this->original_globals[ $global_name ];
			} else {
				unset( $GLOBALS[ $global_name ] );
			}
		}

		parent::tearDown();
	}

	/**
	 * Render the meta box as the current user and return the nonce value of its hidden field.
	 * 現在のユーザーとしてメタボックスを描画し、hidden 欄の nonce の値を返す。
	 *
	 * @param int $post_id Post ID shown in the meta box. / メタボックスに表示する投稿の ID。
	 * @return string Nonce value. An empty string when the field is not output. / nonce の値。欄が出力されないときは空文字。
	 */
	private function get_nonce_from_meta_box( $post_id ) {
		// wp_nonce_field() also outputs the referer field, which reads REQUEST_URI.
		// wp_nonce_field() は REQUEST_URI を読む referer 欄も出力するため、値を用意する。
		$_SERVER['REQUEST_URI'] = '/wp-admin/post.php?post=' . $post_id . '&action=edit';
		$GLOBALS['post']        = get_post( $post_id );

		ob_start();
		pad_content_meta_fields();
		$html = ob_get_clean();

		if ( preg_match( '/name="noncename__pad_hide" value="([^"]+)"/', $html, $matches ) ) {
			return $matches[1];
		}
		return '';
	}

	/**
	 * Test that pad_content_meta_fields() outputs the checkbox (checked according to the saved value) and exactly one hidden nonce field.
	 * pad_content_meta_fields() が、保存値に応じてチェックしたチェックボックスと、hidden の nonce 欄を 1 つだけ出力することを確認する。
	 */
	public function test_pad_content_meta_fields() {
		$test_cases = array(
			array(
				'test_condition_name' => '保存値が true => チェック済みのチェックボックスを出力する',
				'saved'               => 'true',
				'expected_checked'    => true,
			),
			array(
				'test_condition_name' => '保存値が空文字（解除後の値） => チェックなしのチェックボックスを出力する',
				'saved'               => '',
				'expected_checked'    => false,
			),
			array(
				'test_condition_name' => '保存値なし => チェックなしのチェックボックスを出力する',
				'saved'               => null,
				'expected_checked'    => false,
			),
		);

		foreach ( $test_cases as $case ) {
			$post_id = self::factory()->post->create();
			if ( null !== $case['saved'] ) {
				update_post_meta( $post_id, 'pad_hide_post_author', $case['saved'] );
			}

			$_SERVER['REQUEST_URI'] = '/wp-admin/post.php?post=' . $post_id . '&action=edit';
			$GLOBALS['post']        = get_post( $post_id );

			ob_start();
			pad_content_meta_fields();
			$html = ob_get_clean();

			$this->assertSame( 1, preg_match_all( '/<input type="hidden" id="noncename__pad_hide" name="noncename__pad_hide" value="[^"]+" \/>/', $html ), $case['test_condition_name'] . '（hidden の nonce 欄が 1 つ）' );
			$this->assertSame( 1, substr_count( $html, 'type="checkbox" id="pad_hide_post_author" name="pad_hide_post_author" value="true"' ), $case['test_condition_name'] . '（チェックボックスが 1 つ）' );
			$is_checked = ( false !== strpos( $html, 'value="true" checked>' ) );
			$this->assertSame( $case['expected_checked'], $is_checked, $case['test_condition_name'] );
		}
	}

	/**
	 * Test that pad_save_hide_items() verifies the nonce and the user's permission to edit the post, then saves the setting.
	 * It is invoked through the same hooks as real saves: wp_update_post() -> save_post (classic editor, quick edit) and the REST API.
	 * The nonce is taken from the output of pad_content_meta_fields(), so a mismatch between the form and the save handler is detected too.
	 * pad_save_hide_items() が nonce と投稿の編集権限を確認したうえで、設定を保存することを確認する。
	 * 実際の保存と同じフック経由で呼び出す: wp_update_post() → save_post（クラシックエディタ・クイック編集）と REST API。
	 * nonce は pad_content_meta_fields() の出力から取り出すため、フォームと保存処理の食い違いも検出できる。
	 */
	public function test_pad_save_hide_items() {
		$users = array(
			'administrator' => self::factory()->user->create( array( 'role' => 'administrator' ) ),
			'editor'        => self::factory()->user->create( array( 'role' => 'editor' ) ),
			'author'        => self::factory()->user->create( array( 'role' => 'author' ) ),
			'contributor'   => self::factory()->user->create( array( 'role' => 'contributor' ) ),
			'subscriber'    => self::factory()->user->create( array( 'role' => 'subscriber' ) ),
			// Owner of the posts the users above are not allowed to edit (authors can edit only their own posts).
			// 上のユーザーが編集できない投稿の持ち主（投稿者は自分の投稿しか編集できない）。
			'other_author'  => self::factory()->user->create( array( 'role' => 'author' ) ),
		);

		$test_cases = array(
			array(
				'test_condition_name' => '管理者が自分の投稿で nonce 付きでチェックを入れる（保存値なし） => true が保存される',
				'route'               => 'update_post',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => null,
				'nonce'               => 'valid',
				'checkbox'            => 'true',
				'expected'            => 'true',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きでチェックを外す（保存値 true） => 解除され空になる',
				'route'               => 'update_post',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => 'true',
				'nonce'               => 'valid',
				'checkbox'            => null,
				'expected'            => '',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きでチェックを入れたまま保存する（保存値 true） => true のまま',
				'route'               => 'update_post',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => 'true',
				'nonce'               => 'valid',
				'checkbox'            => 'true',
				'expected'            => 'true',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きでチェックなしのまま保存する（保存値なし） => 空のまま',
				'route'               => 'update_post',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => null,
				'nonce'               => 'valid',
				'checkbox'            => null,
				'expected'            => '',
			),
			array(
				'test_condition_name' => '編集者が他の人の投稿で nonce 付きでチェックを入れる => true が保存される（編集者は他の人の投稿も編集できる）',
				'route'               => 'update_post',
				'actor'               => 'editor',
				'post_owner'          => 'other_author',
				'saved'               => null,
				'nonce'               => 'valid',
				'checkbox'            => 'true',
				'expected'            => 'true',
			),
			array(
				'test_condition_name' => '投稿者が自分の投稿で nonce 付きでチェックを入れる => true が保存される',
				'route'               => 'update_post',
				'actor'               => 'author',
				'post_owner'          => 'author',
				'saved'               => null,
				'nonce'               => 'valid',
				'checkbox'            => 'true',
				'expected'            => 'true',
			),
			array(
				'test_condition_name' => '投稿者が自分の投稿で nonce 付きでチェックを外す（保存値 true） => 解除され空になる',
				'route'               => 'update_post',
				'actor'               => 'author',
				'post_owner'          => 'author',
				'saved'               => 'true',
				'nonce'               => 'valid',
				'checkbox'            => null,
				'expected'            => '',
			),
			array(
				'test_condition_name' => '投稿者が他の人の投稿で nonce 付きでチェックを外す（保存値 true） => 保存されず true のまま',
				'route'               => 'update_post',
				'actor'               => 'author',
				'post_owner'          => 'other_author',
				'saved'               => 'true',
				'nonce'               => 'valid',
				'checkbox'            => null,
				'expected'            => 'true',
			),
			array(
				'test_condition_name' => '投稿者が他の人の投稿で nonce 付きでチェックを入れる（保存値なし） => 保存されず空のまま',
				'route'               => 'update_post',
				'actor'               => 'author',
				'post_owner'          => 'other_author',
				'saved'               => null,
				'nonce'               => 'valid',
				'checkbox'            => 'true',
				'expected'            => '',
			),
			array(
				'test_condition_name' => '寄稿者が他の人の投稿で nonce 付きでチェックを入れる => 保存されず空のまま',
				'route'               => 'update_post',
				'actor'               => 'contributor',
				'post_owner'          => 'other_author',
				'saved'               => null,
				'nonce'               => 'valid',
				'checkbox'            => 'true',
				'expected'            => '',
			),
			array(
				'test_condition_name' => '購読者が他の人の投稿で nonce 付きでチェックを入れる => 保存されず空のまま',
				'route'               => 'update_post',
				'actor'               => 'subscriber',
				'post_owner'          => 'other_author',
				'saved'               => null,
				'nonce'               => 'valid',
				'checkbox'            => 'true',
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'nonce が無い（クイック編集・WP-CLI・cron 相当）でチェックボックスの値も無い（保存値 true） => 何もせず true のまま',
				'route'               => 'update_post',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => 'true',
				'nonce'               => 'none',
				'checkbox'            => null,
				'expected'            => 'true',
			),
			array(
				'test_condition_name' => 'nonce が無いのにチェックボックスの値だけ送られる（保存値なし） => 保存されず空のまま',
				'route'               => 'update_post',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => null,
				'nonce'               => 'none',
				'checkbox'            => 'true',
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'nonce が不正（書き換えられている） => 保存されず空のまま',
				'route'               => 'update_post',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => null,
				'nonce'               => 'invalid',
				'checkbox'            => 'true',
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'nonce の action 名が違う => 保存されず空のまま',
				'route'               => 'update_post',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => null,
				'nonce'               => 'other_action',
				'checkbox'            => 'true',
				'expected'            => '',
			),
			array(
				'test_condition_name' => 'REST API でタイトルだけを更新する（保存値 true） => nonce が無くても true のまま',
				'route'               => 'rest',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => 'true',
				'nonce'               => 'none',
				'checkbox'            => null,
				'rest_meta'           => null,
				'expected'            => 'true',
			),
			array(
				'test_condition_name' => 'REST API で meta に true を送る（保存値なし） => true が保存される',
				'route'               => 'rest',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => null,
				'nonce'               => 'none',
				'checkbox'            => null,
				'rest_meta'           => array( 'pad_hide_post_author' => 'true' ),
				'expected'            => 'true',
			),
			array(
				'test_condition_name' => 'REST API で meta に空文字を送る（保存値 true） => 解除され空になる',
				'route'               => 'rest',
				'actor'               => 'administrator',
				'post_owner'          => 'administrator',
				'saved'               => 'true',
				'nonce'               => 'none',
				'checkbox'            => null,
				'rest_meta'           => array( 'pad_hide_post_author' => '' ),
				'expected'            => '',
			),
		);

		foreach ( $test_cases as $case ) {
			// Creating a post also runs pad_save_hide_items() via save_post, so clear the previous case's $_POST first.
			// 投稿の作成でも save_post 経由で pad_save_hide_items() が走るため、前のケースの $_POST を先に空にする。
			$_POST = array();

			$post_id = self::factory()->post->create(
				array(
					'post_author' => $users[ $case['post_owner'] ],
					'post_status' => 'publish',
				)
			);
			if ( null !== $case['saved'] ) {
				update_post_meta( $post_id, 'pad_hide_post_author', $case['saved'] );
			}

			// A nonce is tied to the current user, so switch the user before creating it.
			// nonce は現在のユーザーに紐づくため、ユーザーを切り替えてから作る。
			wp_set_current_user( $users[ $case['actor'] ] );
			if ( 'valid' === $case['nonce'] ) {
				$_POST['noncename__pad_hide'] = $this->get_nonce_from_meta_box( $post_id );
			} elseif ( 'invalid' === $case['nonce'] ) {
				$_POST['noncename__pad_hide'] = 'invalid0000';
			} elseif ( 'other_action' === $case['nonce'] ) {
				$_POST['noncename__pad_hide'] = wp_create_nonce( 'other_action' );
			}
			if ( null !== $case['checkbox'] ) {
				$_POST['pad_hide_post_author'] = $case['checkbox'];
			}

			if ( 'rest' === $case['route'] ) {
				// A REST request carries no form data, so $_POST stays empty.
				// REST リクエストにはフォームの値が無いため、$_POST は空のままにする。
				$request = new WP_REST_Request( 'POST', '/wp/v2/posts/' . $post_id );
				$request->set_param( 'title', 'updated by rest' );
				if ( null !== $case['rest_meta'] ) {
					$request->set_param( 'meta', $case['rest_meta'] );
				}
				$response = rest_do_request( $request );
				$this->assertSame( 200, $response->get_status(), $case['test_condition_name'] . '（前提: REST の更新が成功すること）' );
			} else {
				// Go through the save_post hook, as saving in the classic editor does.
				// クラシックエディタでの保存と同じく save_post フック経由で呼び出す。
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => 'updated',
					)
				);
			}

			$this->assertSame( $case['expected'], get_post_meta( $post_id, 'pad_hide_post_author', true ), $case['test_condition_name'] );
		}
	}
}
