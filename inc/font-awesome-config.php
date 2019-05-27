<?php

/*-------------------------------------------*/
/*  Load modules
/*-------------------------------------------*/
if ( ! class_exists( 'Vk_Font_Awesome_Versions' ) ) {

	// テーマがLightningかどうか
	function pad_is_theme_lightning() {
		$template = get_option( 'template' );
		if ( $template == 'lightning' || $template == 'Lightning' || $template == 'lightning-pro' ) {
			return true;
		}
	}

	// ExUnitでFontAwesomeがアクティブになってるかどうか？
	function pad_is_veu_fa_active() {
		 include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		 $plugin = 'vk-all-in-one-expansion-unit/vkExUnit.php';
		 // プラグインがアクティブか？
		if ( is_plugin_active( $plugin ) ) {
			$options = get_option( 'vkExUnit_common_options' );
			if ( empty( $options['active_fontawesome'] ) ) {
				return true;
			} // if ( empty( $options['active_fontawesome'] ) ) {
		} // if ( is_plugin_active( $plugin ) ) {
	} // function pad_is_veu_fa_active() {

	if ( ! pad_is_theme_lightning() && ! pad_is_veu_fa_active() ) {

		require_once( 'font-awesome/class-vk-font-awesome-versions.php' );

		global $font_awesome_directory_uri;
		$font_awesome_directory_uri = plugins_url( '', __FILE__ ) . '/font-awesome/';

		global $set_enqueue_handle_style;
		$set_enqueue_handle_style = 'vkExUnit_common_style';

	}
}
