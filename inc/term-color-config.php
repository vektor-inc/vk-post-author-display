<?php

/*-------------------------------------------*/
/*  Load modules
/*-------------------------------------------*/
if ( ! class_exists( 'Vk_term_color' ) )
{
	require_once( 'term-color/class.term-color.php' );

	/*  transrate
	/*-------------------------------------------*/
	function pad_term_color_translate(){
		__( 'Color', 'post-author-display' );
	}

	global $vk_term_color_textdomain;
	$vk_term_color_textdomain = 'post-author-display';

}