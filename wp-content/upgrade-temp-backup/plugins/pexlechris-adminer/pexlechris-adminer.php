<?php
/**
 * Plugin Name: Database Management tool - Adminer
 * Description: Manage the database from your WordPress Dashboard using Adminer
 * Version: 2.2.2
 * Stable tag: 2.2.2
 * Adminer version: 4.8.1
 * Author: Pexle Chris
 * Author URI: https://www.pexlechris.dev
 * Contributors: pexlechris
 * Domain Path: /languages
 * Requires at least: 4.7.0
 * Tested up to: 6.4.3
 * Requires PHP: 5.6
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}


if( !defined('PEXLECHRIS_ADMINER_SLUG') ){
	if( is_multisite() ){
		define('PEXLECHRIS_ADMINER_SLUG', 'multisite-adminer');
	}else{
		define('PEXLECHRIS_ADMINER_SLUG', 'wp-adminer');
	}
}

/**
 * PEXLECHRIS_ADMINER_DIR constant
 *
 * @since 2.1.0
 */
define('PEXLECHRIS_ADMINER_DIR', __DIR__);


/**
 * @since 2.2.0
 */
add_action( 'admin_init', 'pexlechris_adminer_copy_adminer_mu_plugin', 1 );
function pexlechris_adminer_copy_adminer_mu_plugin() {

    $file = 'pexlechris_adminer_avoid_conflicts_with_other_plugins.php';

    $from = PEXLECHRIS_ADMINER_DIR . '/' . $file;
    $to = WPMU_PLUGIN_DIR . '/' . $file;

    if( !file_exists($to) ){
		wp_mkdir_p(WPMU_PLUGIN_DIR);
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$WP_Filesystem_Direct = new WP_Filesystem_Direct([]);
		$WP_Filesystem_Direct->copy($from, $to);
    }

}

/**
 * @since 2.2.0
 */
register_deactivation_hook(__FILE__, 'pexlechris_adminer_delete_adminer_mu_plugin');
function pexlechris_adminer_delete_adminer_mu_plugin(){
	$mu_plugin = WPMU_PLUGIN_DIR . '/' . 'pexlechris_adminer_avoid_conflicts_with_other_plugins.php';
	if (file_exists($mu_plugin)) {
		unlink($mu_plugin);
	}
}



add_action( 'plugins_loaded', 'pexlechris_adminer_load_plugin_textdomain', 1 );
function pexlechris_adminer_load_plugin_textdomain() {
	load_plugin_textdomain(
		'pexlechris-adminer',
		false,
		dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
	);
}


//INIT
function determine_if_pexlechris_adminer_will_be_included()
{
	if( have_current_user_access_to_pexlechris_adminer() ){
		if( !defined('WP_ADMIN') ){
			define('WP_ADMIN', true); // adminer is an admin tool, so must be considered as admin interface
		}
		/**
		 * @hooked pexlechris_adminer_before_adminer_loads, priority: 10
		 * @hooked pexlechris_adminer_disable_display_errors_before_adminer_loads, priority: 100
		 */
		do_action('pexlechris_adminer_before_adminer_loads');
		include 'inc/adminer_includer.php';
		exit;
	}else{
		do_action('pexlechris_adminer_current_user_has_not_access');
	}
}

if( !function_exists('pexlechris_adminer_ends_with') ) {
	function pexlechris_adminer_ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		if( !$length ) {
			return true;
		}
		return substr( $haystack, -$length ) === $needle;
	}
}


if( !function_exists('pexlechris_is_current_url_the_wp_adminer_url') ){
	function pexlechris_is_current_url_the_wp_adminer_url(){
		$REQUEST_URI = parse_url( esc_url($_SERVER["REQUEST_URI"]), PHP_URL_PATH);
		$REQUEST_URI = untrailingslashit($REQUEST_URI);
		if( pexlechris_adminer_ends_with($REQUEST_URI, untrailingslashit(PEXLECHRIS_ADMINER_SLUG)) ){
			return true;
		}else{
			return false;
		}
	}
}


if( pexlechris_is_current_url_the_wp_adminer_url() )
{
    add_action('plugins_loaded', 'determine_if_pexlechris_adminer_will_be_included', 2);
}



//POSITION 1
add_action('admin_bar_menu', 'pexlechris_adminer_register_in_wp_admin_bar' , 50);
function pexlechris_adminer_register_in_wp_admin_bar($wp_admin_bar) {

	if( have_current_user_access_to_pexlechris_adminer() ){
		$args = array(
			'id' => 'wp_adminer',
			'title' => esc_html__('WP Adminer', 'pexlechris-adminer'),
			'href' => esc_url(site_url() . '/' . PEXLECHRIS_ADMINER_SLUG),
			"meta" => array(
				"target" => "_blank"
			)
		);
		$wp_admin_bar->add_node($args);
	}

}

//POSITION 2
add_action('admin_menu', 'register_pexlechris_adminer_as_tool');
function register_pexlechris_adminer_as_tool(){
	add_submenu_page(
		'tools.php',
		esc_html__('WP Adminer', 'pexlechris-adminer'),
		esc_html__('WP Adminer', 'pexlechris-adminer'),
		implode(',', pexlechris_adminer_access_capabilities()),
		PEXLECHRIS_ADMINER_SLUG,
		'pexlechris_adminer_tools_page_content',
		3
	);
}


//IN TOOLS
if( !function_exists('pexlechris_adminer_tools_page_content') ){
	function pexlechris_adminer_tools_page_content(){
		?>
		<br>
		<a href="<?php echo esc_url( site_url() . '/' . PEXLECHRIS_ADMINER_SLUG )?>" class="button-primary pexlechris-adminer-tools-page-button" target="_blank">
			<?php esc_html_e('Open Adminer in a new tab', 'pexlechris-adminer');?>
        </a>
		<?php
	}
}


function pexlechris_adminer_access_capabilities()
{
	if ( is_multisite() ) {
		//only Super Admins of website has the capability <code>manage_network_options</code>
		$capabilities = array('manage_network_options');
	} else {
		//only administrator of website has the capability <code>manage_options</code>
		$capabilities = array('manage_options');
	}

	$capabilities = apply_filters('pexlechris_adminer_access_capabilities', $capabilities);
	return $capabilities;
}


// can be overridden in a must-use plugin
if( !function_exists('have_current_user_access_to_pexlechris_adminer') ){
	function have_current_user_access_to_pexlechris_adminer()
	{
		foreach (pexlechris_adminer_access_capabilities() as $capability) {
			if( current_user_can($capability) ) return true;
		}

		return false;
	}
}


add_action('pexlechris_adminer_before_adminer_loads', 'pexlechris_adminer_before_adminer_loads');
function pexlechris_adminer_before_adminer_loads()
{
	if( !defined('PEXLECHRIS_ADMINER_HAVE_ACCESS_ONLY_IN_WP_DB') || true === PEXLECHRIS_ADMINER_HAVE_ACCESS_ONLY_IN_WP_DB ){
		if( !isset($_GET['db']) && isset($_GET['username']) && '' == $_GET['username'] ){
			// show wordpress database
			wp_redirect( $_SERVER["REQUEST_URI"] . '&db=' . DB_NAME);
			exit;
		}elseif( isset($_GET['db']) && DB_NAME != $_GET['db'] ){
			// if try to show another of wordpress database, wp_die
			wp_die(
				esc_html__("You haven't access to any database other than the site's database. In order to enable access, you need to add the following line code in the wp-config.php file", 'pexlechris-adminer') .
				"<pre>define('PEXLECHRIS_ADMINER_HAVE_ACCESS_ONLY_IN_WP_DB', false);</pre>"
			);
		}
	}
}


add_action('pexlechris_adminer_before_adminer_loads', 'pexlechris_adminer_disable_display_errors_before_adminer_loads', 100);
function pexlechris_adminer_disable_display_errors_before_adminer_loads()
{
	/**
	 * @since 2.1.0 firstly set
	 * @since 2.1.1 move after action pexlechris_adminer_before_adminer_loads
     * @since 2.2.0 moved to a wp action with priority 100
     */
	ini_set('display_errors', 0);
}
