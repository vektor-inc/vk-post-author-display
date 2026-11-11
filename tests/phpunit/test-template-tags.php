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
	 *
	 * The admin screen is detected by the path part of the URL, so a front-end URL that only has "wp-admin" in its
	 * query string or as part of a slug is not treated as an admin screen. The post is looked up only when the "post"
	 * URL parameter can be read as a positive integer.
	 * 管理画面かどうかは URL のパス部分で判定し、公開側の URL のクエリ文字列やスラッグの一部に wp-admin が
	 * 含まれるだけでは管理画面扱いにならない。URL の post は、正の整数として読める値のときだけ投稿を引く。
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
				'test_condition_name' => '管理画面の新規追加画面の URL（post-new.php?post_type=page） => slug は page（正常系）',
				'request_uri'         => '/wp-admin/post-new.php?post_type=page',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post_type' => 'page' ),
				'expected_slug'       => 'page',
			),
			array(
				'test_condition_name' => '管理画面の admin-ajax.php の URL で post_type=page => slug は page（正常系）',
				'request_uri'         => '/wp-admin/admin-ajax.php?action=test&post_type=page',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array(
					'action'    => 'test',
					'post_type' => 'page',
				),
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
				'test_condition_name' => '公開側の URL のクエリ文字列に wp-admin を含み post_type=event を付ける => 管理画面扱いにせず、URL の post_type は使わず post（異常系）',
				'request_uri'         => '/?post_type=event&redirect_to=/wp-admin/',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array(
					'post_type'   => 'event',
					'redirect_to' => '/wp-admin/',
				),
				'expected_slug'       => 'post',
			),
			array(
				'test_condition_name' => '公開側の URL のクエリ文字列に wp-admin を含む（?s=wp-admin） => 管理画面扱いにせず、メインクエリの post_type（post）（異常系）',
				'request_uri'         => '/?s=wp-admin',
				'unset_request_uri'   => false,
				'post_type_query_var' => 'post',
				'clear_global_post'   => true,
				'get'                 => array( 'post_type' => 'page' ),
				'expected_slug'       => 'post',
			),
			array(
				'test_condition_name' => '公開側の URL のパスに wp-admin を含む別名のスラッグがある（/wp-admin-guide/） => 管理画面扱いにせず、メインクエリの post_type（post）（異常系）',
				'request_uri'         => '/wp-admin-guide/',
				'unset_request_uri'   => false,
				'post_type_query_var' => 'post',
				'clear_global_post'   => true,
				'get'                 => array( 'post_type' => 'page' ),
				'expected_slug'       => 'post',
			),
			array(
				'test_condition_name' => 'サブディレクトリ設置の管理画面で URL にクエリ文字列がある => 管理画面として post の投稿タイプ page（正常系）',
				'request_uri'         => '/sub/wp-admin/post.php?post=1',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post' => (string) $page_id ),
				'expected_slug'       => 'page',
			),
			array(
				'test_condition_name' => '管理画面の URL で post が数字以外を含む文字列 => 投稿 ID に化けさせず、post の指定が無いときと同じ false（異常系）',
				'request_uri'         => '/wp-admin/post.php',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post' => $page_id . 'abc' ),
				'expected_slug'       => false,
			),
			array(
				'test_condition_name' => '管理画面の URL で post が負の数の文字列 => 絶対値の投稿を拾わず、post の指定が無いときと同じ false（異常系）',
				'request_uri'         => '/wp-admin/post.php',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post' => '-' . $page_id ),
				'expected_slug'       => false,
			),
			array(
				'test_condition_name' => '管理画面の URL で post が小数の文字列 => 整数部の投稿を拾わず、post の指定が無いときと同じ false（異常系）',
				'request_uri'         => '/wp-admin/post.php',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
				'clear_global_post'   => true,
				'get'                 => array( 'post' => '1.5' ),
				'expected_slug'       => false,
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

	/**
	 * Test for vk_get_page_description().
	 * Check the returned description for password protected posts, search results, content with style tags, and so on.
	 * vk_get_page_description() のテスト。
	 * パスワード保護された投稿・検索結果・スタイルタグを含む本文などで、返る説明文を確認する。
	 */
	public function test_vk_get_page_description() {

		// go_to() で書き換えられる前に、変更前の $_SERVER['REQUEST_URI'] を退避する。
		// Back up $_SERVER['REQUEST_URI'] before go_to() rewrites it.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- テスト後に元へ戻すための退避で、値は加工しない。 / Backed up only to restore it after the test; the value is not processed.
		$original_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;

		// テスト後に復元するため、変更前のログインユーザーを退避する。
		// Back up the current user to restore it after the test.
		$original_user_id = get_current_user_id();

		// 本文の style タグを保存時に落とさせないため、管理者として投稿を作る。
		// Create posts as an administrator so that style tags in the content are kept on save.
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$test_cases = array(
			array(
				'test_condition_name' => '固定ページに抜粋がある => 抜粋を返す（正常系）',
				'conditions'          => array(
					'post_args' => array(
						'post_type'    => 'page',
						'post_title'   => 'Normal page',
						'post_excerpt' => 'Excerpt text',
						'post_content' => '<p>Content text</p>',
					),
					'page_role' => '',
					'target'    => 'post',
				),
				'expected'            => 'Excerpt text',
			),
			array(
				'test_condition_name' => '固定ページに抜粋が無い => HTML タグを除いた本文を返す（正常系）',
				'conditions'          => array(
					'post_args' => array(
						'post_type'    => 'page',
						'post_title'   => 'Normal page',
						'post_excerpt' => '',
						'post_content' => '<p>Content text</p>',
					),
					'page_role' => '',
					'target'    => 'post',
				),
				'expected'            => 'Content text',
			),
			array(
				'test_condition_name' => '本文に style タグがある => style タグの中身ごと除いた本文を返す（異常系）',
				'conditions'          => array(
					'post_args' => array(
						'post_type'    => 'page',
						'post_title'   => 'Styled page',
						'post_excerpt' => '',
						'post_content' => '<style>.a{color:red}</style><p>Body text</p>',
					),
					'page_role' => '',
					'target'    => 'post',
				),
				'expected'            => 'Body text',
			),
			array(
				'test_condition_name' => 'パスワード保護された固定ページに抜粋がある => 抜粋ではなく保護中の文言を返す（異常系）',
				'conditions'          => array(
					'post_args' => array(
						'post_type'     => 'page',
						'post_title'    => 'Protected page',
						'post_excerpt'  => 'Protected excerpt',
						'post_content'  => '<p>Protected content</p>',
						'post_password' => 'password',
					),
					'page_role' => '',
					'target'    => 'post',
				),
				'expected'            => 'This article is protected by a password.',
			),
			array(
				'test_condition_name' => 'パスワード保護された投稿に抜粋が無い => 本文ではなく保護中の文言を返す（異常系）',
				'conditions'          => array(
					'post_args' => array(
						'post_type'     => 'post',
						'post_title'    => 'Protected post',
						'post_excerpt'  => '',
						'post_content'  => '<p>Protected content</p>',
						'post_password' => 'password',
					),
					'page_role' => '',
					'target'    => 'post',
				),
				'expected'            => 'This article is protected by a password.',
			),
			array(
				'test_condition_name' => 'パスワード保護された固定ページをフロントページに指定 => 抜粋ではなくキャッチフレーズを返す（異常系）',
				'conditions'          => array(
					'post_args' => array(
						'post_type'     => 'page',
						'post_title'    => 'Front',
						'post_excerpt'  => 'Protected excerpt',
						'post_content'  => '<p>Protected content</p>',
						'post_password' => 'password',
					),
					'page_role' => 'front',
					'target'    => 'post',
				),
				'expected'            => 'Test tagline',
			),
			array(
				'test_condition_name' => 'パスワード保護された固定ページを投稿ページに指定 => 抜粋ではなくアーカイブ用の説明文を返す（異常系）',
				'conditions'          => array(
					'post_args' => array(
						'post_type'     => 'page',
						'post_title'    => 'Blog',
						'post_excerpt'  => 'Protected excerpt',
						'post_content'  => '',
						'post_password' => 'password',
					),
					'page_role' => 'posts',
					'target'    => 'post',
				),
				'expected'            => 'Article of Protected: Blog. Test Site Test tagline',
			),
			array(
				'test_condition_name' => '検索結果ページ => 空文字を返す（境界値）',
				'conditions'          => array(
					'post_args' => array(
						'post_type'    => 'page',
						'post_title'   => 'Normal page',
						'post_excerpt' => 'Excerpt text',
						'post_content' => '<p>Content text</p>',
					),
					'page_role' => '',
					'target'    => 'search',
				),
				'expected'            => '',
			),
		);

		try {
			wp_set_current_user( $admin_id );

			foreach ( $test_cases as $case ) {
				$conditions = $case['conditions'];

				// サイト名とキャッチフレーズを固定する。
				// Fix the site title and tagline.
				update_option( 'blogname', 'Test Site' );
				update_option( 'blogdescription', 'Test tagline' );

				// 条件の投稿を作り、必要ならフロントページ・投稿ページに指定する。
				// Create the post for the case and set it as the front page / posts page when needed.
				$post_id = self::factory()->post->create( array_merge( array( 'post_status' => 'publish' ), $conditions['post_args'] ) );
				if ( 'front' === $conditions['page_role'] ) {
					update_option( 'show_on_front', 'page' );
					update_option( 'page_on_front', $post_id );
				} elseif ( 'posts' === $conditions['page_role'] ) {
					update_option( 'show_on_front', 'page' );
					update_option( 'page_for_posts', $post_id );
				}

				// 対象の URL に移動する。
				// Go to the target URL.
				if ( 'search' === $conditions['target'] ) {
					$this->go_to( home_url( '/?s=Normal' ) );
				} else {
					$this->go_to( get_permalink( $post_id ) );
				}

				$actual = vk_get_page_description();

				$this->assertSame( $case['expected'], $actual, $case['test_condition_name'] );

				// オプションと投稿を削除する。
				// Delete the options and the post.
				delete_option( 'show_on_front' );
				delete_option( 'page_on_front' );
				delete_option( 'page_for_posts' );
				wp_delete_post( $post_id, true );
			}
		} finally {
			// アサーション失敗時も含め、ログインユーザー・オプション・$_SERVER['REQUEST_URI'] を元に戻す。
			// Restore the current user, the options and $_SERVER['REQUEST_URI'] even when an assertion fails.
			delete_option( 'show_on_front' );
			delete_option( 'page_on_front' );
			delete_option( 'page_for_posts' );
			wp_set_current_user( $original_user_id );

			if ( null !== $original_request_uri ) {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			} else {
				unset( $_SERVER['REQUEST_URI'] );
			}
		}
	}

	/**
	 * Test for vk_sanitize_boolean(). Also check that the old name veu_sanitize_boolean() returns the same result.
	 * vk_sanitize_boolean() のテスト。旧名の veu_sanitize_boolean() も同じ結果になることを確認する。
	 */
	public function test_vk_sanitize_boolean() {

		$test_cases = array(
			array(
				'test_condition_name' => 'true を渡す => true（正常系）',
				'input'               => true,
				'expected'            => true,
			),
			array(
				'test_condition_name' => '文字列 true を渡す => true（正常系）',
				'input'               => 'true',
				'expected'            => true,
			),
			array(
				'test_condition_name' => 'false を渡す => false（正常系）',
				'input'               => false,
				'expected'            => false,
			),
			array(
				'test_condition_name' => '空文字を渡す => false（境界値）',
				'input'               => '',
				'expected'            => false,
			),
		);

		$this->assertTrue( function_exists( 'vk_sanitize_boolean' ), 'vk_sanitize_boolean() が定義されていること' );

		foreach ( $test_cases as $case ) {
			$this->assertSame( $case['expected'], vk_sanitize_boolean( $case['input'] ), $case['test_condition_name'] );
			$this->assertSame( $case['expected'], veu_sanitize_boolean( $case['input'] ), $case['test_condition_name'] . '（veu_sanitize_boolean）' );
		}
	}

	/**
	 * Test for vk_the_post_type_check_list(). Check the output HTML.
	 * vk_the_post_type_check_list() のテスト。出力される HTML を確認する。
	 */
	public function test_vk_the_post_type_check_list() {

		global $wp_post_types;

		// テスト後に復元するため、変更前の投稿タイプ一覧を退避する。
		// Back up the registered post types to restore them after the test.
		$original_wp_post_types = $wp_post_types;

		// 属性の区切りに使われる文字を名前に含む投稿タイプを、一覧へ直接足す（register_post_type() は名前を整えてしまうため）。
		// Add a post type whose name contains an attribute delimiter directly to the list (register_post_type() would sanitize the name).
		$quoted_post_type        = clone get_post_type_object( 'post' );
		$quoted_post_type->name  = 'pad"type';
		$quoted_post_type->label = 'Pad <b>Type</b>';

		$test_cases = array(
			array(
				'test_condition_name' => '投稿だけを対象にしてチェック済み・id 指定あり => チェック済みで id とスラッグ付きの一覧（正常系）',
				'args'                => array(
					'post_types_args' => array( 'name' => 'post' ),
					'name'            => 'pad_plugin_options[post_types]',
					'checked'         => array( 'post' => 'true' ),
					'id'              => array( 'post' => 'pad_post' ),
				),
				'expected'            => '<ul class="no-style"><li><label><input type="checkbox" name="pad_plugin_options[post_types][post]" id="pad_post" value="true" checked /> Posts <span class="description">(<code>post</code>)</span></label></li></ul>',
			),
			array(
				'test_condition_name' => '固定ページだけを対象にして未チェック・id が文字列（設定画面と同じ渡し方） => 未チェックで id 無しの一覧（正常系）',
				'args'                => array(
					'post_types_args' => array( 'name' => 'page' ),
					'name'            => 'pad_plugin_options[post_types]',
					'checked'         => array( 'post' => 'true' ),
					'id'              => 'pad_plugin_options[post_types]',
				),
				'expected'            => '<ul class="no-style"><li><label><input type="checkbox" name="pad_plugin_options[post_types][page]" value="true" /> Pages <span class="description">(<code>page</code>)</span></label></li></ul>',
			),
			array(
				'test_condition_name' => '投稿タイプ名と表示名に記号を含む => name 属性・表示名・スラッグをエスケープした一覧（異常系）',
				'args'                => array(
					'post_types_args' => array( 'name' => 'pad"type' ),
					'name'            => 'pad_plugin_options[post_types]',
					'checked'         => array(),
					'id'              => '',
				),
				'expected'            => '<ul class="no-style"><li><label><input type="checkbox" name="pad_plugin_options[post_types][pad&quot;type]" value="true" /> Pad &lt;b&gt;Type&lt;/b&gt; <span class="description">(<code>pad&quot;type</code>)</span></label></li></ul>',
			),
			array(
				'test_condition_name' => '対象の投稿タイプが除外指定されている => 空の一覧（境界値）',
				'args'                => array(
					'post_types_args'    => array( 'name' => 'post' ),
					'name'               => 'pad_plugin_options[post_types]',
					'exclude_post_types' => array( 'post' ),
				),
				'expected'            => '<ul class="no-style"></ul>',
			),
		);

		try {
			$wp_post_types['pad"type'] = $quoted_post_type;

			foreach ( $test_cases as $case ) {
				// 出力を文字列として受け取る。
				// Capture the output as a string.
				ob_start();
				try {
					vk_the_post_type_check_list( $case['args'] );
				} finally {
					$actual = ob_get_clean();
				}

				$this->assertSame( $case['expected'], $actual, $case['test_condition_name'] );
			}
		} finally {
			// アサーション失敗時も含め、投稿タイプ一覧を元に戻す。
			// Restore the registered post types even when an assertion fails.
			$wp_post_types = $original_wp_post_types;
		}
	}

	/**
	 * Test for vk_the_taxonomy_check_list(). Check the output HTML.
	 * vk_the_taxonomy_check_list() のテスト。出力される HTML を確認する。
	 */
	public function test_vk_the_taxonomy_check_list() {

		$this->assertTrue( function_exists( 'vk_the_taxonomy_check_list' ), 'vk_the_taxonomy_check_list() が定義されていること' );

		$category = get_taxonomy( 'category' );

		$test_cases = array(
			array(
				'test_condition_name' => 'カテゴリーを渡してチェック済み・id 指定あり => チェック済みで id とスラッグ付きの一覧（正常系）',
				'args'                => array(
					'taxonomies' => array( 'category' => $category ),
					'name'       => 'options[taxonomies]',
					'checked'    => array( 'category' => 'true' ),
					'id'         => array( 'category' => 'tax_category' ),
				),
				'expected'            => '<ul class="no-style"><li><label><input type="checkbox" name="options[taxonomies][category]" id="tax_category" value="true" checked /> Categories <span class="description">(<code>category</code>)</span></label></li></ul>',
			),
			array(
				'test_condition_name' => 'カテゴリーを渡して未チェック => 未チェックの一覧（正常系）',
				'args'                => array(
					'taxonomies' => array( 'category' => $category ),
					'name'       => 'options[taxonomies]',
				),
				'expected'            => '<ul class="no-style"><li><label><input type="checkbox" name="options[taxonomies][category]" value="true" /> Categories <span class="description">(<code>category</code>)</span></label></li></ul>',
			),
			array(
				'test_condition_name' => 'カテゴリーが除外指定されている => 空の一覧（境界値）',
				'args'                => array(
					'taxonomies'         => array( 'category' => $category ),
					'name'               => 'options[taxonomies]',
					'exclude_taxonomies' => array( 'category' ),
				),
				'expected'            => '<ul class="no-style"></ul>',
			),
			array(
				'test_condition_name' => 'タクソノミーを 1 つも渡さない => 一覧の代わりに指定した文言（境界値）',
				'args'                => array(
					'taxonomies'    => array(),
					'name'          => 'options[taxonomies]',
					'empty_message' => 'Nothing <b>here</b>.',
				),
				'expected'            => '<p class="description">Nothing &lt;b&gt;here&lt;/b&gt;.</p>',
			),
		);

		foreach ( $test_cases as $case ) {
			// 出力を文字列として受け取る。
			// Capture the output as a string.
			ob_start();
			try {
				vk_the_taxonomy_check_list( $case['args'] );
			} finally {
				$actual = ob_get_clean();
			}

			$this->assertSame( $case['expected'], $actual, $case['test_condition_name'] );
		}
	}

	/**
	 * Check that nothing is defined when the bundled template-tags.php is loaded directly outside WordPress.
	 * 同梱している template-tags.php を WordPress の外から直接読み込んだときに、何も定義されないことを確認する。
	 */
	public function test_template_tags_file_direct_access() {

		$file = dirname( dirname( __DIR__ ) ) . '/inc/template-tags/package/template-tags.php';

		$test_cases = array(
			array(
				'test_condition_name' => 'ABSPATH を定義せずに読み込む => 関数は定義されない（異常系）',
				'define_abspath'      => false,
				'expected'            => 'not-defined',
			),
			array(
				'test_condition_name' => 'ABSPATH を定義してから読み込む => 関数が定義される（正常系）',
				'define_abspath'      => true,
				'expected'            => 'defined',
			),
		);

		foreach ( $test_cases as $case ) {
			// WordPress を読み込んでいない別の PHP プロセスでファイルを読み込み、関数が定義されたかを出力させる。
			// Load the file in a separate PHP process without WordPress and print whether a function got defined.
			$code  = $case['define_abspath'] ? 'define( "ABSPATH", "/" );' : '';
			$code .= 'register_shutdown_function( function () { echo function_exists( "vk_is_template_tags_exist" ) ? "defined" : "not-defined"; } );';
			$code .= 'include $argv[1];';

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec -- テスト内で、固定のコードを別プロセスで実行するためだけに使う。 / Used only to run fixed code in a separate process within the test.
			$actual = shell_exec( escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( $code ) . ' ' . escapeshellarg( $file ) );

			$this->assertSame( $case['expected'], $actual, $case['test_condition_name'] );
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
