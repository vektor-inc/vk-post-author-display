<?php
/**********************************************/
// Load modules
/**********************************************/

/*
色選択機能をつける対象のタームの指定

最初Global変数指定をしていたが、 Global変数では
複数の term color が存在した場合に実行タイミングの都合上任意に指定が効かないため、
フックでの指定を行う
 */
global $vk_term_color_taxonomies;

// ★★★★★★ 関数のprefixは固有のものに変更する事 ★★★★★★
// add_filter( 'term_color_taxonomies_custom', 'pad_term_color_taxonomies_custom', 10.2 );
// function pad_term_color_taxonomies_custom( $taxonomies ) {
// $taxonomies[] = 'category';
// $taxonomies[] = 'post_tags';
// return $taxonomies;
// }
if ( ! class_exists( 'Vk_term_color' ) ) {

	global $vk_term_color_textdomain;
	$vk_term_color_textdomain = 'vk-post-author-display';

	/*
	読み込みタイミングをafter_setup_themeにしておかないと
	テーマから対象taxonomyの指定がある場合に効かない
	★★★★★★ 関数のprefixは固有のものに変更する事 ★★★★★★
	*/
	add_action( 'after_setup_theme', 'pad_load_term_color' );
	function pad_load_term_color() {
		require_once( 'term-color/class.term-color.php' );
	}
}
