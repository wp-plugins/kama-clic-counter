<?php

/* included in KCC Class */

// TinyMCE
if ( ! get_user_option('rich_editing') )
	return;
	
add_filter( 'mce_buttons_2',          'kcc_mce_register_buttons');
add_filter( 'mce_external_plugins', 'kcc_mce_js');

function kcc_mce_register_buttons( $buttons ){
	$last = array_pop( $buttons );
	$buttons[] = "kcc";
	$buttons[] = $last;
	return $buttons;
}

function kcc_mce_js( $plugin_array ){
	$plugin_array['KCC'] = plugin_dir_url(__FILE__) . 'mce.js';
	return $plugin_array;
}

