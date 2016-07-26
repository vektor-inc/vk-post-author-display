<?php
function add_user_profile_image_form( $bool ) {
    global $profileuser;
    if ( preg_match( '/^(profile\.php|user-edit\.php)/', basename( $_SERVER['REQUEST_URI'] ) ) ) {
?>
    <tr>
	<th scope="row"><?php _e( 'Profile Picture<br>(VK Post Author Display)', 'post-author-display' );?></th>
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
		<p><?php _e('If you set the "VK Post Author Display Profile Picture", this image overrides the normal profile picture.', 'post-author-display');?>
		</p>
	</td>
    </tr>
<?php
    }
    return $bool;
}