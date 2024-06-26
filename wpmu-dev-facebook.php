<?php
/*
Plugin Name: Ultimate Facebook
Plugin URI: http://premium.wpmudev.org/project/ultimate-facebook
Description: Easy Facebook integration: share your blog posts, autopost to your wall, login and registration integration, BuddyPress profiles support and more. Please, configure the plugin first.
Version: 2.8.2
Text Domain: wdfb
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
WDP ID: 228

Copyright 2009-2011 Incsub (http://incsub.com)
Author - Ve Bailovity (Incsub)
Contributors - Umesh Kumar, Julien Zerbib

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define ( 'WDFB_PLUGIN_SELF_DIRNAME', basename( dirname( __FILE__ ) ));
define ( 'WDFB_PROTOCOL', ( is_ssl() ? 'https://' : 'http://' ));
define ( 'WDFB_PLUGIN_CORE_URL', plugins_url(), true );
define ( 'WDFB_PLUGIN_CORE_BASENAME', plugin_basename( __FILE__ ));
define ( 'WDFB_PLUGIN_VERSION', '2.8.2' );
if ( ! defined( 'WDFB_MEMBERSHIP_INSTALLED' ) ) {
	define ( 'WDFB_MEMBERSHIP_INSTALLED', ( defined( 'MEMBERSHIP_MASTER_ADMIN' ) && defined( 'MEMBERSHIP_SETACTIVATORAS_ADMIN' ) ));
}

//Setup proper paths/URLs and load text domains
if ( is_multisite() && defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {

	define ( 'WDFB_PLUGIN_LOCATION', 'mu-plugins', true );

	define ( 'WDFB_PLUGIN_BASE_DIR', WPMU_PLUGIN_DIR, true );

	define ( 'WDFB_PLUGIN_URL', apply_filters( 'wdfb-core-plugin_url', str_replace( 'http://', WDFB_PROTOCOL, WPMU_PLUGIN_URL ) ), true );

	$textdomain_handler = 'load_muplugin_textdomain';

} else if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . WDFB_PLUGIN_SELF_DIRNAME . '/' . basename( __FILE__ ) ) ) {

	define ( 'WDFB_PLUGIN_LOCATION', 'subfolder-plugins');

	define ( 'WDFB_PLUGIN_BASE_DIR', WP_PLUGIN_DIR . '/' . WDFB_PLUGIN_SELF_DIRNAME);

	define ( 'WDFB_PLUGIN_URL', apply_filters( 'wdfb-core-plugin_url', str_replace( 'http://', WDFB_PROTOCOL, WP_PLUGIN_URL ) . '/' . WDFB_PLUGIN_SELF_DIRNAME ) );

	$textdomain_handler = 'load_plugin_textdomain';

} else if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {

	define ( 'WDFB_PLUGIN_LOCATION', 'plugins', true );

	define ( 'WDFB_PLUGIN_BASE_DIR', WP_PLUGIN_DIR, true );

	define ( 'WDFB_PLUGIN_URL', apply_filters( 'wdfb-core-plugin_url', str_replace( 'http://', WDFB_PROTOCOL, WP_PLUGIN_URL ) ), true );

	$textdomain_handler = 'load_plugin_textdomain';

} else {
	// No textdomain is loaded because we can't determine the plugin location.
	// No point in trying to add textdomain to string and/or localizing it.
	wp_die( __( 'There was an issue determining where Facebook plugin is installed. Please reinstall.' ) );
}

$textdomain_handler( 'wdfb', false, WDFB_PLUGIN_SELF_DIRNAME . '/languages/' );


/**
 * Dashboard permissions widget function.
 */
function wdfb_dashboard_permissions_widget() {
	?>
	<div class="wdfb_perms_root" style="display:none">
		<p class="wdfb_perms_granted">
			<span class="wdfb_message"><?php echo __( 'You already granted extended permissions', 'wdfb' ); ?></span>
		</p>

		<p class="wdfb_perms_not_granted">
			<a href="#" class="wdfb_grant_perms" data-wdfb_locale="<?php echo wdfb_get_locale(); ?>" data-wdfb_perms="<?php echo Wdfb_Permissions::get_permissions(); ?>"><?php echo __( 'Grant extended permissions', 'wdfb' ); ?></a>
		</p>
	</div>
	<script type="text/javascript" src="<?php echo WDFB_PLUGIN_URL; ?> '/js/check_permissions.js"></script><?php
}

function wdfb_add_dashboard_permissions_widget() {
	wp_add_dashboard_widget( 'wdfb_dashboard_permissions_widget', 'Facebook Permissions', 'wdfb_dashboard_permissions_widget' );
}

/**
 * Dashboard BuddyPress/WordPress profile fill-up widget function.
 */
function wdfb_dashboard_profile_widget() {
	$profile = apply_filters( 'wdfb-profile_name', '<em>' . get_bloginfo( 'name' ) . '</em>' ); //defined('BP_VERSION') ? "BuddyPress" : "WordPress";
	echo '<a href="#" class="wdfb_fill_profile">' . sprintf( __( 'Fill my %s profile with Facebook data', 'wdfb' ), $profile ) . '</a>';
	echo '<script type="text/javascript">(function ($) { $(function () { $(".wdfb_fill_profile").click(function () { var $me = $(this); var oldHtml = $me.html(); try {var url = _wdfb_ajaxurl;} catch (e) { var url = ajaxurl; } $me.html("Please, wait... <img src=\"' . WDFB_PLUGIN_URL . '/img/waiting.gif\">"); $.post(url, {"action": "wdfb_populate_profile"}, function (data) { $me.html(oldHtml); }); return false; }); }); })(jQuery);</script>';
}

function wdfb_add_dashboard_profile_widget() {
	$profile = apply_filters( 'wdfb-profile_name', '<em>' . get_bloginfo( 'name' ) . '</em>' ); //defined('BP_VERSION') ? "BuddyPress" : "WordPress";
	wp_add_dashboard_widget( 'wdfb_dashboard_profile_widget', "My {$profile} profile", 'wdfb_dashboard_profile_widget' );
}

/*
// Deprecated
if (file_exists(WDFB_PLUGIN_BASE_DIR . '/lib/external/wpmudev-dash-notification.php')) {
	require_once WDFB_PLUGIN_BASE_DIR . '/lib/external/wpmudev-dash-notification.php';
}
*/
if ( ! class_exists( 'Facebook' ) ) {
	require_once( WDFB_PLUGIN_BASE_DIR . '/lib/external/facebook.php' );
}
require_once( WDFB_PLUGIN_BASE_DIR . '/lib/wdfb_utilities.php' );
require_once( WDFB_PLUGIN_BASE_DIR . '/lib/wdfb_transients_api.php' );
require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_permissions.php' );
require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_options_registry.php' );
require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_marker_replacer.php' );
require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_comments_importer.php' );
require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_model.php' );
require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_error_log.php' );


require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_installer.php' );
Wdfb_Installer::check();


// Require and initialize widgets
$data = Wdfb_OptionsRegistry::get_instance();
if ( $data->get_option( 'wdfb_widget_pack', 'albums_allowed' ) ) {
	require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_widget_albums.php' );
	add_action( 'widgets_init', create_function( '', "register_widget('Wdfb_WidgetAlbums');" ) );
}
if ( $data->get_option( 'wdfb_widget_pack', 'events_allowed' ) ) {
	require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_widget_events.php' );
	add_action( 'widgets_init', create_function( '', "register_widget('Wdfb_WidgetEvents');" ) );
}
if ( $data->get_option( 'wdfb_widget_pack', 'likebox_allowed' ) ) {
	require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_widget_likebox.php' );
	add_action( 'widgets_init', create_function( '', "register_widget('Wdfb_WidgetLikebox');" ) );
}
if ( $data->get_option( 'wdfb_widget_pack', 'connect_allowed' ) ) {
	require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_widget_connect.php' );
	add_action( 'widgets_init', create_function( '', "register_widget('Wdfb_WidgetConnect');" ) );
}
if ( $data->get_option( 'wdfb_widget_pack', 'recent_comments_allowed' ) ) {
	require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_widget_recent_comments.php' );
	add_action( 'widgets_init', create_function( '', "register_widget('Wdfb_WidgetRecentComments');" ) );
}
if ( $data->get_option( 'wdfb_widget_pack', 'dashboard_permissions_allowed' ) ) {
	add_action( 'wp_dashboard_setup', 'wdfb_add_dashboard_permissions_widget' );
	add_action( 'wp_dashboard_setup', 'wdfb_add_dashboard_profile_widget' );
}


/**
 * Schedule cron jobs for comments import.
 */
function wdfb_comment_import() {
	$data = Wdfb_OptionsRegistry::get_instance();
	if ( ! $data->get_option( 'wdfb_comments', 'import_fb_comments' ) ) {
		return;
	} // Don't import comments
	Wdfb_CommentsImporter::serve();

	unset( $data );
}

add_action( 'wdfb_import_comments', 'wdfb_comment_import' ); //array($importer, 'serve'));

if ( ! wp_next_scheduled( 'wdfb_import_comments' ) ) {
	wp_schedule_event( time() + 600, 'hourly', 'wdfb_import_comments' );
}

define( "WDFB_CORE_IS_ADMIN", ( is_admin() || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ));

function _wdfb_initialize() {
	// Include the metabox abstraction
	require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_metabox.php' );
	$og = new Wdfb_Metabox_OpenGraph;

	if ( apply_filters( 'wdfb-core-is_admin', WDFB_CORE_IS_ADMIN ) ) {
		require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_admin_help.php' );
		require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_admin_form_renderer.php' );
		require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_admin_pages.php' );
		require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_tutorial.php' );
		Wdfb_Tutorial::serve();
		Wdfb_AdminPages::serve();
	} else {
		require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_public_pages.php' );
		Wdfb_PublicPages::serve();
	}
	require_once( WDFB_PLUGIN_BASE_DIR . '/lib/class_wdfb_universal_worker.php' );
	Wdfb_UniversalWorker::serve();
}

add_filter( 'get_comment_text', 'decode_utf_8', 1000, 2 );
function decode_utf_8( $content, $comment ) {

	if( empty( $comment )) {
		return $content;
	}
	$comment_meta = get_comment_meta( $comment->comment_ID, 'wdfb_comment', true );
	if ( empty( $comment_meta ) ) {
		//Do not decode wordpress comments
		return $content;
	}

	return html_entity_decode( utf8_decode( $content ) );

}

add_action( 'plugins_loaded', '_wdfb_initialize' );