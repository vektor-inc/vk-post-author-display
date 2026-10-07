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
}
