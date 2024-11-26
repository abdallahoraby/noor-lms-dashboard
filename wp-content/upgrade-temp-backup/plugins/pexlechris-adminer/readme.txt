=== Database Management tool - Adminer ===
Contributors: pexlechris
Donate link: https://www.paypal.com/donate/?hosted_button_id=VDPQY9UE2SQRQ
Plugin Name: Database Management tool - Adminer
Author: Pexle Chris
Author URI: https://www.pexlechris.dev
Tags: Adminer, Database, sql, mysql, mariadb, Database Manager
Version: 2.2.2
Stable tag: 2.2.2
Adminer version: 4.8.1
Requires at least: 4.7.0
Tested up to: 6.4.3
Requires PHP: 5.6
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage the database from your WordPress Dashboard using Adminer.

== Description ==

The best database management tool for the best CMS.

This plugin uses the tool [Adminer 4.8.1](https://www.adminer.org/) in order to give database access to administrators directly from the Dashboard.
As simple as the previous sentence!

Compatible also with WordPress Multisite installations

== WP Adminer access positions ==
You can access the WP Adminer from the above positions:
1. WP Adminer URL in the Admin Bar
2. WP Adminer Tools Page (Dashboard > Tools > WP Adminer)

== Explore my other plugins ==
* [Library Viewer](https://www.pexlechris.dev/library-viewer/wp-wpadminer): With Library Viewer, you can display the containing files and the containing folders of a “specific folder” of your (FTP) server to your users in the front-end.
* [Gift Wrapping for WooCommerce](https://wordpress.org/plugins/gift-wrapping-for-woocommerce): This plugin allows customers to select a gift wrapper for their orders, via a checkbox in the checkout page.


== Screenshots ==

1. The WP Adminer opened from Admin Bar

== Frequently Asked Questions ==

 = Is it safe? =
 Yes, because only administrators have access to WP Adminer. If a guest tries to access the WP Adminer URL, a 404 page will be shown up.


 = Who have access in WP Adminer? =
&nbsp;

 * In the case of single site WordPress installations, only Administrators have access in WP Adminer, because by default only administrator have the `manage_options` capability.
 * In the case of WordPress Multisite installations, only Super Admins have access in WP Adminer, because by default only Super Admins have the `manage_network_options` capability.


 = How to allow other capabilities or roles to have access to WP Adminer? =
 Just use the filter `pexlechris_adminer_access_capabilities` and return the array of desired capabilities that you want to have access to WP Adminer.
 For roles, just use the corresponding capabilities, while checking against particular roles in place of a capability is supported in part, this practice is discouraged as it may produce unreliable results.


 = WP Adminer is stuck in an endless loop, constantly refreshing the page without stopping. What is the issue? =
 This issue maybe is due to the caching engine that your browser OR server uses!
 * You can try to whitelist the WP Adminer URL, OR
 * You can change the WP Adminer URL to a URL that is already whitelisted. For example:
 `define( 'PEXLECHRIS_ADMINER_SLUG', 'wp-admin/adminer');`


 = How to add my own JS and/or CSS in adminer head? =
 You need to use action `pexlechris_adminer_head` as follows:
 `
 add_action('pexlechris_adminer_head', function(){
    ?>
    <script nonce="<?php echo esc_attr( get_nonce() )?>"> // get_nonce is an adminer function
       // Place your JS code here
    </script>
    <style>
       /* Place your CSS code here */
    </style>
    <?php
 });
 `


 = How can I add other Adminer plugins or Adminer extensions? =
 In Adminer's website there is documentation about [Adminer plugins](https://www.adminer.org/en/plugins/) and [Adminer extensions](https://www.adminer.org/en/extension/).
 In order to define function adminer_object() before this plugin define it, you need to define it inside the hook `pexlechris_adminer_before_adminer_loads`.
 More in the phpDoc below:
 `
/**
 * adminer_object can be overridden, in WP action pexlechris_adminer_before_adminer_loads.
 * If a developer want to make his/her own changes (adding plugins, extensions or customizations),
 * it is strongly recommended to include_once the class Pexlechris_Adminer and extend it and
 * make adminer_object function return his/her new class.
 *
 * It is strongly recommended, because Pexlechris_Adminer class contains WordPress/Adminer integration (auto login with WordPress credentials)
 *
 * If a developer want to add just JS and/or CSS in head, he/she can just use the action pexlechris_adminer_head.
 * See plugin's FAQs, for more.
 *
 * @since 2.1.0
 *
 * @link https://www.adminer.org/en/plugins/#use Documentation URL.
 * @link https://www.adminer.org/en/plugins/ Adminer' plugins Documentation URL.
 * @link https://www.adminer.org/en/extension/ Adminer' extensions Documentation URL.
 */
 `


 = Can I limit access to some table/DB ? =
 The answer in this question is complicated.
 The best solution is to create a Database User with the appropriate privileges.
 Maybe you can do it also with WordPress actions.
 Read more in [this support ticket](https://wordpress.org/support/topic/limit-access-to-some-table-db/).


 = How can I access other databases in the same server and same database user? =
 By default, you haven't access to any database other than the site's database. In order to enable access, you need to add the following line code `define('PEXLECHRIS_ADMINER_HAVE_ACCESS_ONLY_IN_WP_DB', false);` in the wp-config.php file.


 = Why is Adminer better than phpMyAdmin? =
 Replace **phpMyAdmin** with **Adminer** and you will get a tidier user interface, better support for MySQL features, higher performance and more security. [See detailed comparison](https://www.adminer.org/en/phpmyadmin/).
 Adminer development priorities are: 1. Security, 2. User experience, 3. Performance, 4. Feature set, 5. Size.

== Installation ==

1. Download the plugin from [Official WP Plugin Repository](https://wordpress.org/plugins/pexlechris-adminer/)
2. Upload Plugin from your WP Dashboard ( Plugins > Add New > Upload Plugin ) the pexlechris-adminer.zip file.
3. Activate the plugin through the 'Plugins' menu in WordPress Dashboard



== Changelog ==
 = 2.2. =
* Before version 2.2, if PEXLECHRIS_ADMINER_SLUG ends with a slash, WP Adminer was not working.
If you still have this problem, try deleting the file wp-content/mu-plugins/pexlechris_adminer_avoid_conflicts_with_other_plugins.php in order to have it installed again automatically.

 = 2.2.1 =
* From now on, this plugin requires WordPress version at least 4.7.0 or later. According to Wordfence, versions below 4.7.0 have a vulnerability that can allow site takeover.

 = 2.2.0 =
* Tested up to 6.4.2
* SOS: From now when WP Adminer runs, only WP Adminer plugin is running (a must-use plugin is automatically installed and is being deleted on plugin deactivation).
So the only way to extend WP Adminer plugin's functionalities using wp hooks is using a must-use plugin.
Helpful Guide: [How to add PHP Hooks in your WordPress Site using a must-use plugin](https://www.pexlechris.dev/how-to-add-php-hooks-in-your-wordpress-site/)
* Hide php errors even if WP_DEBUG_DISPLAY is enabled, in action pexlechris_adminer_before_adminer_loads with priority 100

 = 2.1.1 =
* Tested up to 6.3.2
* From now on, the PEXLECHRIS_ADMINER_SLUG can contain slashes. For example, you can use as below
`define( 'PEXLECHRIS_ADMINER_SLUG', 'wp-admin/wp-adminer');`
* Load textdomain before WP Adminer loads
* Hide php errors even if WP_DEBUG_DISPLAY is enabled, AFTER action pexlechris_adminer_before_adminer_loads
* FAQ on how to fix WP Adminer endless loop has been added.

 = 2.1.0 =
* Tested up to 6.1
* Code Refactoring
* Hide php errors even if WP_DEBUG_DISPLAY is enabled
* Fix Adminer warning `Undefined variable $Ah`
* FAQ on how to add your CSS & JS code in adminer interface has been added.
* FAQ on how to customize adminer has been added.
* FAQ on how to limit access to some table/DB has been added.

 = 2.0.1 =
*   Adminer is an admin tool, so now is considered as admin interface. is_admin() function now return true, when Adminer is viewed

 = 2.0.0 =
*   Tested up to 6.0.1
*   PLEASE UPDATE NOW! Vulnerability issue with password as plain text fixed.
*   SOS! All functions and actions have been renamed. Please have a look in the code to find the new names, if you have written your own customization code before
*   Logout button have been hidden.
*   Adminer has been removed from Tools Page because iframes are not allowed in admin pages
*   print_css_inside_wp_adminer_tools_page action has been removed
*   print_js_inside_wp_adminer_tools_page action has been removed
*   print_js_inside_wp_adminer action has been removed
*   print_css_inside_wp_adminer action has been removed
*   From this version and then, developers can make their Adminer' customizations using the function adminer_object
    in the NEW pexlechris_adminer_before_adminer_loads action
    and to print code in head, developers can use the NEW action pexlechris_adminer_head
*	From this version and then, this plugin is also compatible with WordPress Multisite installations
*   From this version and then, you can change the slug of adminer using the constant PEXLECHRIS_ADMINER_SLUG (By default, adminer loads from www.site.com/wp-adminer )
*   From this version and then, you can log in even if the password is empty string (For some local setups)
*   From this version and then, by default you can only show wordpress database (to enable managing of other databases in same server see FAQ)


 = 1.0.0 =
*	Initial Release.
