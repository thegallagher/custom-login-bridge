<?php
/**
 * Plugin Name: Custom Login Bridge
 * Plugin URI: http://github.com/thegallagher/custom-login-bridge
 * Description: Allows logging in from another data source. All users will be logged in as the same Wordpress user.
 * Version: 0.1.0
 * Author: David Gallagher
 * Author URI: http://github.com/thegallagher
 * License: MIT
 */

// Everyone who logins in through the bridge will be logged in as this user.
if (!defined('CUSTOM_LOGIN_BRIDGE_USER')) {
	define('CUSTOM_LOGIN_BRIDGE_USER', 'client');
}

// Validate a user. Override this function with a function which to validate your users.
if (!function_exists('custom_login_bridge_validate')) {
	function custom_login_bridge_validate($username, $password) {
		return false;
	}
}

// Get the bridge user.
function custom_login_bridge_get_bridge_user() {
	return get_user_by('login', CUSTOM_LOGIN_BRIDGE_USER);
}

// Adds authentication for bridged users.
add_filter('authenticate', 'custom_login_bridge_authenticate', 50, 3);
function custom_login_bridge_authenticate($user, $username, $password) {
	if (is_a($user, 'WP_User')) {
		return $user;
	}

	if (custom_login_bridge_validate($username, $password)) {
		return custom_login_bridge_get_bridge_user();
	}

	return null;
}

// Prevents bridge the user from accessing the admin section.
add_action('admin_init', 'custom_login_bridge_block_dashboard');
function custom_login_bridge_block_dashboard() {
	$user = wp_get_current_user();
	if ($user->user_login === CUSTOM_LOGIN_BRIDGE_USER && !constant('DOING_AJAX')) {
		wp_redirect(home_url());
		exit;
	}
}