<?php
/**
 * Class SocialIconsTest
 *
 * Tests for the SNS icon definitions and rendering.
 * SNS アイコンの定義と描画のテスト。
 *
 * @package vk-post-author-display
 */

class SocialIconsTest extends WP_UnitTestCase {

	/**
	 * Verify that pad_sns_array() returns the expected icon class for each SNS key.
	 * pad_sns_array() が各 SNS キーに対して期待するアイコンクラスを返すことを確認する。
	 */
	function test_pad_sns_array() {
		// Get the SNS definition array under test.
		// テスト対象の SNS 定義配列を取得する。
		$sns_array = pad_sns_array();

		// A set of SNS keys and their expected icon classes ( null = key should not exist ).
		// SNS キーと期待するアイコンクラスのセット（ null = キーが存在しない想定 ）。
		$test_cases = array(
			array(
				'test_condition_name' => 'github キーが存在し fa-square-github を返す',
				'key'                 => 'github',
				'expected'            => 'fa-brands fa-square-github',
			),
			array(
				'test_condition_name' => 'bluesky キーが存在し fa-square-bluesky を返す',
				'key'                 => 'bluesky',
				'expected'            => 'fa-brands fa-square-bluesky',
			),
			array(
				'test_condition_name' => 'threads キーが存在し fa-square-threads を返す',
				'key'                 => 'threads',
				'expected'            => 'fa-brands fa-square-threads',
			),
			array(
				// Regression check: existing key must stay intact after the addition.
				// デグレ確認：既存キーが今回の追加で壊れていないことを確認する。
				'test_condition_name' => '既存の twitter キーは fa-square-x-twitter のまま',
				'key'                 => 'twitter',
				'expected'            => 'fa-brands fa-square-x-twitter',
			),
			array(
				// Boundary: a non-existent key must not be present in the array.
				// 境界値：存在しないキーは配列に含まれない。
				'test_condition_name' => '存在しない dummy キーは配列に含まれない',
				'key'                 => 'dummy',
				'expected'            => null,
			),
		);

		foreach ( $test_cases as $case ) {
			if ( null === $case['expected'] ) {
				// Non-existent key: assert the key is absent.
				// 存在しないキー：キーが無いことを確認する。
				$this->assertArrayNotHasKey( $case['key'], $sns_array, $case['test_condition_name'] );
			} else {
				// Existing key: assert the key exists and its icon_class matches.
				// 存在するキー：キーが存在し icon_class が期待値と一致することを確認する。
				$this->assertArrayHasKey( $case['key'], $sns_array, $case['test_condition_name'] );
				$this->assertEquals( $case['expected'], $sns_array[ $case['key'] ]['icon_class'], $case['test_condition_name'] );
			}
		}
	}

	/**
	 * Verify that get_social_icons() renders a link only for SNS with a URL.
	 * get_social_icons() が URL を設定した SNS のみリンクを描画することを確認する。
	 */
	function test_get_social_icons() {
		// Create an author user for the test.
		// テスト用の投稿者ユーザーを作成する。
		$user_id = self::factory()->user->create( array( 'role' => 'author' ) );

		// Set URLs for the newly added GitHub / Bluesky / Threads ( leave Facebook empty ).
		// 新規追加した GitHub / Bluesky / Threads に URL を設定する（ Facebook は未設定のまま ）。
		update_user_meta( $user_id, 'pad_github', 'https://github.com/example' );
		update_user_meta( $user_id, 'pad_bluesky', 'https://bsky.app/profile/example' );
		update_user_meta( $user_id, 'pad_threads', 'https://www.threads.net/@example' );

		// Create a single post authored by the user and move there so the global author context is set.
		// 投稿者の個別投稿を作成し、グローバルの投稿者コンテキストをセットするため go_to する。
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		// Generate the social icons HTML once.
		// SNS アイコンの HTML を一度だけ生成する。
		$user      = get_userdata( $user_id );
		$sns_icons = Vk_Post_Author_Box::get_social_icons( $user );

		// A set of HTML fragments and whether they should be present.
		// HTML 断片と、それが出力されるべきか否かのセット。
		$test_cases = array(
			array(
				'test_condition_name' => 'github URL 設定時に fa-square-github アイコンが出力される',
				'needle'              => 'fa-brands fa-square-github',
				'expected_present'    => true,
			),
			array(
				'test_condition_name' => 'bluesky URL 設定時に fa-square-bluesky アイコンが出力される',
				'needle'              => 'fa-brands fa-square-bluesky',
				'expected_present'    => true,
			),
			array(
				'test_condition_name' => 'threads URL 設定時に fa-square-threads アイコンが出力される',
				'needle'              => 'fa-brands fa-square-threads',
				'expected_present'    => true,
			),
			array(
				// Boundary: SNS without a URL must not render a link.
				// 境界値：URL 未設定の SNS はリンクが出力されない。
				'test_condition_name' => 'URL 未設定の facebook アイコンは出力されない',
				'needle'              => 'fa-square-facebook',
				'expected_present'    => false,
			),
		);

		foreach ( $test_cases as $case ) {
			if ( $case['expected_present'] ) {
				// Should be present in the output.
				// 出力に含まれるべき。
				$this->assertStringContainsString( $case['needle'], $sns_icons, $case['test_condition_name'] );
			} else {
				// Should not be present in the output.
				// 出力に含まれないべき。
				$this->assertStringNotContainsString( $case['needle'], $sns_icons, $case['test_condition_name'] );
			}
		}
	}
}
