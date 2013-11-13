Custom Login Bridge for Wordpress
=================================

Allows logging in from another data source. All users will be logged in as the same Wordpress user.
This is useful for allowing users from an external data source access toy your protected pages.

How to
------
Create a user in your Wordpress to be the bridge user. Keep in mind that all users will be logged in as this user so
you should give this user as little access as possible. The default username is 'client'. If you use another name
then make sure you define the 'CUSTOM_LOGIN_BRIDGE_USER' constant.

In your theme, create this function:

    function custom_login_bridge_validate($username, $password) {
		// return true or false
    }

If the function returns true, the user will be logged in or false will deny access.

TODO
----
- The bridge user should not be able to comment or comments should be anonymous if commenting is allowed.