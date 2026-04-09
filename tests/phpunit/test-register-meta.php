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
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'pad_hide_post_author', 'true' );
		$value = get_post_meta( $post_id, 'pad_hide_post_author', true );
		$this->assertEquals( 'true', $value );
	}

	/**
	 * Test that sanitize_callback strips dangerous content.
	 * sanitize_callback が危険なコンテンツを除去することを確認する。
	 */
	public function test_pad_meta_sanitize() {
		$meta_keys = get_registered_meta_keys( 'post', 'post' );
		$sanitize  = $meta_keys['pad_hide_post_author']['sanitize_callback'];
		$this->assertEquals( 'sanitize_text_field', $sanitize );

		// Verify sanitize_text_field strips HTML tags.
		// sanitize_text_field が HTML タグを除去することを確認する。
		$result = sanitize_text_field( '<b>bold</b>' );
		$this->assertEquals( 'bold', $result );

		// Verify script tags and their content are removed.
		// script タグとその内容が除去されることを確認する。
		$result = sanitize_text_field( '<script>alert("xss")</script>' );
		$this->assertStringNotContainsString( '<script>', $result );
	}

	/**
	 * Test that sanitize_callback is applied through the registered meta pipeline.
	 * register_post_meta のパイプライン経由でサニタイズが実行されることを確認する。
	 */
	public function test_pad_meta_sanitize_through_pipeline() {
		$post_id = self::factory()->post->create();

		// update_post_meta 経由で危険な値を保存し、
		// register_post_meta の sanitize_callback が適用されることを確認する。
		update_post_meta( $post_id, 'pad_hide_post_author', '<script>alert("xss")</script>' );
		$value = get_post_meta( $post_id, 'pad_hide_post_author', true );
		$this->assertStringNotContainsString( '<script>', $value );

		// 正常値 'true' がそのまま保存されることを確認する。
		update_post_meta( $post_id, 'pad_hide_post_author', 'true' );
		$value = get_post_meta( $post_id, 'pad_hide_post_author', true );
		$this->assertEquals( 'true', $value );

		// 空文字がそのまま保存されることを確認する。
		update_post_meta( $post_id, 'pad_hide_post_author', '' );
		$value = get_post_meta( $post_id, 'pad_hide_post_author', true );
		$this->assertEmpty( $value );
	}
}
