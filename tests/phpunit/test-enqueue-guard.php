<?php
/**
 * Class EnqueueGuardTest
 *
 * @package vk-post-author-display
 */

/**
 * pad_enqueue_block_editor_assets() のスクリーンガードテスト。
 * Screen guard tests for pad_enqueue_block_editor_assets().
 */
class EnqueueGuardTest extends WP_UnitTestCase {

	/**
	 * Current_screen が null の時は何もエンキューしない。
	 * Does nothing when current_screen is null.
	 *
	 * @return void
	 */
	public function test_pad_enqueue_guard_no_screen() {
		$GLOBALS['current_screen'] = null;
		pad_enqueue_block_editor_assets();
		$this->assertFalse( wp_script_is( 'pad-editor-panel', 'enqueued' ) );
	}

	/**
	 * Is_block_editor が false の時は何もエンキューしない。
	 * Does nothing when is_block_editor is false.
	 *
	 * @return void
	 */
	public function test_pad_enqueue_guard_not_block_editor() {
		$screen                    = WP_Screen::get( 'widgets' );
		$screen->is_block_editor   = false;
		$GLOBALS['current_screen'] = $screen;
		pad_enqueue_block_editor_assets();
		$this->assertFalse( wp_script_is( 'pad-editor-panel', 'enqueued' ) );
		$GLOBALS['current_screen'] = null;
	}

	/**
	 * Post_type が空の時は何もエンキューしない。
	 * Does nothing when post_type is empty.
	 *
	 * @return void
	 */
	public function test_pad_enqueue_guard_empty_post_type() {
		$screen                    = WP_Screen::get( 'widgets' );
		$screen->is_block_editor   = true;
		$GLOBALS['current_screen'] = $screen;
		pad_enqueue_block_editor_assets();
		$this->assertFalse( wp_script_is( 'pad-editor-panel', 'enqueued' ) );
		$GLOBALS['current_screen'] = null;
	}
}
