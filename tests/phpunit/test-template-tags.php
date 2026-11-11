<?php
/**
 * Class TemplateTagsTest
 *
 * @package Vk_All_In_One_Expansion_Unit
 */
 /*
 cd /app
 bash setup-phpunit.sh
 source ~/.bashrc
 cd $(wp plugin path --dir vk-all-in-one-expansion-unit)
 phpunit
 */


class TemplateTagsTest extends WP_UnitTestCase {

	function test_vk_the_post_type_check_list_saved_array_convert() {

		$tests = array(
			array(
				'option'  => array(
					'post' => true,
					'info' => '',
				),
				'correct' => array( 'post' ),
			),
			array(
				'option'  => array(
					'post' => true,
					'info' => true,
				),
				'correct' => array( 'post', 'info' ),
			),
			array(
				'option'  => array(
					'post' => 'true',
					'info' => true,
				),
				'correct' => array( 'post', 'info' ),
			),
		);

		print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_vk_the_post_type_check_list_saved_array_convert' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		foreach ( $tests as $key => $test_value ) {
			update_option( 'vkExUnit_Ads', $test_value['option'] );

			$return = vk_the_post_type_check_list_saved_array_convert( $test_value['option'] );

			// PHPunit
			$this->assertEquals( $test_value['correct'], $return );
			print PHP_EOL;
			// 帰り値が配列だから print してもエラーになるだけなのでコメントアウト
			// print 'return    :' . $return. PHP_EOL;
			// print 'correct   :' . $test_value['correct'] . PHP_EOL;
		}
	}

	/**
	 * vk_get_post_type() は $_SERVER['REQUEST_URI'] が未設定（WP-CLI / cron 相当）でも
	 * Undefined array key / strpos(null) の警告を出さず、slug を含む配列を返すことを確認する。
	 * また、メインクエリの post_type が配列で指定された場合（pre_get_posts で
	 * array( 'event', 'page' ) 等を set するケース）に "Array to string conversion" の
	 * 警告を出さず、先頭要素を文字列化した slug を返すことも確認する
	 * （Issue #158 / vk-all-in-one-expansion-unit#1375 相当の修正）。
	 *
	 * Also check the admin branch (a REQUEST_URI containing "wp-admin"): the post_type / post URL parameters are
	 * sanitized, a legitimate value returns the same slug as before, and an invalid value is never returned as is.
	 * 管理画面の分岐（REQUEST_URI に wp-admin を含む場合）も確認する。URL の post_type / post は整えられ、
	 * 正当な値はこれまでと同じ slug を返し、不正な値はそのままは返らない。
	 */
	function test_vk_get_post_type() {
		// go_to() で書き換えられる前に、変更前の $_SERVER['REQUEST_URI'] を退避する。
		$original_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;

		// フロントページに移動して $wp_query を用意する。
		$this->go_to( home_url( '/' ) );

		global $wp_query;

		// テスト後に復元するため、変更前の値を退避する。
		$original_post_type_query_var = isset( $wp_query->query_vars['post_type'] ) ? $wp_query->query_vars['post_type'] : null;
		// go_to() が WP::register_globals() でセットするグローバル $post（既定の「Hello world!」投稿等）を退避する。
		// これが残っていると get_post_type() が常に 'post' を返してしまい、
		// post_type クエリ変数を使う分岐（配列正規化の分岐）に一度も入らないため、明示的に空にする必要がある。
		$original_global_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		// 管理画面の分岐のケースで URL のパラメーターとして $_GET を書き換えるため、変更前の値を退避する。
		// Back up $_GET because the admin branch cases rewrite it as the URL parameters.
		$original_get = $_GET;

		// 配列を absint() に通すと 1 になるため、ID 1 の投稿を拾わないことを確かめられるよう、ID 1 の投稿（投稿タイプ post）を用意する。
		// absint() turns an array into 1, so prepare a post with ID 1 (post type "post") to verify that it is not picked up.
		if ( get_post( 1 ) ) {
			wp_update_post(
				array(
					'ID'        => 1,
					'post_type' => 'post',
				)
			);
		} else {
			wp_insert_post(
				array(
					'import_id'   => 1,
					'post_type'   => 'post',
					'post_status' => 'publish',
					'post_title'  => 'Post with ID 1',
				)
			);
		}
		$this->assertSame( 'post', get_post_type( 1 ), 'テストの前提: ID 1 の投稿が投稿タイプ post で存在すること' );

		// 管理画面の分岐の「post」パラメーター用に、固定ページを 1 件作る。
		// ID 1 の用意より後に作ること（先に作ると、この固定ページの ID が 1 になる環境で投稿タイプが書き換わる）。
		// Create one page for the "post" parameter of the admin branch cases.
		// Create it after preparing ID 1 (otherwise its post type gets rewritten in an environment where this page gets ID 1).
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$test_cases = array(
			array(
				'test_condition_name' => 'REQUEST_URI が通常どおり設定されている場合 => slug を含む配列を返す（正常系）',
				'request_uri'         => '/',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => false,
			),
			array(
				'test_condition_name' => 'REQUEST_URI が未設定（WP-CLI / cron 相当）=> Undefined array key / strpos(null) を出さず slug を含む配列を返す（境界値）',
				'request_uri'         => null,
				'unset_request_uri'   => true,
				'post_type_query_var' => null,
				'clear_global_post'   => false,
			),
			array(
				'test_condition_name' => 'メインクエリの post_type が配列で指定された場合（pre_get_posts で array( "event", "page" ) を set 相当）=> Array to string conversion 警告を出さず先頭要素を文字列化した slug を返す（異常系）',
				'request_uri'         => '/',
				'unset_request_uri'   => false,
				'post_type_query_var' => array( 'event', 'page' ),
				// グローバル $post が残っていると get_post_type() が 'post' を返し、
				// 配列正規化の分岐に到達できないため、このケースでは明示的に空にする。
				'clear_global_post'   => true,
			),
			// 以降は管理画面の分岐（REQUEST_URI に wp-admin を含む場合）。
			// グローバル $post が残っていると get_post_type() が 'post' を返し URL のパラメーターを見ないため、全ケースで空にする。
			// The following cases are for the admin branch (a REQUEST_URI containing "wp-admin").
			// A remaining global $post makes get_post_type() return 'post' and skips the URL parameters, so every case clears it.
			array(
				'test_condition_name' => '管理画面の URL で post_type=page => slug は page（正常系）',
				'request_uri'         => '/wp-admin/edit.php?post_type=page',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post_type' => 'page' ),
				'expected_slug'       => 'page',
			),
			array(
				'test_condition_name' => '管理画面の URL で post_type に大文字と _ - を含む（Event_Type-2） => 英小文字に整えた event_type-2（正常系）',
				'request_uri'         => '/wp-admin/edit.php?post_type=Event_Type-2',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post_type' => 'Event_Type-2' ),
				'expected_slug'       => 'event_type-2',
			),
			array(
				'test_condition_name' => '管理画面の URL で post_type に記号を含む => 英小文字・数字・_・- だけに整えた slug（異常系）',
				'request_uri'         => '/wp-admin/edit.php',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post_type' => 'page"><script>alert(1)</script>' ),
				'expected_slug'       => 'pagescriptalert1script',
			),
			array(
				'test_condition_name' => '管理画面の URL で post_type が配列（post_type[]=x） => sanitize_key() に渡さず、post_type の指定が無いときと同じ false（異常系）',
				'request_uri'         => '/wp-admin/edit.php',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post_type' => array( 'x' ) ),
				'expected_slug'       => false,
			),
			array(
				'test_condition_name' => '管理画面の URL で post_type が配列で post（文字列の投稿 ID）もある => post_type の指定が無いときと同じく post の投稿タイプ page（異常系）',
				'request_uri'         => '/wp-admin/post.php?action=edit',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array(
					'post_type' => array( 'event' ),
					'post'      => (string) $page_id,
				),
				'expected_slug'       => 'page',
			),
			array(
				'test_condition_name' => '管理画面の URL で post_type（文字列）と post の両方がある => post_type の値を優先して event（正常系）',
				'request_uri'         => '/wp-admin/post-new.php',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array(
					'post_type' => 'event',
					'post'      => (string) $page_id,
				),
				'expected_slug'       => 'event',
			),
			array(
				'test_condition_name' => '管理画面の URL で post に固定ページの ID => その投稿タイプ page（正常系）',
				'request_uri'         => '/wp-admin/post.php?action=edit',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post' => (string) $page_id ),
				'expected_slug'       => 'page',
			),
			array(
				'test_condition_name' => '管理画面で他のコードが $_GET の post に整数の投稿 ID を入れている => その投稿タイプ page（正常系）',
				'request_uri'         => '/wp-admin/post.php?action=edit',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post' => $page_id ),
				'expected_slug'       => 'page',
			),
			array(
				'test_condition_name' => '管理画面の URL で post が配列（post[]=x） => ID 1 の投稿を拾わず、post の指定が無いときと同じ false（異常系）',
				'request_uri'         => '/wp-admin/post.php?action=edit',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post' => array( (string) $page_id ) ),
				'expected_slug'       => false,
			),
			array(
				'test_condition_name' => '管理画面の URL で post が数字以外（abc） => 投稿タイプは決まらず false（異常系）',
				'request_uri'         => '/wp-admin/post.php?action=edit',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post' => 'abc' ),
				'expected_slug'       => false,
			),
			array(
				'test_condition_name' => '管理画面の URL で post_type も post も無い => 投稿タイプは決まらず false（境界値）',
				'request_uri'         => '/wp-admin/index.php',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array(),
				'expected_slug'       => false,
			),
			array(
				'test_condition_name' => '公開側の URL に wp-admin を含み post_type=event を付ける => 正当な値はそのまま event（正常系）',
				'request_uri'         => '/?post_type=event&redirect_to=/wp-admin/',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array(
					'post_type'   => 'event',
					'redirect_to' => '/wp-admin/',
				),
				'expected_slug'       => 'event',
			),
		);

		// sanitize_key() に文字列以外が渡されたかを観測する（最新の WordPress は本体が配列を空文字にするため、返り値だけでは渡ったかどうかを見分けられない）。
		// Observe whether a non-string value reaches sanitize_key() (the latest WordPress turns an array into an empty string, so the return value alone cannot tell).
		$non_string_sanitize_key_args = array();
		/**
		 * 文字列以外が sanitize_key() に渡されたら記録する。
		 * Record the value when a non-string value is passed to sanitize_key().
		 *
		 * @param string $sanitized_key sanitize_key() が整えた後の値 / The sanitized key.
		 * @param mixed  $raw_key       sanitize_key() に渡された元の値 / The raw value passed to sanitize_key().
		 * @return string 整えた後の値をそのまま返す / The sanitized key as is.
		 */
		$sanitize_key_observer = function ( $sanitized_key, $raw_key ) use ( &$non_string_sanitize_key_args ) {
			if ( ! is_string( $raw_key ) ) {
				$non_string_sanitize_key_args[] = $raw_key;
			}
			return $sanitized_key;
		};
		add_filter( 'sanitize_key', $sanitize_key_observer, 10, 2 );

		try {
			foreach ( $test_cases as $case ) {
				if ( $case['unset_request_uri'] ) {
					unset( $_SERVER['REQUEST_URI'] );
				} else {
					$_SERVER['REQUEST_URI'] = $case['request_uri'];
				}

				$GLOBALS['post'] = $case['clear_global_post'] ? null : $original_global_post;

				// 管理画面の分岐のケースだけ URL のパラメーターを指定する。それ以外は空にする。
				// Only the admin branch cases specify URL parameters. The other cases use none.
				$_GET = isset( $case['get'] ) ? $case['get'] : array();

				if ( null !== $case['post_type_query_var'] ) {
					$wp_query->query_vars['post_type'] = $case['post_type_query_var'];
				} else {
					unset( $wp_query->query_vars['post_type'] );
				}

				if ( $case['clear_global_post'] ) {
					// 配列正規化の分岐（post_type クエリ変数を使う分岐）に実際に入ることを担保するため、
					// その前提となる get_post_type() === false をここで確認しておく。
					$this->assertFalse( get_post_type(), $case['test_condition_name'] );
				}

				$non_string_sanitize_key_args = array();

				$actual = vk_get_post_type();

				$this->assertSame( array(), $non_string_sanitize_key_args, $case['test_condition_name'] . '（sanitize_key() に文字列以外を渡さないこと）' );
				$this->assertIsArray( $actual, $case['test_condition_name'] );
				$this->assertArrayHasKey( 'slug', $actual, $case['test_condition_name'] );

				if ( array_key_exists( 'expected_slug', $case ) ) {
					// 管理画面の分岐のケースは slug の値そのものを確認する（投稿タイプが決まらないときは false）。
					// The admin branch cases check the slug itself (false when the post type cannot be determined).
					$this->assertSame( $case['expected_slug'], $actual['slug'], $case['test_condition_name'] );
				} else {
					$this->assertIsString( $actual['slug'], $case['test_condition_name'] );
				}

				if ( is_array( $case['post_type_query_var'] ) ) {
					$this->assertSame( reset( $case['post_type_query_var'] ), $actual['slug'], $case['test_condition_name'] );
				}
			}
		} finally {
			// アサーション失敗（例外）時も含め、フィルター・$_SERVER・$_GET・$wp_query・グローバル $post を必ず復元して後続テストへの影響を防ぐ。
			remove_filter( 'sanitize_key', $sanitize_key_observer, 10 );
			$_GET = $original_get;

			if ( null !== $original_request_uri ) {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			} else {
				unset( $_SERVER['REQUEST_URI'] );
			}

			if ( null !== $original_post_type_query_var ) {
				$wp_query->query_vars['post_type'] = $original_post_type_query_var;
			} else {
				unset( $wp_query->query_vars['post_type'] );
			}

			$GLOBALS['post'] = $original_global_post;
		}
	}

	/**
	 * vk_sanitize_array() は配列以外が渡された場合でも「Undefined variable $return」の
	 * 警告を出さず、空配列を返すことを確認する。
	 */
	function test_vk_sanitize_array() {

		$tests = array(
			array(
				'test_condition_name' => '配列を渡した場合 => 各値が wp_kses_post でサニタイズされた配列を返す（正常系）',
				'input'               => array(
					'post' => 'true',
					'info' => '<script>alert(1)</script>true',
				),
				// info は WordPress のバージョンで結果が変わるため、末尾の文字列だけを期待値にする。
				'expected'            => array(
					'post' => 'true',
					'info' => 'true',
				),
				'script_removed_keys' => array( 'info' ),
			),
			array(
				'test_condition_name' => '空配列を渡した場合 => 空配列を返す（正常系）',
				'input'               => array(),
				'expected'            => array(),
			),
			array(
				'test_condition_name' => '配列以外（文字列）を渡した場合 => Undefined variable $return 警告を出さず空配列を返す（異常系）',
				'input'               => 'not-an-array',
				'expected'            => array(),
			),
			array(
				'test_condition_name' => '配列以外（null）を渡した場合 => Undefined variable $return 警告を出さず空配列を返す（境界値）',
				'input'               => null,
				'expected'            => array(),
			),
		);

		foreach ( $tests as $test_value ) {
			$actual = vk_sanitize_array( $test_value['input'] );

			if ( empty( $test_value['script_removed_keys'] ) ) {
				$this->assertSame( $test_value['expected'], $actual, $test_value['test_condition_name'] );
				continue;
			}

			// script タグの中身は 7.1 では残り 7.2 では消えるが、どちらでも script タグが出力に残らないことを確認する。
			$this->assertIsArray( $actual, $test_value['test_condition_name'] );
			$this->assertSame( array_keys( $test_value['expected'] ), array_keys( $actual ), $test_value['test_condition_name'] );
			foreach ( $test_value['expected'] as $key => $expected_value ) {
				if ( in_array( $key, $test_value['script_removed_keys'], true ) ) {
					$this->assertStringNotContainsStringIgnoringCase( '<script', $actual[ $key ], $test_value['test_condition_name'] );
					$this->assertStringNotContainsStringIgnoringCase( '</script', $actual[ $key ], $test_value['test_condition_name'] );
					$this->assertStringEndsWith( $expected_value, $actual[ $key ], $test_value['test_condition_name'] );
				} else {
					$this->assertSame( $expected_value, $actual[ $key ], $test_value['test_condition_name'] );
				}
			}
		}
	}

	function test_pad_plugin_options_validate_css_load_scope() {

		$default_input = array(
			'author_box_title'        => 'Author Profile',
			'author_box_title_tag'    => 'h4',
			'author_picture_style'    => 'square',
			'list_box_title'          => 'Latest entries',
			'list_box_title_tag'      => 'h5',
			'author_archive_link'     => 'hide',
			'author_archive_link_txt' => 'Author Archives',
			'show_thumbnail'          => 'display',
			'auto_display'            => 'yes',
			'post_types'              => array( 'post' => 'true' ),
		);

		$tests = array(
			// デフォルト値のテスト
			array(
				'input'    => array_merge( $default_input, array( 'css_load_scope' => 'post_types_only' ) ),
				'expected' => 'post_types_only',
				'label'    => 'css_load_scope should be saved as post_types_only',
			),
			// all_pages 設定のテスト
			array(
				'input'    => array_merge( $default_input, array( 'css_load_scope' => 'all_pages' ) ),
				'expected' => 'all_pages',
				'label'    => 'css_load_scope should be saved as all_pages',
			),
			// 不正な値の場合は post_types_only にフォールバック
			array(
				'input'    => array_merge( $default_input, array( 'css_load_scope' => 'invalid_scope' ) ),
				'expected' => 'post_types_only',
				'label'    => 'css_load_scope falls back to post_types_only when given invalid value',
			),
			// キー未指定の場合は post_types_only にフォールバック
			array(
				'input'    => $default_input,
				'expected' => 'post_types_only',
				'label'    => 'css_load_scope falls back to post_types_only when key is omitted',
			),
		);

		print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'test_pad_plugin_options_validate_css_load_scope' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;

		foreach ( $tests as $test ) {
			$validated = pad_plugin_options_validate( $test['input'] );
			$this->assertEquals( $test['expected'], $validated['css_load_scope'], $test['label'] );
			print $test['label'] . ' ... OK' . PHP_EOL;
		}
	}
}
