<?php
define( 'WP_CACHE', true );


 
//Begin Really Simple SSL key
define('RSSSL_KEY', 'MphTyvfdSrIwdBEfTwICh1Y2jIE6r56SibqGtQgDdcILyZ4tkgOXOSxMaxtc1Q3b');
//END Really Simple SSL key
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'earthgo_staging' );
/** Database username */
define( 'DB_USER', 'earthgo_staging' );
/** Database password */
define( 'DB_PASSWORD', 'OdGhNjraXvet' );
/** Database hostname */
define( 'DB_HOST', 'localhost' );
/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );
/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY','_i-}HFeG#C$oY+x,V$D/+O+{QCr7q|nM(J]<@FSUZwC:X,|QO{Xm,7[|iJ2#:6`.');
define('SECURE_AUTH_KEY','x#E%E8^YiLU0i/U`z[->VsH3?|DP-7KD#q&WL}%R$Fgnp/,^<$|,i#(kw|<D&#=$');
define('LOGGED_IN_KEY','#gEQetZnR`:wUhX.t_O;060|rF# |n+gec^xmrS}E&C8-ac8c6ec`S6q;s:iHvkn');
define('NONCE_KEY','K3*qen}-+Mu,YB}Y&f^$;}H|%U$GZ*,CVMD]Xl|*+KKN&v^^BmnH{vL.~mvw+l( ');
define('AUTH_SALT',')~JmGl6;gE0P;E!C(k;qf_B0B$?=:mH**<dj_9T<czJ~_z1?6U~U` creABUBM <');
define('SECURE_AUTH_SALT',';wc4fw`V];FW---i%#7I+O[& xxb+pgzDL{.H=z4LF$ ldo(*2?FV!u(`faAI#&h');
define('LOGGED_IN_SALT','Wlk`dShE(G2NE1<HX1XRI3yK^n<@v2+!~.y^riO8U9*uam?_+,U%t!pzYa~+!1i{');
define('NONCE_SALT','ayIKl/rT$p|PGB6OScn!#E,m(? vD%!M{&R9HNAy+4ruo#wZy?n5G/LRSaMEN^rA');
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );
/* Add any custom values between this line and the "stop editing" line. */
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';