<?php
/**
 * Class AdminPostTypeListStyleTest
 *
 * @package vk-post-author-display
 */

use VektorInc\VK_Admin\VkAdmin;

/**
 * 設定画面「Display post types」一覧の折り返し用 CSS のテスト。
 * Tests for the line wrapping CSS of the "Display post types" list on the setting page.
 */
class AdminPostTypeListStyleTest extends WP_UnitTestCase {

	/**
	 * テストごとに vk-admin のスタイル登録を外し、次のテストへ追記分を持ち越さない。
	 * Deregister the vk-admin style after each test so that the inline CSS does not leak into the next test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		wp_dequeue_style( 'vk-admin-style' );
		wp_deregister_style( 'vk-admin-style' );
		parent::tearDown();
	}

	/**
	 * 指定した管理画面のスタイル出力フックを発火させ、vk-admin のスタイルに追記された CSS を返す。
	 * Fire the print styles hook of the given admin screen and return the CSS added to the vk-admin style.
	 *
	 * @param string $hook_suffix 管理画面のフック名 / Hook suffix of the admin screen.
	 * @return string 追記された CSS（無ければ空文字） / Added CSS (empty string if nothing was added).
	 */
	private function get_inline_css_on_screen( $hook_suffix ) {
		// 実際の管理画面と同じ順番で、先に vk-admin のスタイルを読み込む.
		// Load the vk-admin style first, in the same order as the real admin screen.
		VkAdmin::admin_common_css();

		// 対象画面のスタイル出力フックを発火させる.
		// Fire the print styles hook of the target screen.
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- WordPress 本体のフック名.
		do_action( 'admin_print_styles-' . $hook_suffix );

		// 追記された CSS を 1 つの文字列にまとめる.
		// Join the added CSS into one string.
		$inline_css = wp_styles()->get_data( 'vk-admin-style', 'after' );
		return is_array( $inline_css ) ? implode( "\n", $inline_css ) : '';
	}

	/**
	 * 設定画面では折り返し用 CSS が追記され、ほかの管理画面では追記されない。
	 * The line wrapping CSS is added on the setting page only.
	 *
	 * @return void
	 */
	public function test_pad_add_post_type_list_wrap_style() {
		$test_cases = array(
			'設定画面では追記される'      => array(
				'hook_suffix' => 'settings_page_pad_plugin_options',
				'expected'    => true,
			),
			'ダッシュボードでは追記されない'  => array(
				'hook_suffix' => 'index.php',
				'expected'    => false,
			),
			'プロフィール画面では追記されない' => array(
				'hook_suffix' => 'profile.php',
				'expected'    => false,
			),
		);

		foreach ( $test_cases as $label => $test_case ) {
			$inline_css = $this->get_inline_css_on_screen( $test_case['hook_suffix'] );

			// 行頭禁則とスラッグの改行禁止の 2 つの指定がそろっているかを確かめる.
			// Check that both the line breaking rule and the nowrap rule for the slug exist.
			$has_line_break = false !== strpos( $inline_css, '#disolay_post_types .form-table td li label{line-break:strict;}' );
			$has_nowrap     = false !== strpos( $inline_css, '#disolay_post_types .form-table td li .description{white-space:nowrap;}' );

			$this->assertSame( $test_case['expected'], $has_line_break, $label . '（line-break）' );
			$this->assertSame( $test_case['expected'], $has_nowrap, $label . '（white-space）' );

			// 次のケースへ追記分を持ち越さないよう、登録を外す.
			// Deregister the style so that the added CSS does not leak into the next case.
			wp_dequeue_style( 'vk-admin-style' );
			wp_deregister_style( 'vk-admin-style' );
		}
	}
}
