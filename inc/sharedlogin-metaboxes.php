<?php
function sharedlogin_metabox_options_init( $options ) {
	$options[] = array(
		'id'        => 'sharedlogin_details',
		'title'     => __( 'Shared Login Settings', 'shared-login' ),
		'post_type' => 'shared-login', // or post or CPT or array( 'page', 'post' )
		'context'   => 'normal',
		'priority'  => 'default',
		'sections'  => array(
			array(
				'name'   => 'sharedlogin_section_shortcode',
				'icon'   => 'fa fa-image',
				'fields' => array(
					array(
						'id'      => 'sharedlogin_active',
						'type'    => 'switcher',
						'title'   => __( 'Active', 'shared-login' ),
						'default' => true
					),
					array(
						'id'         => 'sharedlogin_user',
						'type'       => 'select',
						'title'      => __( 'Select user to login as', 'shared-login' ),
						'attributes' => array(
							"id" => 'sharedlogin_user',
						),
						'options'    => sharedlogin_get_users()
					),
					array(
						'id'         => 'sharedlogin_howmanytimes',
						'type'       => 'number',
						'title'      => __( 'How many times it can be used?', 'shared-login' ),
						'attributes' => array( "id" => 'sharedlogin_howmanytimes' ),
						'default'    => 1
					),
					array(
						'id'      => 'sharedlogin_restrict_ip_switch',
						'type'    => 'switcher',
						'title'   => __( 'Restrict by IP address', 'shared-login' ),
						'default' => false
					),
					array(
						'id'         => 'sharedlogin_restrict_ip',
						'type'       => 'text',
						'title'      => __( 'Restricted IP ddress', 'shared-login' ),
						'attributes' => array( "id" => 'sharedlogin_howmanytimes' ),
						'dependency' => array( "sharedlogin_restrict_ip_switch", "==", true )
					),

					array(
						'id'      => 'sharedlogin_email_notification',
						'type'    => 'switcher',
						'title'   => __( 'Receive login notification email', 'shared-login' ),
						'default' => false
					),
					array(
						'id'         => 'sharedlogin_email_notification_address',
						'type'       => 'text',
						'title'      => __( 'Login notification email', 'shared-login' ),
						'dependency' => array( "sharedlogin_email_notification", "==", true )
					),

					array(
						'id'      => 'sharedlogin_secret_on',
						'type'    => 'switcher',
						'title'   => __( 'Extra protection - require secret word', 'shared-login' ),
						'default' => false
					),
					array(
						'id'         => 'sharedlogin_secret',
						'type'       => 'text',
						'title'      => __( 'Secret word', 'shared-login' ),
						'dependency' => array( "sharedlogin_secret_on", "==", true )
					),
				)
			)
		)
	);

	return $options;
}