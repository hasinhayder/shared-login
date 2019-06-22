<?php
function sharedlogin_get_hashcode() {
	$random_value = mt_rand( 0, 100000 ) . time() . mt_rand( 0, 100000 ) . wp_generate_uuid4();
	$hash         = md5( $random_value );
	return $hash;
}

function sharedlogin_get_users() {
	$sharedlogin_users = array();
	$_users            = get_users();
	foreach ( $_users as $_user ) {
		$sharedlogin_users[ $_user->ID ] = $_user->display_name ;
	}

	return $sharedlogin_users;
}

function sharedlogin_get_user_ip() {

	$client  = isset( $_SERVER['HTTP_CLIENT_IP'] ) ? $_SERVER['HTTP_CLIENT_IP'] : "0";
	$forward = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : "0";
	$remote  = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : "0";

	if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
		$ip = $client;
	} elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
		$ip = $forward;
	} else {
		$ip = $remote;
	}

	return $ip;
}


