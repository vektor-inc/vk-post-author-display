<?php
/**
 * Class AdminProfileTest
 *
 * @package vk-post-author-display
 */

class AdminProfileTest extends WP_UnitTestCase {

	/**
	 * Globals changed by the tests. They are restored in tearDown().
	 * テストで書き換えるグローバルの退避先。tearDown() で戻す。
	 *
	 * @var array
	 */
	private $original_globals = array();

	/**
	 * Back up the globals the tests change.
	 * テストで書き換えるグローバルを退避する。
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_globals = array(
			'post_data'    => $_POST,
			'request_uri'  => isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null,
			'profile_user' => isset( $GLOBALS['profile_user'] ) ? $GLOBALS['profile_user'] : null,
			'profileuser'  => isset( $GLOBALS['profileuser'] ) ? $GLOBALS['profileuser'] : null,
			'user_id'      => get_current_user_id(),
		);
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

		foreach ( array( 'profile_user', 'profileuser' ) as $global_name ) {
			if ( null !== $this->original_globals[ $global_name ] ) {
				$GLOBALS[ $global_name ] = $this->original_globals[ $global_name ];
			} else {
				unset( $GLOBALS[ $global_name ] );
			}
		}

		parent::tearDown();
	}

	/**
	 * Render the profile picture row of the given user's profile screen as the current user.
	 * 現在のユーザーとして、指定したユーザーのプロフィール編集画面のプロフィール写真の行を描画する。
	 *
	 * @param int    $profile_user_id User ID of the profile being edited. / 編集中のプロフィールのユーザー ID。
	 * @param string $request_uri     Request URI of the screen. / 画面の REQUEST_URI。
	 * @return string Output HTML. / 出力された HTML。
	 */
	private function render_profile_image_form( $profile_user_id, $request_uri = '' ) {
		$_SERVER['REQUEST_URI']  = '' !== $request_uri ? $request_uri : '/wp-admin/user-edit.php?user_id=' . $profile_user_id;
		$GLOBALS['profile_user'] = get_userdata( $profile_user_id );
		$GLOBALS['profileuser']  = $GLOBALS['profile_user'];

		ob_start();
		add_user_profile_image_form( true );
		return ob_get_clean();
	}

	/**
	 * Render the form as the current user and return the nonce value of its hidden field.
	 * 現在のユーザーとしてフォームを描画し、hidden 欄の nonce の値を返す。
	 *
	 * @param int $profile_user_id User ID of the profile being edited. / 編集中のプロフィールのユーザー ID。
	 * @return string Nonce value. An empty string when the field is not output. / nonce の値。欄が出力されないときは空文字。
	 */
	private function get_nonce_from_profile_form( $profile_user_id ) {
		$html = $this->render_profile_image_form( $profile_user_id );
		if ( preg_match( '/name="pad_user_profile_image_nonce" value="([^"]+)"/', $html, $matches ) ) {
			return $matches[1];
		}
		return '';
	}

	/**
	 * Test that add_user_profile_image_form() outputs the profile picture row with exactly one hidden nonce field only on the profile screens.
	 * add_user_profile_image_form() が、プロフィール編集画面でだけ、hidden の nonce 欄を 1 つ含むプロフィール写真の行を出力することを確認する。
	 */
	public function test_add_user_profile_image_form() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$test_cases = array(
			array(
				'test_condition_name' => 'プロフィール画面（profile.php）で保存済みの写真あり => 値入りの hidden 欄・ボタン・hidden の nonce 欄を出力する',
				'request_uri'         => '/wp-admin/profile.php',
				'saved'               => '123',
				'expected_output'     => true,
			),
			array(
				'test_condition_name' => 'ユーザー編集画面（user-edit.php）で保存済みの写真なし => 空の hidden 欄・ボタン・hidden の nonce 欄を出力する',
				'request_uri'         => '/wp-admin/user-edit.php?user_id=' . $user_id,
				'saved'               => '',
				'expected_output'     => true,
			),
			array(
				'test_condition_name' => 'プロフィール編集画面以外（index.php） => 何も出力しない',
				'request_uri'         => '/wp-admin/index.php',
				'saved'               => '123',
				'expected_output'     => false,
			),
		);

		foreach ( $test_cases as $case ) {
			update_user_meta( $user_id, 'user_profile_image', $case['saved'] );

			$html = $this->render_profile_image_form( $user_id, $case['request_uri'] );

			if ( ! $case['expected_output'] ) {
				$this->assertSame( '', $html, $case['test_condition_name'] );
				continue;
			}

			$this->assertSame( 1, preg_match_all( '/<input type="hidden" id="pad_user_profile_image_nonce" name="pad_user_profile_image_nonce" value="[^"]+" \/>/', $html ), $case['test_condition_name'] . '（hidden の nonce 欄が 1 つ）' );
			$this->assertSame( 1, substr_count( $html, '<input id="user_profile_image" type="hidden" name="user_profile_image" value="' . $case['saved'] . '" />' ), $case['test_condition_name'] . '（写真の ID を入れる hidden 欄が 1 つ）' );
			$this->assertSame( 1, substr_count( $html, 'id="media_user_profile_image"' ), $case['test_condition_name'] . '（「画像を選択」ボタンが 1 つ）' );
			$this->assertSame( 1, substr_count( $html, 'id="media_reset_user_profile_image"' ), $case['test_condition_name'] . '（「画像をリセット」ボタンが 1 つ）' );
		}
	}

	/**
	 * Test that pad_update_user_profile_image() verifies the nonce and the user's permission to edit the user,
	 * then saves the profile picture (attachment ID) only when it is a positive integer or an empty string.
	 * It is invoked through the same hook as the profile screens: wp_update_user() -> profile_update.
	 * The nonce is taken from the output of add_user_profile_image_form(), so a mismatch between the form and the save handler is detected too.
	 * pad_update_user_profile_image() が nonce とユーザーの編集権限を確認したうえで、
	 * プロフィール写真（画像の ID）を、正の整数か空文字のときだけ保存することを確認する。
	 * プロフィール編集画面と同じ profile_update フック経由（wp_update_user()）で呼び出す。
	 * nonce は add_user_profile_image_form() の出力から取り出すため、フォームと保存処理の食い違いも検出できる。
	 */
	public function test_pad_update_user_profile_image() {
		$users = array(
			'administrator' => self::factory()->user->create( array( 'role' => 'administrator' ) ),
			'author'        => self::factory()->user->create( array( 'role' => 'author' ) ),
			'subscriber'    => self::factory()->user->create( array( 'role' => 'subscriber' ) ),
			// A user whose profile is edited by others. Only administrators can edit it.
			// 他の人に編集されるユーザー（編集できるのは管理者だけ）。
			'target'        => self::factory()->user->create( array( 'role' => 'subscriber' ) ),
		);

		$test_cases = array(
			array(
				'test_condition_name' => '管理者が自分のプロフィールで nonce 付きで写真を選ぶ（保存値なし） => 画像の ID が保存される',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => null,
				'nonce'               => 'valid',
				'posted'              => '123',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きで写真を別の写真に変える（保存値 123） => 新しい画像の ID が保存される',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => '456',
				'expected'            => '456',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きで写真を解除する（保存値 123、空文字を送信） => 空文字が保存される',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => '',
				'expected'            => '',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きで保存済みと同じ値を送る（保存値 123） => 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => '123',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きで空文字を送る（保存値なし） => 空のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => null,
				'nonce'               => 'valid',
				'posted'              => '',
				'expected'            => '',
			),
			array(
				'test_condition_name' => '購読者が自分のプロフィールで nonce 付きで写真を選ぶ => 画像の ID が保存される（自分のプロフィールは誰でも編集できる）',
				'actor'               => 'subscriber',
				'profile'             => 'subscriber',
				'saved'               => null,
				'nonce'               => 'valid',
				'posted'              => '456',
				'expected'            => '456',
			),
			array(
				'test_condition_name' => '投稿者が自分のプロフィールで nonce 付きで写真を選ぶ => 画像の ID が保存される',
				'actor'               => 'author',
				'profile'             => 'author',
				'saved'               => null,
				'nonce'               => 'valid',
				'posted'              => '456',
				'expected'            => '456',
			),
			array(
				'test_condition_name' => '管理者が他の利用者のプロフィールで nonce 付きで写真を選ぶ => その利用者に画像の ID が保存される',
				'actor'               => 'administrator',
				'profile'             => 'target',
				'saved'               => null,
				'nonce'               => 'valid',
				'posted'              => '789',
				'expected'            => '789',
			),
			array(
				'test_condition_name' => '投稿者が他の利用者のプロフィールで nonce 付きで写真を選ぶ => 保存されず空のまま',
				'actor'               => 'author',
				'profile'             => 'target',
				'saved'               => null,
				'nonce'               => 'valid',
				'posted'              => '789',
				'expected'            => '',
			),
			array(
				'test_condition_name' => '購読者が他の利用者のプロフィールで nonce 付きで写真を解除する（保存値 123） => 保存されず 123 のまま',
				'actor'               => 'subscriber',
				'profile'             => 'target',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => '',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => 'nonce が無い（REST API・WP-CLI・他のコードの wp_update_user() 相当）のに写真の値だけ送られる（保存値 123） => 保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'none',
				'posted'              => '456',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => 'nonce が不正（書き換えられている） => 保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'invalid',
				'posted'              => '456',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '別のユーザーのプロフィール用の nonce を使う => 保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'other_profile',
				'posted'              => '456',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => 'nonce の action 名が違う => 保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'other_action',
				'posted'              => '456',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '数字以外（abc）を送る（保存値 123） => 保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => 'abc',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '数字に文字が続く値（12abc）を送る（保存値 123） => 12 に化けず保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => '12abc',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '負の数（-5）を送る（保存値 123） => 5 に化けず保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => '-5',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '小数（1.5）を送る（保存値 123） => 保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => '1.5',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '0 を送る（保存値 123） => 画像の ID として使えないため保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => '0',
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '配列を送る（保存値 123） => 警告を出さず保存されず 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => array( '456' ),
				'expected'            => '123',
			),
			array(
				'test_condition_name' => '写真の値が送られない（他の項目だけの更新、保存値 123） => 123 のまま',
				'actor'               => 'administrator',
				'profile'             => 'administrator',
				'saved'               => '123',
				'nonce'               => 'valid',
				'posted'              => null,
				'expected'            => '123',
			),
		);

		foreach ( $test_cases as $case ) {
			$_POST      = array();
			$profile_id = $users[ $case['profile'] ];

			if ( null !== $case['saved'] ) {
				update_user_meta( $profile_id, 'user_profile_image', $case['saved'] );
			} else {
				delete_user_meta( $profile_id, 'user_profile_image' );
			}

			// A nonce is tied to the current user, so switch the user before creating it.
			// nonce は現在のユーザーに紐づくため、ユーザーを切り替えてから作る。
			wp_set_current_user( $users[ $case['actor'] ] );
			if ( 'valid' === $case['nonce'] ) {
				$_POST['pad_user_profile_image_nonce'] = $this->get_nonce_from_profile_form( $profile_id );
			} elseif ( 'other_profile' === $case['nonce'] ) {
				// A nonce taken from another user's profile screen.
				// 別のユーザーのプロフィール画面から取り出した nonce。
				$other_profile_id                      = $users['target'] === $profile_id ? $users['administrator'] : $users['target'];
				$_POST['pad_user_profile_image_nonce'] = $this->get_nonce_from_profile_form( $other_profile_id );
			} elseif ( 'invalid' === $case['nonce'] ) {
				$_POST['pad_user_profile_image_nonce'] = 'invalid0000';
			} elseif ( 'other_action' === $case['nonce'] ) {
				$_POST['pad_user_profile_image_nonce'] = wp_create_nonce( 'other_action' );
			}

			// Reproduce the slashes WordPress adds to $_POST.
			// WordPress が $_POST に付けるスラッシュを再現する。
			if ( null !== $case['posted'] ) {
				$_POST['user_profile_image'] = wp_slash( $case['posted'] );
			}

			// Go through the profile_update hook, as saving on the profile screen does.
			// プロフィール画面での保存と同じく profile_update フック経由で呼び出す。
			$result = wp_update_user(
				array(
					'ID'          => $profile_id,
					'description' => 'updated',
				)
			);
			$this->assertSame( $profile_id, $result, $case['test_condition_name'] . '（前提: ユーザーの更新が成功すること）' );

			$this->assertSame( $case['expected'], get_user_meta( $profile_id, 'user_profile_image', true ), $case['test_condition_name'] );
		}
	}
}
