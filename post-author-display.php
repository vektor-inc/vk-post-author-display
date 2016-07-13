<?php
/*
Plugin Name: VK Post Author Display
Plugin URI: http://wordpress.org/extend/plugins/vk-post-author-display/
Description: Show post author information at post bottom.
Version: 0.3.3.0
Author: Kurudrive(Hidekazu Ishikawa) at Vektor,Inc.
Author URI: http://ex-unit.vektor-inc.co.jp/
Text Domain : post-author-display
Domain Path : /languages/
License: GPL2

/*  Copyright 2013-2016 Hidekazu Ishikawa ( email : kurudrive@gmail.com )

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$data = get_file_data( __FILE__, array( 'version' => 'Version','textdomain' => 'Text Domain' ) );
define( 'VK_PAD_VERSION', $data['version'] );
define( 'VK_PAD_TEXTDOMAIN', $data['textdomain'] );
define( 'VK_PAD_BASENAME', plugin_basename( __FILE__ ) );
define( 'VK_PAD_URL', plugin_dir_url( __FILE__ ) );
define( 'VK_PAD_DIR', plugin_dir_path( __FILE__ ) );

require_once( VK_PAD_DIR . 'view.post-author.php' );

/*-------------------------------------------*/

/*-------------------------------------------*/
/*	Display post author unit
/*-------------------------------------------*/
add_filter( 'the_content', 'pad_add_author');
function pad_add_author($content){
	if ( is_single()){
		if ( get_post_type() == 'post'){
			$author_unit = pad_get_author_box();
			$content = $content.$author_unit;
		}
	}
	return $content;
}
/*-------------------------------------------*/
/*	front display css
/*-------------------------------------------*/
add_action('wp_enqueue_scripts', 'pad_set_css');
function pad_set_css(){
	$cssPath = apply_filters( "pad-stylesheet", plugins_url("css/vk-post-author.css", __FILE__) );
	if ( get_post_type() == 'post'){
		wp_enqueue_style( 'set_vk_post_autor_css', $cssPath , false, VK_PAD_VERSION);
		wp_enqueue_style( 'font-awesome', VK_PAD_URL . '/libraries/font-awesome/css/font-awesome.min.css', array(), '4.6.3', 'all' );
	}
}

function pad_sns_array(){
	$sns_array = array( 
		'twitter'     => array( 'name' => 'Twitter', 'icon' => 'fa-twitter-square' ),
		'facebook'    => array( 'name' => 'Facebook', 'icon' => 'fa-facebook-square' ),
		'instagram'   => array( 'name' => 'Instagram', 'icon' => 'fa-instagram' ),
		'youtube'     => array( 'name' => 'You Tube', 'icon' => 'fa-youtube-square' ),
		'linkedin'    => array( 'name' => 'LinkedIn', 'icon' => 'fa-linkedin-square' ),
		'google-plus' => array( 'name' => 'Google+', 'icon' => 'fa-google-plus-square' ),
		);
	return $sns_array;
}

/*-------------------------------------------*/
/*	Add user items
/*-------------------------------------------*/
function pad_update_profile_fields( $contactmethods ) {

	//項目の追加
	$contactmethods['pad_caption'] = __( 'Caption(Post Author Display)', 'post-author-display' );

	$sns_array = pad_sns_array();
	foreach ($sns_array as $key => $value) {
		$contactmethods['pad_'.$key] = $value['name'].' URL (Post Author Display)';
	}

	return $contactmethods;
}
add_filter('user_contactmethods','pad_update_profile_fields',10,1);


function pad_get_default_options() {
	$display_author_options = array(
		'author_box_title'        => __( 'Author Profile', 'post-author-display' ),
		'list_box_title'          => __( 'Latest entries', 'post-author-display' ),
		'author_archive_link'     => 'hide',
		'author_archive_link_txt' => __( 'Author Archives', 'post-author-display' ),
		'show_thumbnail'          => 'hide',
		'generate_thumbnail'      => 'no'
	);
	return apply_filters( 'pad_default_options', $display_author_options );
}

function pad_plugin_options_Custom_init() {
	if ( false === pad_get_plugin_options() )
	add_option( 'pad_plugin_options', pad_get_default_options() );
	register_setting(
		'pad_plugin_options',
		'pad_plugin_options',
		'pad_plugin_options_validate'
	);
}
add_action( 'admin_init', 'pad_plugin_options_Custom_init' );

/*-------------------------------------------*/
/*	functionsで毎回呼び出して$options_padに入れる処理を他でする。
/*-------------------------------------------*/
function pad_get_plugin_options() {
	return get_option( 'pad_plugin_options', pad_get_default_options() );
}

/*-------------------------------------------*/
/*	メニューに追加
/*-------------------------------------------*/
function pad_add_customSetting() {
	$custom_page = add_options_page(
		__( 'Post author display setting', 'post-author-display' ),		// Name of page
		_x( 'Post author display', 'label in admin menu', 'post-author-display' ),				// Label in menu
		'edit_theme_options',				// Capability required　このメニューページを閲覧・使用するために最低限必要なユーザーレベルまたはユーザーの種類と権限。
		'pad_plugin_options',				// ユニークなこのサブメニューページの識別子
		'pad_add_customSettingPage'			// メニューページのコンテンツを出力する関数
	);
	if ( ! $custom_page )
	return;
}
add_action( 'admin_menu', 'pad_add_customSetting' );

/*-------------------------------------------*/
/*	管理画面_admin_head JavaScriptのデバッグコンソールにhook_suffixの値を出力
/*-------------------------------------------*/

add_action("admin_head", 'suffix2console');
function suffix2console() {
		global $hook_suffix;
		if (is_user_logged_in()) {
				$str = "<script type=\"text/javascript\">console.log('%s')</script>";
				printf($str, $hook_suffix);
		}
}

/*-------------------------------------------*/
/*	Setting page
/*-------------------------------------------*/
// 第１引数で、どのページで適応するのかを指定。この場合後半（settings_page_pad_plugin_options ）がどのページかを判断する hook_suffix になっている。
// hook_suffix は、body のclass名などから確認する事が出来る。
// 第２引数は処理するfunction名
// add_action( 'admin_print_styles-settings_page_pad_plugin_options ', 'pad_custom_enqueue_scripts' );
// function pad_custom_enqueue_scripts( $hook_suffix ) {
// 	wp_enqueue_style( 'pad_plugin_options', get_template_directory_uri() . '/inc/theme-options.css', false, '2012-11-17' );
// }
/*-------------------------------------------*/
/*	Setting Page
/*-------------------------------------------*/
function pad_add_customSettingPage() { ?>
<div class="wrap" id="pad_plugin_options">
<?php screen_icon(); ?>
<h2><?php _e( 'Post author display setting', 'post-author-display' ); ?></h2>


<div style="width:68%;display:inline-block;vertical-align:top;">

<form method="post" action="options.php">
<?php
	settings_fields( 'pad_plugin_options' );
	$options_pad = pad_get_plugin_options();
	$default_options = pad_get_default_options();

?>
<div>
<p>[ <a href="https://gravatar.com/" target="_blank"><?php  _e( 'Set your image (Gravatar)', 'post-author-display' ); ?></a> ]</p>
<p>[ <a href="<?php echo get_admin_url(); ?>profile.php" target="_blank"><?php _e( 'Set your display name, twitter, caption, description', 'post-author-display' );?></a> ]</p>
<table class="form-table">
<tr>
<th><?php _e( 'Post author box title', 'post-author-display' ); ?></th>
<td><?php echo get_pad_options('author_box_title'); ?> -> <input type="text" name="pad_plugin_options[author_box_title]" id="author_box_title" value="<?php echo esc_attr( $options_pad['author_box_title'] ); ?>" style="width:50%;" /></td>
</tr>
<tr>
<th><?php _e( 'Post list box title', 'post-author-display' ); ?></th>
<td><?php echo get_pad_options('list_box_title'); ?> -> <input type="text" name="pad_plugin_options[list_box_title]" id="list_box_title" value="<?php echo esc_attr( $options_pad['list_box_title'] ); ?>" style="width:50%;" /></td>
</tr>

<tr>
<th><?php _e( 'Display post author archive page link', 'post-author-display' ) ?></th>
<td>
<?php $author_archive_links = array(
	'hide' => __( 'hide', 'post-author-display' ),
	'display' => __( 'display author archive link', 'post-author-display' )
	);
foreach( $author_archive_links as $author_archive_link_value => $author_archive_link_lavel) {
	$checked = ''; ?>
	<label>
	<?php if ( $author_archive_link_value == $options_pad['author_archive_link'] ) : $checked = ' checked'; endif; ?>
	<input type="radio" name="pad_plugin_options[author_archive_link]" value="<?php echo $author_archive_link_value ?>"<?php echo $checked; ?>> <?php echo $author_archive_link_lavel ?>
	</label>
<?php } ?>
</td>
</tr>
<tr>
<th><?php _e( 'Author archives text', 'post-author-display' ); ?></th>
<td><?php echo get_pad_options('author_archive_link_txt'); ?> -> <input type="text" name="pad_plugin_options[author_archive_link_txt]" id="author_archive_link_txt" value="<?php echo esc_attr( $options_pad['author_archive_link_txt'] ); ?>" style="width:50%;" /></td>
</tr>

<tr>
<th><?php _e( 'Display post thumbnail image', 'post-author-display' ); ?></th>
<td>
<?php $show_thumbnails = array('hide' => __( 'hide', 'post-author-display' ), 'display' => __( 'display thumbnail image', 'post-author-display' ), );
foreach( $show_thumbnails as $show_thumbnail_value => $show_thumbnail_lavel) {
	$checked = ''; ?>
	<label>
	<?php if ( $show_thumbnail_value == $options_pad['show_thumbnail'] ) : $checked = ' checked'; endif; ?>
	<input type="radio" name="pad_plugin_options[show_thumbnail]" value="<?php echo $show_thumbnail_value ?>"<?php echo $checked; ?>> <?php echo $show_thumbnail_lavel ?>
	</label>
<?php } ?>
</td>
</tr>

<tr>
	<th><?php _e( 'Use custom size thumbnails for thumbnails display?', 'post-author-display' ); ?></th>
	<td>
		<?php $generate_thumbnails = array(
										__( 'yes', 'post-author-display' ) => 'yes',
										__( 'no', 'post-author-display' ) => 'no' );
		foreach ( $generate_thumbnails as $generate_thumbnail_label => $generate_thumbnail_value ) {

			$checked = '';
			if ( ( !isset($options_pad['generate_thumbnail']) && $generate_thumbnail_value == 'no'  )
				 || ( $options_pad['generate_thumbnail'] == $generate_thumbnail_value ) )
					$checked = ' checked'; ?>
			<label>
				<input type="radio" name="pad_plugin_options[generate_thumbnail]" value="<?php echo $generate_thumbnail_value ?>"<?php echo $checked ?>/>
				<?php echo $generate_thumbnail_label ?>
			</label><?php
		} ?><br />
		<?php _e('* If you already have many posts in your WordPress, you have to regenerate the thumbnail images using (for example) the "Regenerate Thumbnails" plugin.','post-author-display');?>
	</td>
</tr>

</table>
<?php submit_button(); ?>
</div><!-- [ /#sogoHeadBnr ] -->
</form>
</div>

<div style="width:29%;display:block; overflow:hidden;float:right;">
	<a href="http://bizvektor.com/en/" target="_blank" title="<?php _e( 'Free WordPress theme for businesses', 'post-author-display' );?>">
		<img src="<?php echo plugins_url('/vk-post-author-display/images/bizVektor-ad-banner-vert.jpg') ?>" alt="<?php _e( 'Download Biz Vektor free WordPress theme for businesses', 'post-author-display' ); ?>" style="max-width:100%;" />
	</a>
</div>

</div>
<?php }

function pad_plugin_options_validate( $input ) {
	$output = $defaults = pad_get_default_options();

	$output['author_box_title']         = $input['author_box_title'];
	$output['list_box_title']           = $input['list_box_title'];
	$output['author_archive_link']      = $input['author_archive_link'];
	$output['author_archive_link_txt']  = $input['author_archive_link_txt'];
	$output['show_thumbnail']           = $input['show_thumbnail'];
	$output['generate_thumbnail']       = $input['generate_thumbnail'];

	return apply_filters( 'pad_plugin_options_validate', $output, $input, $defaults );
}

/*-------------------------------------------*/
/*	optionの値を単純に引っ張る
/*-------------------------------------------*/
function get_pad_options($optionLabel) {
	$options_pad = pad_get_plugin_options();
	if ($options_pad[$optionLabel]){
		return $options_pad[$optionLabel];
	}
}


/*-------------------------------------------*/
/*	vk post author display custom size thumbnail
/*-------------------------------------------*/
function pad_plugin_special_thumbnail() {

	$options 		 = pad_get_plugin_options();
	$default_options = pad_get_default_options();

	if( $options['generate_thumbnail'] != $default_options['generate_thumbnail'] ) {

		if ( function_exists( 'add_theme_support' ) ) {
			add_theme_support( 'post-thumbnails' );
			//custom thumbnail for pad plugin
			add_image_size( 'pad_thumb', 240, 135, array('center', 'center') );
		}
	}
	else {
		apply_filters('intermediate_image_sizes', 'pad_plugin_disable_thumbnail');
	}
}

pad_plugin_special_thumbnail();


/*-------------------------------------------*/
/*	vk post author disable custom size thumbnail
/*-------------------------------------------*/
function pad_plugin_disable_thumbnail( $sizes ) {
	unset( $sizes['pad_thumb'] );
}

/*-------------------------------------------*/
/*	vk post author text domain load
/*-------------------------------------------*/
function post_author_display_text_domain() {
	load_plugin_textdomain( 'post-author-display', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'post_author_display_text_domain' );
