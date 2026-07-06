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
	 * Saved current_screen value restored after each test.
	 *
	 * @var WP_Screen|null
	 */
	private $_original_screen;

	/**
	 * Save the original current_screen before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->_original_screen = isset( $GLOBALS['current_screen'] )
			? $GLOBALS['current_screen']
			: null;
	}

	/**
	 * Restore current_screen and dequeue test scripts after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$GLOBALS['current_screen'] = $this->_original_screen;
		wp_dequeue_script( 'pad-editor-panel' );
		wp_deregister_script( 'pad-editor-panel' );
		parent::tearDown();
	}

	/**
	 * Is_block_editor が true かつ post_type がある時に script がエンキューされる。
	 * Enqueues script when is_block_editor is true and post_type is set.
	 *
	 * @return void
	 */
	public function test_pad_enqueue_happy_path() {
		$screen                    = WP_Screen::get( 'post' );
		$screen->is_block_editor   = true;
		$screen->post_type         = 'post';
		$GLOBALS['current_screen'] = $screen;
		pad_enqueue_block_editor_assets();
		$this->assertTrue( wp_script_is( 'pad-editor-panel', 'enqueued' ) );
	}

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
	}
}
