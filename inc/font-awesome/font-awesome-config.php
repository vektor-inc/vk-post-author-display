<?php
/**
 * VK Blocks Font Awesome
 *
 * @package vk_blocks
 */
require_once VK_PAD_DIR . 'vendor/autoload.php';
use VektorInc\VK_Font_Awesome_Versions\VkFontAwesomeVersions;

/*
 * Font Awesome Load modules
 */
if ( ! class_exists( 'Vk_Font_Awesome_Versions' ) ) {
	new VkFontAwesomeVersions();
	global $font_awesome_directory_uri;
	// phpcs:ignore
	$font_awesome_directory_uri = VK_PAD_URL . 'vendor/vektor-inc/font-awesome-versions/src/';
}