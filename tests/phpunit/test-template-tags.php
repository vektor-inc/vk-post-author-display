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
