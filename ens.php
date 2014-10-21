<?php
/**
 * Plugin Name: Export Network Stats
 * Plugin URI: http://github.io/sanghviharshit
 * Description: Exports various site stats such as admins of each sites in multisite network
 * Version: 0.1
 * Author: Harshit Sanghvi
 * Author URI: http://about.me/harshit
 * License: GPL2
 */

define('ENS_PLUGIN_URL', plugin_dir_url( __FILE__ ));
//define('ENS_PLUGIN_URL', plugins_url( __FILE__ ).'/');
define('ENS_PLUGIN_DIR', dirname( __FILE__ ).'/');
// ENS Plugin Main table name
define('ENS_MAIN_TABLE', 'ens_main_hps');
// This is the slug used for the plugin's create site wizard page 
define('ENS_EXPORT_SLUG', 'Export_Network_Stats');
define('ENS_OPTIONS_PAGE_SLUG', 'Export_Network_Stats_Options');
define('ENS_CONFIG_OPTIONS_FOR_DATABASE', 'ens_config_options_hps');
define('ENS_VERSION', '0.1');


if(!class_exists('Export_Network_Stats')) {
	class Export_Network_Stats {
		
		/* All public variables that will be used for dynamic programming */	
		public $multiste;		
		
    	/*	Construct the plugin object	*/
		public function __construct() {

			// Installation and uninstallation hooks
			register_activation_hook(__FILE__, array( $this, 'ens_activate' ) );
			// Plugin Deactivation Hook
			register_deactivation_hook(__FILE__, array( $this, 'ens_deactivate' ) );
			
			// Plugin Uninstall hook - Used to call ens_uninstall function while plugin is being uninstalled
			register_uninstall_hook(__FILE__, array( $this, 'ens_uninstall' ) );

			/* Add action to display Export menu item in Site's Dashboard */
			// add_action( 'admin_menu', array($this, 'ens_menu'));
			/* Add action to display Export menu item in Network Admin's Dashboard */
			add_action( 'network_admin_menu', array( $this, 'ens_network_menu' ) );

			/* Add shortcode [site_setuo_wizard] to any page to display Site Setup Wizard over it */
			//add_shortcode('export_netwok_stats', array( $this, 'ens_shortcode' ) );
			
			/* Check and store is the wordpress installation is multisite or not */
			$this->multiste = is_multisite();

		}

		/*	Menu function to display Network Stats -> Export in Site's Dashboard	*/
		public function ens_menu() {
			/*	Adding Menu item "Export" in Dashboard, allowing it to be displayed for all users including 
				subscribers with "read" capability and displaying it with position "10.74"
			*/
			add_menu_page('Network Stats', 'Export', 'read', ENS_EXPORT_SLUG, 
				array( $this, 'ens_export' ), plugins_url('ens/images/icon.png'), '1.74');
		}

		/*	Menu function to display Site Network Stats -> Export in the Network Admin's Dashboard	*/
		public function ens_network_menu() {
			/*	Adding Menu item "Export" in Dashboard, allowing it to be displayed for all users including 
				subscribers with "read" capability and displaying it with position "10.74"
			*/
			add_menu_page('Network Stats', 'Network Stats', 'read', ENS_EXPORT_SLUG, 
				array($this, 'ens_export'), plugins_url('ens/images/icon.png'), '1.74');
			/* Adding First Sub menu item in the ENS Plugin to reflect the Create Site functionality in the sub menu */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Export', 'Export', 'read', ENS_EXPORT_SLUG, 
				array($this, 'ens_export') );
			/* Adding Options page in the Network Dashboard below the Create Site menu item */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Options', 'Options', 'manage_network', 
				ENS_OPTIONS_PAGE_SLUG, array($this, 'ens_options_page') );
		}

		/* ENS Shortcode function */
		public function ens_shortcode() {
			if( !is_user_logged_in()) {
				$login_url = site_url( 'wp-login.php?redirect_to='.urlencode( network_site_url( $_SERVER['REQUEST_URI'] ) ) ).'&action=shibboleth';
				echo sprintf( __( 'You must first <a href="%s">log in</a>, and then you can create a new site.' ), $login_url );
			}
			else {
				$this->ens_export();
			}
		}

		/* ENS Options Page which is displayed under the Network Dashboard -> Create Site -> Options Page */
		public function ens_options_page() {
			include(ENS_PLUGIN_DIR.'admin/ens_options_page.php');
		}

		/* Activate the plugin	*/  
		public function ens_activate() {
			//ToDo: Activate function
		} 

		/*	Deactivate the plugin	*/
		public function ens_deactivate() {
			//ToDo: Deactivate function
			
		} 
		/*	Unistall the plugin	*/ 
		public function ens_uninstall() {
			global $wpdb;

			if ( !defined('WP_UNINSTALL_PLUGIN') ) {
				header('Status: 403 Forbidden');
				header('HTTP/1.1 403 Forbidden');
				exit();
			}

			if ( !is_user_logged_in() ) {
				wp_die( 'You must be logged in to run this script.' );
			}

			if ( !current_user_can( 'install_plugins' ) ) {
				wp_die( 'You do not have permission to run this script.' );
			}
			
			$ens_main_table = $wpdb->base_prefix.ENS_MAIN_TABLE;
			// Drop ENS Main Table
			$wpdb->query( 'DROP TABLE IF EXISTS '.$ens_main_table );
			
		}

		/*	ENS Export function which is the main function	*/
		public function ens_export() {

			global $wpdb;
			$blogs = $wpdb->get_results($wpdb->prepare("
			    SELECT blog_id
			    FROM {$wpdb->blogs}
			    WHERE site_id = '{$wpdb->siteid}'
			    AND spam = '0'
			    AND deleted = '0'
			    AND archived = '0'
			"));

			echo $wpdb->prepare("
			    SELECT blog_id
			    FROM {$wpdb->blogs}
			    WHERE site_id = '{$wpdb->siteid}'
			    AND spam = '0'
			    AND deleted = '0'
			    AND archived = '0'
			    AND mature = '0' 
			    AND public = '1'
			");

			echo '<br /><br />';
			//$site_admins = '';
			$site_admins_list = '';
			foreach ($blogs as $blog) 
			{
			    switch_to_blog( $blog->blog_id );
			    $users_query = new WP_User_Query( array( 
			                'role' => 'administrator', 
			                'orderby' => 'display_name'
			                ) );
			   $results = $users_query->get_results();

			//http://codex.wordpress.org/Function_Reference/get_blog_details
			    $blog_details = get_blog_details($blog->blog_id);
			    //echo 'Blog '.$blog_details->blog_id.' is called '.$blog_details->blogname.'.';

			    $site_admins_list .= '<b>Blog ID: ' . $blog->blog_id . '</b><br />';
			    $site_admins_list .= 'Blog Name: ' . $blog_details->blogname . '<br />';
			    $site_admins_list .= 'Blog URL: ' . $blog_details->path . '<br />';
			        

			    foreach($results as $user)
			    {
			        $site_admins_list .= 'user_email: ' . $user->user_email . '<br />';
			        //$site_admins_list .= 'user_id: ' . $user->ID . '<br />';
			    }
			        $site_admins_list .= '<br />';

			    //$site_admins .= 'Blog ID: ' . $blog->blog_id . '<pre>' . print_r($results,true) . '</pre>';
			}
			restore_current_blog();

			echo $site_admins_list;

		}
	}
}

if(class_exists('Export_Network_Stats')) {
	// instantiate the plugin class
	$export_netwok_stats = new Export_Network_Stats();
}

?>