<?php
/**
 * Plugin Name: Custom Login Bridge
 * Plugin URI: http://github.com/thegallagher/custom-login-bridge
 * Description: Allows logging in from another data source. All users will be logged in as the same Wordpress user.
 * Version: 0.1.1
 * Author: David Gallagher
 * Author URI: http://github.com/thegallagher
 * License: MIT
 */

/**
 * Get the bridge user
 *
 * @return bool|WP_User
 */
function custom_login_bridge_get_bridge_user() {
	$login = 'client';
	if ( defined( 'CUSTOM_LOGIN_BRIDGE_USER' ) ) {
		$login = CUSTOM_LOGIN_BRIDGE_USER;
	}

	return get_user_by( 'login', $login );
}

/**
 * Adds authentication for bridged users
 *
 * @param WP_User $user
 * @param string $username
 * @param string $password
 * @return bool|null|WP_User
 */
function custom_login_bridge_authenticate( $user, $username, $password ) {
	if ( is_a( $user, 'WP_User' ) ) {
		return $user;
	}

	if ( ! function_exists( 'custom_login_bridge_validate' ) ) {
		return null;
	}

	if ( custom_login_bridge_validate( $username, $password ) ) {
		return custom_login_bridge_get_bridge_user();
	}

	return null;
}
add_filter( 'authenticate', 'custom_login_bridge_authenticate', 50, 3 );

/**
 * Prevents bridge user from accessing the admin section.
 */
function custom_login_bridge_block_dashboard() {
	$user = wp_get_current_user();
	$bridge_user = custom_login_bridge_get_bridge_user();
	if ( $user->user_login === $bridge_user->user_login && ( ! defined( 'DOING_AJAX' ) || DOING_AJAX ) ) {
		wp_redirect( home_url() );
		exit;
	}
}
add_action( 'admin_init', 'custom_login_bridge_block_dashboard' );

/**
 * Shortcode to display content only to specified users
 *
 * @param $atts
 * @param null $content
 * @return null|string
 */
function custom_login_bridge_if_user_shortcode( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'id'    => null,
		'login' => null,
		'roles' => null,
		'none'  => null,
	), $atts, 'clb-if-user' ) );

	$current_user = wp_get_current_user();
	$display = false;
	if ( empty( $id ) && empty( $login ) && empty( $roles ) && empty( $none ) ) {
		$display = $current_user->exists();
	}
	if ( ! $display && ! empty( $id ) ) {
		$ids = explode( ',', $id );
		$display = in_array( $current_user->ID, $ids );
	}
	if ( ! $display && ! empty( $login ) ) {
		$logins = explode( ',', $login );
		$display = in_array( $current_user->user_login, $logins );
	}
	if ( ! $display && ! empty( $roles ) ) {
		$roles = explode( ',', $roles );
		$user_role = array_shift( $current_user->roles );
		$display = in_array ( $user_role, $roles );
	}
	if ( ! $display && ! empty( $none ) ) {
		$display = ! $current_user->exists();
	}

	if ( $display ) {
		return do_shortcode($content);
	}
	return null;
}
add_shortcode( 'clb-if-user', 'custom_login_bridge_if_user_shortcode' );

/**
 * Shortcode to display user information
 *
 * @param $atts
 * @return string|null
 */
function custom_login_bridge_user_info_shortcode( $atts ) {
	extract( shortcode_atts( array(
		'field' => 'user_login',
	), $atts, 'clb-user-info' ) );

	$current_user = wp_get_current_user();
	switch ( $field ) {
		case 'roles':
		case 'caps':
		case 'allcaps':
			return implode( ',', $current_user->{$field} );
	}

	if ( isset( $current_user->{$field} ) ) {
		return $current_user->{$field};
	}
	return null;
}
add_shortcode( 'clb-user-info', 'custom_login_bridge_user_info_shortcode' );

/**
 * Shortcode to display a login form
 *
 * A filter can be applied to the form after it has been generated.
 *
 * @see wp_login_form() used to generate the form. Attributes are passed to the function.
 *
 * @param $atts
 * @return string
 */
function custom_login_bridge_login_form_shortcode( $atts ) {
	if ( is_user_logged_in() ) {
		return null;
	}

	$atts = shortcode_atts( array(
		'redirect'       => $_SERVER['REQUEST_URI'],
		'form_id'        => 'loginform',
		'label_username' => 'Username',
		'label_password' => 'Password',
		'label_remember' => 'Remember Me',
		'label_log_in'   => 'Log In',
		'id_username'    => 'user_login',
		'id_password'    => 'user_pass',
		'id_remember'    => 'rememberme',
		'id_submit'      => 'wp-submit',
		'remember'       => 'true',
		'value_username' => null,
		'value_remember' => 'false',
	), $atts, 'clb-login-form' );

	$atts['echo']           = false;
	$atts['redirect']       = site_url( $atts['redirect'] );
	$atts['label_username'] = __( $atts['label_username'] );
	$atts['label_password'] = __( $atts['label_password'] );
	$atts['label_remember'] = __( $atts['label_remember'] );
	$atts['label_log_in']   = __( $atts['label_log_in'] );
	$atts['remember']       = 'true' ==  $atts['remember'];
	$atts['value_remember'] = 'true' ==  $atts['value_remember'];

	$form = wp_login_form( $atts );
	return apply_filters( 'custom_login_bridge_login_form', $form );
}
add_shortcode( 'clb-login-form', 'custom_login_bridge_login_form_shortcode' );

/**
 * Shortcode to display login/logout link
 *
 * @param $atts
 * @return string
 */
function custom_login_bridge_loginout( $atts ) {
	extract( shortcode_atts( array(
		'redirect'  => home_url(),
	), $atts, 'clb-loginout' ) );

	return wp_loginout( $redirect, false );
}
add_shortcode( 'clb-loginout', 'custom_login_bridge_loginout' );

/**
 * Fix empty paragraphs
 *
 * @param $content
 * @return string
 */
function custom_login_bridge_fix_empty_p( $content ) {
	$replace = '#(?:<br\s*/?>|\s)*(\[/?clb-(?:login-form|if-user).*?\])(?:<br\s*/?>|\s)*#i';
	return preg_replace($replace, '\1', $content);
}
add_filter( 'the_content', 'custom_login_bridge_fix_empty_p' );