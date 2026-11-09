<?php
/**
 * Class TermColorSanitizeTest
 *
 * @package vk-post-author-display
 */

class TermColorSanitizeTest extends WP_UnitTestCase {

	/**
	 * Test that sanitize_hex() only accepts 3 or 6 digit hex color values.
	 * sanitize_hex() が3桁・6桁の16進数カラー値のみを受け付けることを確認する。
	 */
	public function test_sanitize_hex() {
		$cases = array(
			array( '#ff0000', 'ff0000' ),
			array( '#abc', 'abc' ),
			// Previously accepted because the old regex lacked a start anchor (^) and only
			// checked the trailing characters; this string ends in '000' which looks like
			// valid hex. It must now be rejected.
			// 旧正規表現には先頭アンカー（^）が無く末尾だけを見ていたため、16進数に見える
			// '000' で終わるこの文字列が誤って許可されていた。現在は拒否されることを確認する。
			array( 'notacolor000', '' ),
			// Attribute-breakout payload that also ends in hex-looking characters; must be rejected.
			// 属性からの脱出を狙った値も、末尾が16進数に見えるだけでは許可しないことを確認する。
			array( '" onmouseover="alert(1)//aaa', '' ),
			array( '#ff0000ff', '' ),
			// PCRE's $ anchor matches just before a trailing newline, so a value ending in
			// "\n" could previously slip through unsanitized; it must now be rejected.
			// PCRE の $ は末尾の改行の直前にもマッチするため、末尾に改行を付けた値が
			// サニタイズを素通りしていた。現在は拒否されることを確認する。
			array( "abc\n", '' ),
		);

		foreach ( $cases as [ $input, $expected ] ) {
			$this->assertSame( $expected, Vk_term_color::sanitize_hex( $input ), "input: {$input}" );
		}
	}

	/**
	 * Test that a non-string value (e.g. an array from a crafted form submission) does not
	 * raise a TypeError and is rejected instead.
	 * 配列など非文字列値を渡しても TypeError にならず、空文字として拒否されることを確認する。
	 */
	public function test_sanitize_hex_rejects_non_string_without_error() {
		$this->assertSame( '', Vk_term_color::sanitize_hex( array( '#ff0000' ) ) );
	}

	/**
	 * Test that the sanitize_callback registered for term_color is actually applied when
	 * saving term meta through the public API.
	 * term_color に登録された sanitize_callback が、term meta 保存時に実際に適用されることを確認する。
	 */
	public function test_term_color_meta_save_sanitizes_invalid_value() {
		$term_id = self::factory()->term->create();

		update_term_meta( $term_id, 'term_color', 'notacolor000' );
		$this->assertSame( '', get_term_meta( $term_id, 'term_color', true ) );

		update_term_meta( $term_id, 'term_color', '#00ff00' );
		$this->assertSame( '00ff00', get_term_meta( $term_id, 'term_color', true ) );
	}

	/**
	 * Test that save_term_meta_color() verifies the nonce and the user's permission to edit the term,
	 * then sanitizes and saves the submitted color.
	 * It is invoked through the same hooks as the admin screens: wp_update_term() -> edited_category
	 * (route = update) and wp_insert_term() -> create_category (route = insert), so the hook wiring is tested too.
	 * save_term_meta_color() が nonce とタームの編集権限を確認したうえで、送信された色を整形して保存することを確認する。
	 * 管理画面での保存と同じく wp_update_term() → edited_category フック経由（route = update）と、
	 * 新規ターム作成と同じく wp_insert_term() → create_category フック経由（route = insert）で呼び出し、フックの配線ごと検証する。
	 */
	public function test_save_term_meta_color() {
		// Administrators have the category edit capability (manage_categories); authors and subscribers do not.
		// カテゴリーの編集権限（manage_categories）は管理者にはあり、投稿者・購読者には無い。
		$admin_id      = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$author_id     = self::factory()->user->create( array( 'role' => 'author' ) );
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		// The nonce action must match basename( __FILE__ ) of the class file.
		// nonce のアクション名はクラスファイルの basename( __FILE__ ) と一致させる必要がある。
		$nonce_action = 'class.term-color.php';

		// Value stored before each case (saved without '#' because it passes through sanitize_hex).
		// 各ケースの前に入れておく保存済みの値（sanitize_hex を通るため # は除かれて保存される）。
		$saved = '00ff00';

		$test_cases = array(
			array(
				'test_condition_name' => '管理者が nonce 付きで #ff0000 を送信 => ff0000 が保存される',
				'route'               => 'update',
				'user_id'             => $admin_id,
				'nonce'               => 'valid',
				'term_color'          => '#ff0000',
				'expected'            => 'ff0000',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きで # なしの ff0000 を送信 => ff0000 が保存される',
				'route'               => 'update',
				'user_id'             => $admin_id,
				'nonce'               => 'valid',
				'term_color'          => 'ff0000',
				'expected'            => 'ff0000',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きで 3 桁の #abc を送信 => abc が保存される',
				'route'               => 'update',
				'user_id'             => $admin_id,
				'nonce'               => 'valid',
				'term_color'          => '#abc',
				'expected'            => 'abc',
			),
			array(
				'test_condition_name' => '投稿者（カテゴリーの編集権限なし）が nonce 付きで送信 => 保存されず既存の値のまま',
				'route'               => 'update',
				'user_id'             => $author_id,
				'nonce'               => 'valid',
				'term_color'          => '#ff0000',
				'expected'            => $saved,
			),
			array(
				'test_condition_name' => '購読者（カテゴリーの編集権限なし）が nonce 付きで送信 => 保存されず既存の値のまま',
				'route'               => 'update',
				'user_id'             => $subscriber_id,
				'nonce'               => 'valid',
				'term_color'          => '#ff0000',
				'expected'            => $saved,
			),
			array(
				'test_condition_name' => 'nonce が無い => 保存されず既存の値のまま',
				'route'               => 'update',
				'user_id'             => $admin_id,
				'nonce'               => 'none',
				'term_color'          => '#ff0000',
				'expected'            => $saved,
			),
			array(
				'test_condition_name' => 'nonce のアクション名が違う => 保存されず既存の値のまま',
				'route'               => 'update',
				'user_id'             => $admin_id,
				'nonce'               => 'other',
				'term_color'          => '#ff0000',
				'expected'            => $saved,
			),
			array(
				'test_condition_name' => 'term_color が送信されない => 既存の値のまま',
				'route'               => 'update',
				'user_id'             => $admin_id,
				'nonce'               => 'valid',
				'term_color'          => null,
				'expected'            => $saved,
			),
			array(
				'test_condition_name' => '配列を送信 => 警告を出さずに空文字が保存される',
				'route'               => 'update',
				'user_id'             => $admin_id,
				'nonce'               => 'valid',
				'term_color'          => array( '#ff0000' ),
				'expected'            => '',
			),
			array(
				'test_condition_name' => '16進数カラーではない値を送信 => 空文字が保存される',
				'route'               => 'update',
				'user_id'             => $admin_id,
				'nonce'               => 'valid',
				'term_color'          => 'notacolor000',
				'expected'            => '',
			),
			array(
				'test_condition_name' => '管理者が nonce 付きで #ff0000 を送信して新規ターム作成 => 新規タームに ff0000 が保存される',
				'route'               => 'insert',
				'user_id'             => $admin_id,
				'nonce'               => 'valid',
				'term_color'          => '#ff0000',
				'expected'            => 'ff0000',
			),
			array(
				'test_condition_name' => '購読者（カテゴリーの編集権限なし）が nonce 付きで送信して新規ターム作成 => 保存されず空のまま',
				'route'               => 'insert',
				'user_id'             => $subscriber_id,
				'nonce'               => 'valid',
				'term_color'          => '#ff0000',
				'expected'            => '',
			),
		);

		foreach ( $test_cases as $case ) {
			// Creating a term also runs save_term_meta_color() via create_category, so clear the previous case's $_POST first.
			// ターム作成時にも create_category 経由で save_term_meta_color() が走るため、前のケースの $_POST を先に空にする。
			$_POST = array();

			// The update route needs a term with a stored value; for the insert route, wp_insert_term() itself is under test.
			// update 経路は保存済みの値を持つタームを先に用意する（insert 経路は wp_insert_term() 自体が検証対象）。
			$term_id = 0;
			if ( 'update' === $case['route'] ) {
				$term_id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );
				update_term_meta( $term_id, 'term_color', '#' . $saved );
			}

			// A nonce is tied to the current user, so switch the user before creating it.
			// nonce は現在のユーザーに紐づくため、ユーザーを切り替えてから作る。
			wp_set_current_user( $case['user_id'] );
			if ( 'valid' === $case['nonce'] ) {
				$_POST['term_color_nonce'] = wp_create_nonce( $nonce_action );
			} elseif ( 'other' === $case['nonce'] ) {
				$_POST['term_color_nonce'] = wp_create_nonce( 'other_action' );
			}

			// Reproduce the slashes WordPress adds to $_POST.
			// WordPress が $_POST に付けるスラッシュを再現する。
			if ( null !== $case['term_color'] ) {
				$_POST['term_color'] = wp_slash( $case['term_color'] );
			}

			if ( 'insert' === $case['route'] ) {
				// Go through the create_category hook, as "Add New Category" in the admin does.
				// 管理画面の「新規カテゴリーを追加」と同じく create_category フック経由で呼び出す。
				$inserted = wp_insert_term( uniqid( 'term-color-insert-' ), 'category' );
				$this->assertNotWPError( $inserted, $case['test_condition_name'] . '（前提: タームが作成できること）' );
				$term_id = $inserted['term_id'];
			} else {
				// Go through the edited_category hook, as saving in the admin does.
				// 管理画面での保存と同じく edited_category フック経由で呼び出す。
				wp_update_term( $term_id, 'category', array( 'description' => 'updated' ) );
			}

			$this->assertSame( $case['expected'], get_term_meta( $term_id, 'term_color', true ), $case['test_condition_name'] );
		}

		$_POST = array();
		wp_set_current_user( 0 );
	}

	/**
	 * Test that get_term_color() returns the stored value with a leading '#', and returns the same color
	 * for a legacy value that was stored with the '#' included.
	 * get_term_color() が保存済みの値を # 付きで返し、# 付きで保存された旧い値でも同じ色を返すことを確認する。
	 */
	public function test_get_term_color() {
		$test_cases = array(
			array(
				'test_condition_name' => '# なしで保存された値 => # 付きで返す',
				'saved'               => '00ff00',
				'expected'            => '#00ff00',
			),
			array(
				'test_condition_name' => '# 付きで保存された旧い値 => # を重ねずに同じ色を返す',
				'saved'               => '#00ff00',
				'expected'            => '#00ff00',
			),
			array(
				'test_condition_name' => '保存値が無い => 既定色を返す',
				'saved'               => null,
				'expected'            => '#999999',
			),
		);

		foreach ( $test_cases as $case ) {
			$term_id = self::factory()->term->create( array( 'taxonomy' => 'category' ) );

			// Store the value bypassing the sanitize_callback to reproduce a legacy value saved with '#'.
			// The callback is registered with an instance ( array( $this, 'sanitize_hex' ) ), so it cannot be
			// removed by a static-array remove_filter(); detach every callback of the hook and restore them afterwards.
			// 旧い保存値（# 付き）を再現するため、sanitize_callback を通さずに保存する。
			// コールバックはインスタンス（ array( $this, 'sanitize_hex' ) ）で登録されていて静的配列の remove_filter() では外れないため、
			// フックのコールバックをいったん退避して全解除し、保存後に戻す。
			if ( null !== $case['saved'] ) {
				global $wp_filter;
				$hook_name  = 'sanitize_term_meta_term_color';
				$saved_hook = isset( $wp_filter[ $hook_name ] ) ? clone $wp_filter[ $hook_name ] : null;
				remove_all_filters( $hook_name );
				update_term_meta( $term_id, 'term_color', $case['saved'] );
				if ( null !== $saved_hook ) {
					$wp_filter[ $hook_name ] = $saved_hook;
				}
				$this->assertSame( $case['saved'], get_term_meta( $term_id, 'term_color', true ), $case['test_condition_name'] . '（前提: 保存値がそのまま入っていること）' );
			}

			$this->assertSame( $case['expected'], Vk_term_color::get_term_color( $term_id ), $case['test_condition_name'] );
		}
	}
}
