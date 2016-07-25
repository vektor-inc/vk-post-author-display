<?php

/*-------------------------------------------*/
/*	vk post author profile image
/*-------------------------------------------*/
function add_user_profile_image_form( $bool ) {
    global $profileuser;
    if ( preg_match( '/^(profile\.php|user-edit\.php)/', basename( $_SERVER['REQUEST_URI'] ) ) ) {
?>
    <tr>
	<th scope="row"><?php _e( 'Profile image<br>(VK Post Author Display)', 'post-author-display' );?></th>
	<td>

	<input id="user_profile_image" type="hidden" name="user_profile_image" value="<?php echo esc_html( $profileuser->user_profile_image ); ?>" />
	<img id="defaultImage_user_profile_image" style="display:none;" src="<?php echo esc_url( VK_PAD_URL );?>images/no_image.png" alt="" />
	<?php
		$attr = array(
		'id'    => 'thumb_user_profile_image',
		'src'   => '',
		'class' => 'input_thumb',
		);
		if ( isset( $profileuser->user_profile_image ) && $profileuser->user_profile_image ){
			echo wp_get_attachment_image( $profileuser->user_profile_image, 'medium', false, $attr );
		} else {
			echo '<img src="'.VK_PAD_URL.'/images/no_image.png" id="'.$attr['id'].'" alt="" class="'.$attr['class'].'" style="width:96px;height:auto;">';

		} ?>

		<button id="media_user_profile_image" class="media_btn btn btn-default button button-default">
		<?php _e('Choose image', 'post-author-display');?>
		</button>
		<div id="media_reset_user_profile_image" class="media_reset_btn btn btn-default button button-default">
		<?php _e('Reset image', 'post-author-display');?>
		</div>

	</td>
    </tr>
<?php
    }
    return $bool;
}
add_action( 'show_password_fields', 'add_user_profile_image_form' );
 
function pad_update_user_profile_image( $user_id, $old_user_data ) {
    if ( isset( $_POST['user_profile_image'] ) && $old_user_data->user_profile_image != $_POST['user_profile_image'] ) {
        $user_profile_image = sanitize_text_field( $_POST['user_profile_image'] );
        $user_profile_image = wp_filter_kses( $user_profile_image );
        $user_profile_image = _wp_specialchars( $user_profile_image );
        update_user_meta( $user_id, 'user_profile_image', $user_profile_image );
    }
}
add_action( 'profile_update', 'pad_update_user_profile_image', 10, 2 );

/*-------------------------------------------*/
/*	$admin_pages の配列にいれる識別値は下記をコメントアウト解除すればブラウザのコンソールで確認出来る
/*-------------------------------------------*/

add_action("admin_head", 'suffix2console');
function suffix2console() {
		global $hook_suffix;
		if (is_user_logged_in()) {
				$str = "<script type=\"text/javascript\">console.log('%s')</script>";
				printf($str, $hook_suffix);
		}
}

function pad_admin_enqueue_scripts() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_media();
	wp_enqueue_script( 'pad-profile-media-js', VK_PAD_URL.'/js/media.js', array( 'jquery' ), VK_PAD_VERSION );
	wp_enqueue_style( 'pad-profile-style', VK_PAD_URL.'/css/pad-admin-profile.css', array(), VK_PAD_VERSION );
}
add_action( 'admin_print_styles-profile.php', 'pad_admin_enqueue_scripts' );
add_action( 'admin_print_styles-user-edit.php', 'pad_admin_enqueue_scripts' );
