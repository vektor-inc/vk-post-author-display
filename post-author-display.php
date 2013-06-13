<?php
/*
Plugin Name: VK Post Author Display
Plugin URI: http://wordpress.org/extend/plugins/vk-post-author-display/
Description: Show post author information at post bottom.
Version: 0.3.1.7
Author: Kurudrive(Hidekazu Ishikawa) at Vektor,Inc.
Author URI: http://vektor-inc.co.jp
License: GPL2

/*  Copyright 2013 Hidekazu Ishikawa ( email : kurudrive@gmail.com )

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
/*-------------------------------------------*/

/*-------------------------------------------*/
/*	Display post author unit
/*-------------------------------------------*/
add_filter( 'the_content', 'pad_add_author');
function pad_add_author($content){
	if ( is_single()){
		if ( get_post_type() == 'post'){
			/*-------------------------------------------*/
			/*	Profile
			/*-------------------------------------------*/
			$author_box_title = get_pad_options('author_box_title');
			// author caption
			if (get_the_author_meta( 'pad_caption' )){
				$caption = '<span id="pad_caption">'.get_the_author_meta( 'pad_caption' ).'</span>';
			}
			// twitter
			if (get_the_author_meta( 'pad_twitter' )){
				$twitter = '<span id="pad_twitter">Twitter:<a href="https://twitter.com/'.get_the_author_meta( 'pad_twitter' ).'" target
				="_blank">@'.get_the_author_meta( 'pad_twitter' ).'</a></span>';
			}
			$profileUnit =
				'<h4>'.$author_box_title.'</h4>'.
				'<div id="avatar">'.get_avatar( get_the_author_meta('email'), 80 ).'</div>'.
				'<dl id="profileTxtSet">'.
				'<dt>'.'<span id="authorName">'.get_the_author_meta( 'display_name' ).'</span>';
			if(isset($caption)):
				$profileUnit .= $caption;
			endif;
			if(isset($twitter)):
				$profileUnit .= $twitter;
			endif;
			$profileUnit .= '</dt>'.
							'<dd>'.nl2br(get_the_author_meta( 'description' )).'</dd>'.
							'</dl>';
			/*-------------------------------------------*/
			/*	entryUnit (Latest entries)
			/*-------------------------------------------*/
			$list_box_title = get_pad_options('list_box_title');
			$thumbnail = get_pad_options('show_thumbnai');

			// author entries
			global $post;
			$autorID = $post->post_author;
			$loop = new WP_Query( array( 'post_type' => 'post', 'posts_per_page'=> 4, 'author' => $autorID ) );
			$entryUnit = '<div id="latestEntries">'."\n";
			$entryUnit .= '<h5>'.$list_box_title.'</h5>'."\n";
			$entryUnit .= '<ul class="entryList">'."\n";
			while ( $loop->have_posts() ) : $loop->the_post();
				$categories = '';
				foreach((get_the_category()) as $cat) {
					$cat_id = $cat->cat_ID ;
					break ;
				}
				$category_link = get_category_link( $cat_id );
				$categories = '<a href="'.$category_link.'" title="'.$cat->cat_name.'" class="padCate cate-'.$cat->slug.'">'.$cat->cat_name.'</a>';
				if ($thumbnail == 'hide'){
					/* list only */
					$entryUnit .= '<li class="textList"><span class="padDate">'.get_the_date('Y.m.d').'</span>'.$categories.'<a href="'.get_permalink($post->ID).'" class="padTitle">'.get_the_title().'</a></li>'."\n";
				} else {
					/* Show thumbnail box */
					$entryUnit .= '<li class="thumbnailBox"><span class="postImage"><a href="'.get_permalink().'">';
					if ( has_post_thumbnail()) {
						$entryUnit .= get_the_post_thumbnail();
					} else {
						$entryUnit .= '<img src="'.plugins_url().'/vk-post-author-display/images/thumbnailDummy.png" alt="'.get_the_title().'" />';
					}
					$entryUnit .= '</a></span><span class="padDate">'.get_the_date('Y.m.d').'</span>'.$categories.'<a href="'.get_permalink($post->ID).'" class="padTitle">'.get_the_title().'</a></li>'."\n";
				}
				endwhile;
			$entryUnit .= '</ul>'."\n";
			$entryUnit .= '</div>'."\n";
			wp_reset_query(); // important!!
			//  get_author_posts_url()
			/*-------------------------------------------*/
			/*	Unit display
			/*-------------------------------------------*/
			$author_unit = '<div id="padSection">';
			$author_unit .= $profileUnit;
			$author_unit .= $entryUnit;
			$author_unit .= '</div>';
			$content = $content.$author_unit;
		}
	}
	return $content;
}
/*-------------------------------------------*/
/*	front display css
/*-------------------------------------------*/
add_action('wp_head', 'pad_set_css');
function pad_set_css(){
	$cssPath = apply_filters( "pad-stylesheet", plugins_url("css/vk-post-author.css", __FILE__) );
	if ( get_post_type() == 'post'){
		wp_enqueue_style( 'set_vk_post_autor_css', $cssPath , false, '2013-05-13b');
	}
}
/*-------------------------------------------*/
/*	Add user items
/*-------------------------------------------*/
function pad_update_profile_fields( $contactmethods ) {
	//項目の追加
	$contactmethods['pad_caption'] = 'Caption(Post Author Display)';
	$contactmethods['pad_twitter'] = 'Twitter(Post Author Display)';
	return $contactmethods;
}
add_filter('user_contactmethods','pad_update_profile_fields',10,1);


function pad_get_default_options() {
	$display_author_options = array(
		'author_box_title' => 'Author Profile',
		'list_box_title' => 'Latest entries',
		'author_archive_link' => 'hide',
		'show_thumbnai' => 'hide',
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
		'Post author display setting',		// Name of page
		'Post author display',		// Label in menu
		'edit_plugins',				// Capability required　このメニューページを閲覧・使用するために最低限必要なユーザーレベルまたはユーザーの種類と権限。
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
<h2>Post author display setting</h2>

<form method="post" action="options.php">
<?php
	settings_fields( 'pad_plugin_options' );
	$options_pad = pad_get_plugin_options();
	$default_options = pad_get_default_options();
?>
<div id="" class="sectionBox">
<p>[ <a href="https://gravatar.com/" target="_blank">Set your image (Gravatar)</a> ]</p>
<p>[ <a href="<?php echo get_admin_url(); ?>profile.php" target="_blank">Set your display name,twitter,caption,description</a> ]</p>
<table class="form-table">
<tr>
<th>Post author box title</th>
<td><?php echo get_pad_options('author_box_title'); ?> -> <input type="text" name="pad_plugin_options[author_box_title]" id="author_box_title" value="<?php echo esc_attr( $options_pad['author_box_title'] ); ?>" style="width:50%;" /></td>
</tr>
<tr>
<th>Post list box title</th>
<td><?php echo get_pad_options('list_box_title'); ?> -> <input type="text" name="pad_plugin_options[list_box_title]" id="list_box_title" value="<?php echo esc_attr( $options_pad['list_box_title'] ); ?>" style="width:50%;" /></td>
</tr>

<!--
<tr>
<th>Display post author archive page link</th>
<td>
<?php $author_archive_links = array(
	'hide' => 'hide',
	'display' => 'display author archive link'
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
-->
<tr>
<th>Display post thumbnail image</th>
<td>
<?php $show_thumbnails = array('hide' => 'hide','display' => 'display thumbnail image');
foreach( $show_thumbnails as $show_thumbnail_value => $show_thumbnail_lavel) {
	$checked = ''; ?>
	<label>
	<?php if ( $show_thumbnail_value == $options_pad['show_thumbnai'] ) : $checked = ' checked'; endif; ?>
	<input type="radio" name="pad_plugin_options[show_thumbnai]" value="<?php echo $show_thumbnail_value ?>"<?php echo $checked; ?>> <?php echo $show_thumbnail_lavel ?>
	</label>
<?php } ?>
</td>
</tr>

</table>
<?php submit_button(); ?>
</div><!-- [ /#sogoHeadBnr ] -->
</form>
</div>
<?php }

function pad_plugin_options_validate( $input ) {
	$output = $defaults = pad_get_default_options();

	$output['author_box_title'] = $input['author_box_title'];
	$output['list_box_title'] = $input['list_box_title'];
	$output['author_archive_link'] = $input['author_archive_link'];
	$output['show_thumbnai'] = $input['show_thumbnai'];

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