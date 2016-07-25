<?php

/*-------------------------------------------*/
/*  Load modules
/*-------------------------------------------*/
if ( ! class_exists( 'Vk_Admin' ) )
{
	require_once( 'vk-admin/class.vk-admin.php' );
}

$admin_pages = array( 'settings_page_pad_plugin_options' );
Vk_Admin::admin_scripts( $admin_pages );
