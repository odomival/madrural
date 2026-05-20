<?php
define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'madrurp416');

/** MySQL database username */
define('DB_USER', 'madrurp416');

/** MySQL database password */
define('DB_PASSWORD', 'quuqNveqN2ZT');

/** MySQL hostname */
define('DB_HOST', 'madrurp416.mysql.db:3306');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'kFaVtH6rbzllDKnNjeBmm8jm+J9cMx/Bq2UYw4XTyzBeBXuLhw8+DetCNgmE');
define('SECURE_AUTH_KEY',  '1bZnXj0oUuYErpbRG8b/vWLVz6F0BNnMM7FzMFrd8MXxwBCTsnURdd0XlQ9+');
define('LOGGED_IN_KEY',    'ripqD+pZDjT+gQAumeVLvNAZESlMhGbGIFFojjI3PrXcp1ilGa/Q4/2xcPWP');
define('NONCE_KEY',        'hlqQ4vRjkQ21NkN62PcVBLZyz8LubjnYTqbLkVqPA3t4ma3tbItGLVP1F0g0');
define('AUTH_SALT',        'DiUymVYzCHVluDZ0hEoZJWK55LB5L41j6Y/b63VWfWI6/j1r/Y5Nu3xTTuyZ');
define('SECURE_AUTH_SALT', '3cNL2j3J4cXfhNNxuICFYaw7iDB999CGiVBVvJPJ7IHjhKG7pZujRIfcXAT8');
define('LOGGED_IN_SALT',   'BGcyfAuT1XWmtzgfuCQE0m4FaUgEkzCUTrqY50UbA/2btI5l2jatqCzuztLp');
define('NONCE_SALT',       'pRhlKvoKaruHgvjp9u8/bMzPILF1VujDRdj6aUvHLime8rpAZEYqqkaCVceB');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'mod145_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
/*define('WP_DEBUG', false);*/
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);


define('ADMIN_COOKIE_PATH', '/');
define('COOKIE_DOMAIN', '');
define('COOKIEPATH', '');
define('SITECOOKIEPATH', '');

/* That's all, stop editing! Happy blogging. */
define('WP_MEMORY_LIMIT','768M');
/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/* Fixes "Add media button not working", see http://www.carnfieldwebdesign.co.uk/blog/wordpress-fix-add-media-button-not-working/ */
define('CONCATENATE_SCRIPTS', false );

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

set_time_limit(600);


