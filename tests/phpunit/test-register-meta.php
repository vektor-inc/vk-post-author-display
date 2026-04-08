<?php
/**
 * Class RegisterMetaTest
 *
 * @package vk-post-author-display
 */

class RegisterMetaTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		// Ensure meta is registered for tests.
		pad_register_post_meta();
	}

	/**
	 * Test that pad_hide_post_author meta key is registered for post type.
	 */
	public function test_pad_register_post_meta() {
		$registered = registered_meta_key_exists( 'post', 'pad_hide_post_author', 'post' );
		$this->assertTrue( $registered, 'pad_hide_post_author should be registered for post type' );
	}

	/**
	 * Test that pad_hide_post_author meta has show_in_rest enabled.
	 */
	public function test_pad_meta_show_in_rest() {
		$meta_keys = get_registered_meta_keys( 'post', 'post' );
		$this->assertArrayHasKey( 'pad_hide_post_author', $meta_keys );
		$this->assertTrue( $meta_keys['pad_hide_post_author']['show_in_rest'] );
	}

	/**
	 * Test that meta value can be saved and retrieved.
	 */
	public function test_meta_save_and_retrieve() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, 'pad_hide_post_author', 'true' );
		$value = get_post_meta( $post_id, 'pad_hide_post_author', true );
		$this->assertEquals( 'true', $value );
	}

	/**
	 * Test that sanitize_callback strips dangerous content.
	 */
	public function test_pad_meta_sanitize() {
		$meta_keys = get_registered_meta_keys( 'post', 'post' );
		$sanitize  = $meta_keys['pad_hide_post_author']['sanitize_callback'];
		$this->assertEquals( 'sanitize_text_field', $sanitize );

		// Verify sanitize_text_field strips HTML.
		$result = sanitize_text_field( '<script>alert("xss")</script>' );
		$this->assertEquals( 'alert("xss")', $result );
	}
}
