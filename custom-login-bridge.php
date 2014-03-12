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

// Get the bridge user.
function custom_login_bridge_get_bridge_user() {
	$login = 'client';
	if (defined('CUSTOM_LOGIN_BRIDGE_USER')) {
		$login = CUSTOM_LOGIN_BRIDGE_USER;
	}

	return get_user_by('login', $login);
}

// Adds authentication for bridged users.
add_filter('authenticate', 'custom_login_bridge_authenticate', 50, 3);
function custom_login_bridge_authenticate($user, $username, $password) {
	if (is_a($user, 'WP_User')) {
		return $user;
	}

	if (!function_exists('custom_login_bridge_validate')) {
		return null;
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
	$bridge_user = custom_login_bridge_get_bridge_user();
	if ($user->user_login === $bridge_user->user_login && (!defined('DOING_AJAX') || DOING_AJAX)) {
		wp_redirect(home_url());
		exit;
	}
}