<?php
/**
 * Plugin Name: Shared Login
 * Plugin URI: http://sharedlogin.thebinarymonk.net
 * Description: Create magic login URLs to allow users to login without username and password with various restrictions
 * Version: 1.0
 * Author: The Binary Monk
 * Author URI: http://thebinarymonk.net
 * Text Domain: shared-login
 * Domain Path: /languages/
 * License: GPL2
 */

defined( 'ABSPATH' ) or die( __( "No Direct Access", 'shared-login' ) );

require_once plugin_dir_path( __FILE__ ) . "/inc/csf/cs-framework.php";
require_once plugin_dir_path( __FILE__ ) . "/inc/sharedlogin-metaboxes.php";
require_once plugin_dir_path( __FILE__ ) . "/inc/sharedlogin-utility-functions.php";

class SharedLogin {
	public function __construct() {
		add_action( 'init', array( $this, 'sharedlogin_init' ) );
		add_action( 'plugins_loaded', array( $this, 'sharedlogin_load_textdomain' ) );
		add_action( 'template_redirect', array( $this, 'sharedlogin_redirect_post' ) );
		add_action( 'wp_logout', array( $this, 'sharedlogin_clear_cookie' ), 99 );
	}

	function sharedlogin_clear_cookie() {
		setcookie( "sharedlogin_nono", '', time() - 3600, "/" );
	}


	function sharedlogin_redirect_post() {
		$queried_post_type = get_query_var( 'post_type' );
		if ( is_single() && 'shared-login' == $queried_post_type ) {
			$post_url                  = get_permalink();
			$_id                       = get_the_ID();
			$sharedlogin_meta          = get_post_meta( $_id, "sharedlogin_details", true );
			$is_active                 = $sharedlogin_meta['sharedlogin_active'];
			$restricted_ip             = $sharedlogin_meta['sharedlogin_restrict_ip'];
			$restricted_ip_switch      = $sharedlogin_meta['sharedlogin_restrict_ip_switch'];
			$email_notification_active = $sharedlogin_meta['sharedlogin_email_notification'];
			$_user                     = $sharedlogin_meta['sharedlogin_user'];
			if ( $is_active ) {
				//check IP
				if ( $restricted_ip_switch ) {
					$user_ip_address = sharedlogin_get_user_ip();
					if ( $restricted_ip && $user_ip_address != $restricted_ip ) {
						wp_die( __( "you are not allowed to login from", 'shared-login' ) . ' ' . $user_ip_address );
					}
				}

				//check secret
				$secret_enabled = $sharedlogin_meta['sharedlogin_secret_on'];
				if ( $secret_enabled ) {
					//check if it's a $_POST call
					$secret = $sharedlogin_meta['sharedlogin_secret'];
					if ( ( isset( $_POST['secret'] ) && $_POST['secret'] != $secret ) || ( ! isset( $_POST['secret'] ) ) ) {
						wp_die( "<form class='sharedlogin-secret' method='POST' action={$post_url}><h4>" . __( "Please enter your secret", 'shared-login' ) . "</h4> <input style='margin-top:-5px;font-size:14px;line-height: 25px; width: 300px;' type='password' name='secret'/> <br/> <input style=\"cursor: pointer;margin-top:15px;background-color:#4CAF50;border:1px solid #4CAF50;color:#ffffff;display:inline-block;font-size:14px;line-height:28px;text-align:center;text-decoration:none;width:120px;-webkit-text-size-adjust:none;mso-hide:all;\" type='submit' value='" . __( "Login", 'shared-login' ) . "' /></form>" );
					}
				}

				//check how many times this sharedlogin permalink has been used
				$allowed_login_count = $sharedlogin_meta['sharedlogin_howmanytimes'];
				$login_count         = get_post_meta( $_id, "sharedlogin_login_count", true );

				if ( ! $login_count ) {
					$login_count = 1;
				} else {
					$login_count ++;
				}
				add_post_meta( $_id, "sharedlogin_login_count", $login_count );

				if ( $allowed_login_count <= $login_count ) {
					wp_trash_post( $_id );
				}


				//send email notification
				if ( $email_notification_active ) {
					$email = $sharedlogin_meta['sharedlogin_email_notification_address'];
					if ( $email ) {

						$site_url = site_url();
						$message  = <<<EOD
Hi Admin,

Someone just logged in {$site_url} using one of your shared login url {$post_url} from the IP address {$user_ip_address}, and we think you wanted to be notified about this. 

Thanks
Your Faithful SharedLogin Plugin
EOD;
						wp_mail( $email, __( "Someone just logged in using your shared login url", 'shared-login' ), $message );
					}
				}

				//set cookie to prevent this special user from accessing shared login
				setcookie( "sharedlogin_nono", 1, time() + 3600, "/" );

				//auth and redirection
				wp_clear_auth_cookie();
				wp_set_current_user( $_user );
				wp_set_auth_cookie( $_user );

				$user_admin_url = user_admin_url();
				wp_safe_redirect( $user_admin_url );
			} else {
				//it's not an active sharedlogin, lets go to home
				wp_safe_redirect( home_url() );
			}
		}
	}

	function sharedlogin_init() {

		if ( isset( $_COOKIE["sharedlogin_nono"] ) ) {
			return;
		}
		$this->sharedlogin_cpt_init();

		add_filter( 'sharedlogin_metabox_options', "sharedlogin_metabox_options_init" );

		$sharedlogin_metabox_options = array();
		$sharedlogin_metabox_options = apply_filters( "sharedlogin_metabox_options", $sharedlogin_metabox_options );
		new CSFramework_Metabox( $sharedlogin_metabox_options );
	}

	function sharedlogin_cpt_init() {
		$labels = array(
			'name'               => _x( 'Shared Logins', 'Post Type General Name', 'shared-login' ),
			'singular_name'      => _x( 'Shared Login', 'Post Type Singular Name', 'shared-login' ),
			'menu_name'          => __( 'Shared Login', 'shared-login' ),
			'name_admin_bar'     => __( 'Shared Login', 'shared-login' ),
			'parent_item_colon'  => __( 'Parent Shared Login:', 'shared-login' ),
			'all_items'          => __( 'All Shared Logins', 'shared-login' ),
			'add_new_item'       => __( 'Add New Shared Login', 'shared-login' ),
			'add_new'            => __( 'Add New', 'shared-login' ),
			'new_item'           => __( 'New Shared Login', 'shared-login' ),
			'edit_item'          => __( 'Edit Shared Login', 'shared-login' ),
			'update_item'        => __( 'Update Shared Login', 'shared-login' ),
			'view_item'          => __( 'View Shared Login', 'shared-login' ),
			'search_items'       => __( 'Search Shared Login', 'shared-login' ),
			'not_found'          => __( 'Not found', 'shared-login' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'shared-login' ),
		);

		$args = array(
			'label'               => __( 'Shared Login', 'shared-login' ),
			'description'         => __( 'Shared Logins', 'shared-login' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 70,
			'menu_icon'           => 'dashicons-admin-network',
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			'rewrite'             => array( "slug" => 'sharedlogin' ),
		);

		register_post_type( 'sharedlogin', $args );
	}

	function sharedlogin_load_textdomain() {
		load_plugin_textdomain( 'shared-login', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

new SharedLogin();
