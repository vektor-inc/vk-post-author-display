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
	 */
	function test_vk_get_post_type() {
		// フロントページに移動して $wp_query を用意する。
		$this->go_to( home_url( '/' ) );

		global $wp_query;

		// テスト後に復元するため、変更前の値を退避する。
		$original_request_uri         = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;
		$original_post_type_query_var = isset( $wp_query->query_vars['post_type'] ) ? $wp_query->query_vars['post_type'] : null;

		$test_cases = array(
			array(
				'test_condition_name' => 'REQUEST_URI が通常どおり設定されている場合 => slug を含む配列を返す（正常系）',
				'request_uri'         => '/',
				'unset_request_uri'   => false,
				'post_type_query_var' => null,
			),
			array(
				'test_condition_name' => 'REQUEST_URI が未設定（WP-CLI / cron 相当）=> Undefined array key / strpos(null) を出さず slug を含む配列を返す（境界値）',
				'request_uri'         => null,
				'unset_request_uri'   => true,
				'post_type_query_var' => null,
			),
			array(
				'test_condition_name' => 'メインクエリの post_type が配列で指定された場合（pre_get_posts で array( "event", "page" ) を set 相当）=> Array to string conversion 警告を出さず先頭要素を文字列化した slug を返す（異常系）',
				'request_uri'         => '/',
				'unset_request_uri'   => false,
				'post_type_query_var' => array( 'event', 'page' ),
			),
		);

		try {
			foreach ( $test_cases as $case ) {
				if ( $case['unset_request_uri'] ) {
					unset( $_SERVER['REQUEST_URI'] );
				} else {
					$_SERVER['REQUEST_URI'] = $case['request_uri'];
				}

				if ( null !== $case['post_type_query_var'] ) {
					$wp_query->query_vars['post_type'] = $case['post_type_query_var'];
				} else {
					unset( $wp_query->query_vars['post_type'] );
				}

				$actual = vk_get_post_type();

				$this->assertIsArray( $actual, $case['test_condition_name'] );
				$this->assertArrayHasKey( 'slug', $actual, $case['test_condition_name'] );
				$this->assertIsString( $actual['slug'], $case['test_condition_name'] );

				if ( is_array( $case['post_type_query_var'] ) ) {
					$this->assertSame( reset( $case['post_type_query_var'] ), $actual['slug'], $case['test_condition_name'] );
				}
			}
		} finally {
			// アサーション失敗（例外）時も含め、$_SERVER と $wp_query を必ず復元して後続テストへの影響を防ぐ。
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
				'input'                => array(
					'post' => 'true',
					'info' => '<script>alert(1)</script>true',
				),
				'expected'             => array(
					'post' => 'true',
					// wp_kses_post() は script タグ自体を除去するが、タグの中身のテキストは残す仕様。
					'info' => 'alert(1)true',
				),
			),
			array(
				'test_condition_name' => '空配列を渡した場合 => 空配列を返す（正常系）',
				'input'                => array(),
				'expected'             => array(),
			),
			array(
				'test_condition_name' => '配列以外（文字列）を渡した場合 => Undefined variable $return 警告を出さず空配列を返す（異常系）',
				'input'                => 'not-an-array',
				'expected'             => array(),
			),
			array(
				'test_condition_name' => '配列以外（null）を渡した場合 => Undefined variable $return 警告を出さず空配列を返す（境界値）',
				'input'                => null,
				'expected'             => array(),
			),
		);

		foreach ( $tests as $test_value ) {
			$actual = vk_sanitize_array( $test_value['input'] );
			$this->assertSame( $test_value['expected'], $actual, $test_value['test_condition_name'] );
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
