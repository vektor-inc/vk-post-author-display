<?php
function pad_tag_selector( $options_pad, $name ){
	// タイトルのタグをセレクトボックスで選択できるようにする。option は h2〜h6 と div span strong
	$tag_options = array(
		'h2'     => 'h2',
		'h3'     => 'h3',
		'h4'     => 'h4',
		'h5'     => 'h5',
		'h6'     => 'h6',
		'div'    => 'div',
		'span'   => 'span',
		'strong' => 'strong',
	);
	?>
<select name="pad_plugin_options[<?php echo $name;?>]" style="width:100%;max-width:100%;">
	<?php
	foreach ( $tag_options as $tag_value => $tag_lavel ) {
		$selected = '';
		if ( isset( $options_pad[$name] ) && $tag_value === $options_pad[$name] ) {
			$selected = ' selected';
		}
		?>
	<option value="<?php echo $tag_value; ?>"<?php echo $selected; ?>><?php echo $tag_lavel; ?></option>
	<?php } ?>
</select>
<?php
}


function pad_the_admin_body() {
	?>

<form method="post" action="options.php">
	<?php
	settings_fields( 'pad_plugin_options' );
	$options_pad = pad_get_plugin_options();
	?>
<div>

<section>
<h3><?php _e( 'Post Author Box Setting', 'vk-post-author-display' ); ?></h3>
<table class="form-table">
<tr>
<th><?php _e( 'Post author box title', 'vk-post-author-display' ); ?></th>
<td><?php echo wp_kses_post( $options_pad['author_box_title'] ); ?> -> <input type="text" name="pad_plugin_options[author_box_title]" id="author_box_title" value="<?php echo esc_attr( $options_pad['author_box_title'] ); ?>" style="width:50%;" /></td>
</tr>
<tr>
<th><?php _e( 'Post author box title tag', 'vk-post-author-display' ); ?></th>
<td>
<?php
$name = 'author_box_title_tag';
pad_tag_selector( $options_pad, $name );
?>
</td>
</tr>
<tr>
<th><?php _e( 'Profile Picture Style', 'vk-post-author-display' ); ?></th>
<td>
	<?php
	if ( ! isset( $options_pad['author_picture_style'] ) ) {
		$options_pad['author_picture_style'] = 'square';
	}

	$picture_designs = array(
		'square' => __( 'Square', 'vk-post-author-display' ),
		'circle' => __( 'Circle', 'vk-post-author-display' ),
	);
	foreach ( $picture_designs as $picture_design_value => $picture_design_lavel ) {
		$checked = '';
		?>
	<label class="form_horizontal_item">
		<?php
		if ( $picture_design_value == $options_pad['author_picture_style'] ) :
			$checked = ' checked';
	endif;
		?>
	<input type="radio" name="pad_plugin_options[author_picture_style]" value="<?php echo $picture_design_value; ?>"<?php echo $checked; ?>> <?php echo $picture_design_lavel; ?>
	</label>
<?php } ?>
</td>
</tr>
<tr>
<th><?php _e( 'Post list box title', 'vk-post-author-display' ); ?></th>
<td><?php echo wp_kses_post( $options_pad['list_box_title'] ); ?> -> <input type="text" name="pad_plugin_options[list_box_title]" id="list_box_title" value="<?php echo esc_attr( $options_pad['list_box_title'] ); ?>" style="width:50%;" /></td>
</tr>
<tr>
<th><?php _e( 'Post list box title tag', 'vk-post-author-display' ); ?></th>
<td>
<?php
$name = 'list_box_title_tag';
pad_tag_selector( $options_pad, $name );
?>
</td>
</tr>
<tr>
<th><?php _e( 'Display post author archive page link', 'vk-post-author-display' ); ?></th>
<td>
	<?php
	$author_archive_links = array(
		'hide'    => __( 'hide', 'vk-post-author-display' ),
		'display' => __( 'display author archive link', 'vk-post-author-display' ),
	);
	foreach ( $author_archive_links as $author_archive_link_value => $author_archive_link_lavel ) {
		$checked = '';
		?>
	<label class="form_horizontal_item">
		<?php
		if ( $author_archive_link_value == $options_pad['author_archive_link'] ) :
			$checked = ' checked';
	endif;
		?>
	<input type="radio" name="pad_plugin_options[author_archive_link]" value="<?php echo $author_archive_link_value; ?>"<?php echo $checked; ?>> <?php echo $author_archive_link_lavel; ?>
	</label>
<?php } ?>
</td>
</tr>
<tr>
<th><?php _e( 'Author archives text', 'vk-post-author-display' ); ?></th>
<td><?php echo wp_kses_post( $options_pad['author_archive_link_txt'] ); ?> -> <input type="text" name="pad_plugin_options[author_archive_link_txt]" id="author_archive_link_txt" value="<?php echo esc_attr( $options_pad['author_archive_link_txt'] ); ?>" style="width:50%;" /></td>
</tr>

<tr>
<th><?php _e( 'Display post thumbnail image', 'vk-post-author-display' ); ?></th>
<td>
	<?php
	$show_thumbnails = array(
		'hide'    => __( 'hide', 'vk-post-author-display' ),
		'display' => __( 'display thumbnail image', 'vk-post-author-display' ),
	);
	foreach ( $show_thumbnails as $key => $lavel ) {
		$checked = '';
		?>
	<label class="form_horizontal_item">
		<?php
		if ( $key == $options_pad['show_thumbnail'] ) {
			$checked = ' checked';
		}
		?>
	<input type="radio" name="pad_plugin_options[show_thumbnail]" value="<?php echo $key; ?>"<?php echo $checked; ?>> <?php echo $lavel; ?>
	</label>
<?php } ?>
</td>
</tr>

<tr>
	<th><?php _e( 'Auto display', 'vk-post-author-display' ); ?></th>
	<td>
		<?php
		$auto_displays = array(
			__( 'yes', 'vk-post-author-display' ) => 'yes',
			__( 'no', 'vk-post-author-display' )  => 'no',
		);
		foreach ( $auto_displays as $auto_display_label => $auto_display_value ) {

			$checked = '';
			if ( $options_pad['auto_display'] == $auto_display_value ) {
					$checked = ' checked';
			}
			?>
			<label class="form_horizontal_item">
				<input type="radio" name="pad_plugin_options[auto_display]" value="<?php echo $auto_display_value; ?>"<?php echo $checked; ?>/>
				<?php echo $auto_display_label; ?>
			</label>
			<?php

		}
		?>
		<br />
		<hr >
		<p>
		<?php _e( 'If you wantt to control of display position, please select the "no" and write this code to the template file', 'vk-post-author-display' ); ?></p>

		<pre>
if ( class_exists( 'Vk_Post_Author_Box' ) ) {
	echo Vk_Post_Author_Box::pad_get_author_box();
}
		</pre>

		<h4><?php _e( 'Short code', 'vk-post-author-display' ); ?></h4>
		<p><?php _e( 'You can use short code too.', 'vk-post-author-display' ); ?></p>
		<pre>[pad]</pre>
	</td>
</tr>

</table>
	<?php submit_button(); ?>
</section>

<section id="disolay_post_types">
<h3><?php _e( 'Display post types', 'vk-post-author-display' ); ?></h3>
<table class="form-table">
<tr>
<th><?php _e( 'Display post types', 'vk-post-author-display' ); ?></th>
<td>
	<?php
	$args = array(
		'name'    => 'pad_plugin_options[post_types]',
		'checked' => $options_pad['post_types'],
		'id'      => 'pad_plugin_options[post_types]',
	);
	vk_the_post_type_check_list( $args );
	?>
</td>
</tr>
</table>
</section>
	<?php submit_button(); ?>

</div>
</form>

<?php } ?>
