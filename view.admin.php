<?php
function pad_the_admin_body(){ ?>

<form method="post" action="options.php">
<?php
	settings_fields( 'pad_plugin_options' );
	$options_pad = pad_get_plugin_options();
	$default_options = pad_get_default_options();

?>
<div>
<section>
<h3><?php _e( 'Post Author Box Setting', 'post-author-display');?></h3>
<table class="form-table">
<tr>
<th><?php _e( 'Post author box title', 'post-author-display' ); ?></th>
<td><?php echo get_pad_options('author_box_title'); ?> -> <input type="text" name="pad_plugin_options[author_box_title]" id="author_box_title" value="<?php echo esc_attr( $options_pad['author_box_title'] ); ?>" style="width:50%;" /></td>
</tr>
<tr>
<th><?php _e( 'Profile Picture Style', 'post-author-display' ); ?></th>
<td>
<?php
if ( !isset( $options_pad['author_picture_style'] ) ) $options_pad['author_picture_style'] = 'square';

$picture_designs = array(
	'square' => __( 'Square', 'post-author-display' ),
	'circle' => __( 'Circle', 'post-author-display' )
	);
foreach( $picture_designs as $picture_design_value => $picture_design_lavel) {
	$checked = ''; ?>
	<label class="form_horizontal_item">
	<?php if ( $picture_design_value == $options_pad['author_picture_style'] ) : $checked = ' checked'; endif; ?>
	<input type="radio" name="pad_plugin_options[author_picture_style]" value="<?php echo $picture_design_value ?>"<?php echo $checked; ?>> <?php echo $picture_design_lavel ?>
	</label>
<?php } ?>
</td>
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
	<label class="form_horizontal_item">
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
	<label class="form_horizontal_item">
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
			<label class="form_horizontal_item">
				<input type="radio" name="pad_plugin_options[generate_thumbnail]" value="<?php echo $generate_thumbnail_value ?>"<?php echo $checked ?>/>
				<?php echo $generate_thumbnail_label ?>
			</label><?php
		} ?><br />
		<p>
		<?php _e('If your theme already cropping thumbnail, please select the "no".','post-author-display');?></br>
		<?php _e('If thumbnail images layout has not aligned, please select the "yes".','post-author-display');?>
		<?php _e('This plugin generate the appropriate image size.','post-author-display');?></br>
		<?php _e('* If you select the "yes" and already have many posts in your WordPress, you have to regenerate the thumbnail images using (for example) the "Regenerate Thumbnails" plugin.','post-author-display');?></p>
	</td>
</tr>

</table>
<?php submit_button(); ?>
</section>
</div>
</form>

<?php } ?>