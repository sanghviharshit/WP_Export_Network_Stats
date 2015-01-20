<?php
/**
 * Plugin Name: Export Network Stats
 * Plugin URI: http://github.io/sanghviharshit
 * Description: Exports various site stats such as admins of each sites in multisite network
 * Version: 1.3
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
define('ENS_EXPORT_SITES_SLUG', 'Export_Network_Sites_Stats');
define('ENS_EXPORT_PLUGINS_SLUG', 'Export_Network_Plugins_Stats');
define('ENS_EXPORT_CSV_SLUG', 'Export_Stats_CSV');
define('ENS_OPTIONS_PAGE_SLUG', 'Export_Network_Stats_Options');
define('ENS_CONFIG_OPTIONS_FOR_DATABASE', 'ens_config_options_hps');
define('ENS_VERSION', '0.1');
// SSW Plugin Main table name
define('ENS_MAIN_TABLE', 'ens_main_hps');
define('SSW_PLUGIN_DIR', 'nsd_ssw/ssw.php');
define('MSP_PLUGIN_DIR', 'sitewide-privacy-options/sitewide-privacy-options.php');


include 'ens_charts_js.php';


if(!class_exists('Export_Network_Stats')) {
	class Export_Network_Stats {
		
		/* All public variables that will be used for dynamic programming */	
		public $multiste;		
		const use_transient = false;

    	/*	Construct the plugin object	*/
		public function __construct() {

			if(isset($_GET['export_data']))
			{
				$this->ens_export_data($_GET['export_data']);
				exit();
			}


			// Installation and uninstallation hooks
			register_activation_hook(__FILE__, array( $this, 'ens_activate' ) );
			// Plugin Deactivation Hook
			register_deactivation_hook(__FILE__, array( $this, 'ens_deactivate' ) );
			
			// Plugin Uninstall hook - Used to call ens_uninstall function while plugin is being uninstalled
			register_uninstall_hook(__FILE__, array( $this, 'ens_uninstall' ) );

			/* Add action to display Export menu item in Site's Dashboard */
			add_action( 'admin_menu', array($this, 'ens_menu'));
			/* Add action to display Export menu item in Network Admin's Dashboard */
			add_action( 'network_admin_menu', array( $this, 'ens_network_menu' ) );


			add_action( 'admin_enqueue_scripts', 'ens_charts_load_scripts' );
			add_action( 'admin_head', 'ens_charts_html5_support');

			/* Add shortcode [site_setuo_wizard] to any page to display Site Setup Wizard over it */
			//add_shortcode('export_netwok_stats', array( $this, 'ens_shortcode' ) );
			
			/* Check and store is the wordpress installation is multisite or not */
			$this->multiste = is_multisite();


		}

		/*	Menu function to display Network Stats -> Export in Site's Dashboard	*/
		public function ens_menu() {
			/*	Adding Menu item "Export" in Dashboard, allowing it to be displayed for all super admin users
				with "manage_network" capability and displaying it with position "1.74"
			*/
			add_menu_page('Network Stats', 'Network Stats', 'manage_network', ENS_EXPORT_SLUG, 
				array( $this, 'ens_main' ), plugins_url('ens/images/icon.png'), '2.74');
			/* Adding First Sub menu item in the ENS Plugin to show site stats in the sub menu */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Site Stats', 'Site Stats', 'manage_network', ENS_EXPORT_SITES_SLUG, 
				array($this, 'ens_print_site_stats') );
			/* Adding First Sub menu item in the ENS Plugin to show plugin stats in the sub menu */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Plugin Stats', 'Plugin Stats', 'manage_network', ENS_EXPORT_PLUGINS_SLUG, 
				array($this, 'ens_print_plugin_stats') );
			/* Adding First Sub menu item in the ENS Plugin to show plugin stats in the sub menu */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Export Stats', 'Export Stats', 'manage_network', ENS_EXPORT_CSV_SLUG, 
				'ens_charts_shortcode' );
			
			/* Adding Options page in the Network Dashboard */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Options', 'Options', 'manage_network', 
				ENS_OPTIONS_PAGE_SLUG, array($this, 'ens_options_page') );
		}

		/*	Menu function to display Site Network Stats -> Export in the Network Admin's Dashboard	*/
		public function ens_network_menu() {
			/*	Adding Menu item "Export" in Network Dashboard, allowing it to be displayed for all super admin users 
				with "manage_network" capability and displaying it with position "1.74"
			*/
			add_menu_page('Network Stats', 'Network Stats', 'manage_network', ENS_EXPORT_SLUG, 
				array( $this, 'ens_main' ), plugins_url('ens/images/icon.png'), '2.74');
			/* Adding First Sub menu item in the ENS Plugin to show site stats in the sub menu */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Site Stats', 'Site Stats', 'manage_network', ENS_EXPORT_SITES_SLUG, 
				array($this, 'ens_print_site_stats') );
			/* Adding First Sub menu item in the ENS Plugin to show plugin stats in the sub menu */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Plugin Stats', 'Plugin Stats', 'manage_network', ENS_EXPORT_PLUGINS_SLUG, 
				array($this, 'ens_print_plugin_stats') );
			/* Adding First Sub menu item in the ENS Plugin to show plugin stats in the sub menu */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Export Stats', 'Export Stats', 'manage_network', ENS_EXPORT_CSV_SLUG, 
				'ens_charts_shortcode' );
			
			/* Adding Options page in the Network Dashboard */
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
				$this->ens_site_data();
			}
		}

		/* ENS Options Page which is displayed under the Network Dashboard -> Create Site -> Options Page */
		public function ens_options_page() {
			include(ENS_PLUGIN_DIR.'admin/ens_options_page.php');
		}

		/* Activate the plugin	*/  
		public function ens_activate() {
			//include(SSW_PLUGIN_DIR.'admin/ssw_activate.php');
			
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
			
			// Drop ENS Main Table
			$wpdb->query( 'DROP TABLE IF EXISTS '.$ens_main_table );
			
		}


		/* Store ENS Plugin's main table name in variable based on multisite */
		public function ens_main_table( $tablename = ENS_MAIN_TABLE ) {
			global $wpdb;
			$ssw_main_table = $wpdb->base_prefix.$tablename;
			return $ssw_main_table;
		} 



		public function ens_main() {
			echo '<h1>Export Network Stats</h1><br/>';
			//echo '<br/><h3>TODO: This page will include options to export stats into CSV file</h3><br/>';

			//TODO: Securing the export function
			echo '<form method="post" action="'. plugins_url('admin/ens_export.php', __FILE__) . ' ">';
			echo '	<input type="hidden" id="export_data" name="export_data" value="site_data">';
			echo '	<input type="submit" value="Export Site Data">';
			echo '</form>';

			echo '<h3><a href="'.site_url().'/wp-admin/network/admin.php?page='.ENS_EXPORT_SLUG.'&export_data=site_data">Export Site Data</a></h3>';
			
			echo '<h3><a href="'.site_url().'/wp-admin/network/admin.php?page='.ENS_EXPORT_SLUG.'&export_data=plugin_data">Export Plugin Data</a></h3>';
				
		}

		/*	ENS Export Site Stats	*/
		public function ens_site_data() {

			global $wpdb;
			$ens_main_table = $this->ens_main_table();
    		
			$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = %d",$wpdb->siteid));
			//TODO: Take care of spam, deleted and archived sites
			/*
			AND spam = '0'
			    AND deleted = '0'
			    AND archived = '0'
   			    AND mature = '0' 
			*/


   			$ens_site_data = array();
   			$ens_site_row = array();

   			/* To add Table headers
   			$ens_site_row = array("Blog ID", "Blog Name", "Blog URL", "Privacy", "Current Theme", "Admin Email", "Total Users", "Active Plugins", "Site Type");
   			$ens_site_data[] = $ens_site_row;
			*/
			// Get List of all plugins using get_plugins()
			if ( ! function_exists( 'is_plugin_active_for_network' ) || ! function_exists( 'get_plugins' ) ) {
		    	require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins=get_plugins();

			//$site_admins_list = '';

			foreach ($blogs as $blog) 
			{
			    switch_to_blog( $blog->blog_id );
				$result = count_users();

				//http://codex.wordpress.org/Function_Reference/get_blog_details
			    $blog_details = get_blog_details($blog->blog_id);


			    $option_privacy = get_option( 'blog_public', '');
			    $option_theme = get_option( 'template', '');
			    $option_admin_email = get_option( 'admin_email', '');


				//$blogs = $wpdb->get_results($wpdb->prepare("SELECT  FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}'"));
			
				$apl=get_option('active_plugins');
				
				$activated_plugins=array();
				foreach ($apl as $p){           
				    if(isset($plugins[$p])){
				         array_push($activated_plugins, $plugins[$p]);
				    }           
				}

				$count_active_plugins = count ($activated_plugins);


			    $site_type = $wpdb->get_var( 
        			'SELECT site_usage FROM '.$ssw_main_table.' WHERE blog_id = '.$blog->blog_id
    				);


			    if ( $this -> is_plugin_network_activated(SSW_PLUGIN_DIR)) {
					$ens_site_row = array(
		   				'blog_id' => $blog->blog_id, 
		   				'blog_name' => $blog_details->blogname, 
		   				'blog_url' => $blog_details->path, 
		   				'privacy' => $option_privacy, 
		   				'current_theme' => $option_theme, 
		   				'admin_email' => $option_admin_email, 
		   				'total_users' => $result['total_users'], 
		   				'active_plugins' => $count_active_plugins, 
		   				'site_type' => $site_type
	   				);
				}
				else {
					$ens_site_row = array(
		   				'blog_id' => $blog->blog_id, 
		   				'blog_name' => $blog_details->blogname, 
		   				'blog_url' => $blog_details->path, 
		   				'privacy' => $option_privacy, 
		   				'current_theme' => $option_theme, 
		   				'admin_email' => $option_admin_email, 
		   				'total_users' => $result['total_users'], 
		   				'site_type' => $count_active_plugins, 
		   			);
				}
			
	   			

	   			$ens_site_data[] = $ens_site_row;

			    
			}

			restore_current_blog();


			$wpdb->query( 'TRUNCATE table ' . $ens_main_table );
			echo '<br/>insert data in '. $ens_main_table . '<br />';
			foreach ($ens_site_data as $site_data) {
				$wpdb->insert( $ens_main_table, $site_data);
			}


			return $ens_site_data;
		}

		/* Export Plugin Data */
		public function ens_plugin_data() {

			// Check if get_plugins() function exists. This is required on the front end of the
			// site, since it is in a file that is normally only loaded in the admin.
			
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			

			// Is this plugin network activated
			if ( ! function_exists( 'is_plugin_active_for_network' ) || ! function_exists( 'get_plugins' ) ) {
			    require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			
			$all_plugins = get_plugins();

   			$ens_plugin_data = array();
			$ens_plugin_row = array();
   			
   			/*
   			$ens_plugin_row = array("Plugin", "Number of Sites");
   			$ens_plugin_data[] = $ens_plugin_row;
			*/
    		
			foreach( $all_plugins as $plugin_file => $plugin_data ) {
        		$active_on_network = is_plugin_active_for_network( $plugin_file );
				
				if ( $active_on_network ) {
				    // We don't need to check any further for network active plugins
				    $ens_plugin_row = array($plugin_data[ 'Title' ],"Network Activated");
				} 
				else {
		            // Is this plugin Active on any blogs in this network?
		            $active_on_blogs = self::is_plugin_active_on_blogs( $plugin_file );
		            if ( is_array( $active_on_blogs ) ) {
		            	$count_blogs_plugin_is_active = count($active_on_blogs);
		            	$ens_plugin_row = array($plugin_data[ 'Title' ],$count_blogs_plugin_is_active);
		            	
/*
						// To List all the sites the plugin is active on.
		                // Loop through the blog list, gather details and append them to the output string
		                foreach ( $active_on_blogs as $blog_id ) {
		                    $blog_id = trim( $blog_id );
		                    if ( ! isset( $blog_id ) || $blog_id == '' ) {
		                        continue;
		                    }

		                    $blog_details = get_blog_details( $blog_id, true );

		                    if ( isset( $blog_details->siteurl ) && isset( $blog_details->blogname ) ) {
		                        $blog_url  = $blog_details->siteurl;
		                        $blog_name = $blog_details->blogname;

		                        //$output .= '<li><nobr><a title="' . esc_attr( __( 'Manage plugins on ', 'npa' ) . $blog_name ) .'" href="'.esc_url( $blog_url ).'/wp-admin/plugins.php">' . esc_html( $blog_name ) . '</a></nobr></li>';
		                    }

		                    unset( $blog_details );
		                }
*/
		            }
				}

				$ens_plugin_data[] = $ens_plugin_row;
			
			}


			return $ens_plugin_data;
		}

		
		function ens_print_site_stats() {
			global $wpdb;
			$ens_main_table = $this->ens_main_table();

			$ens_site_data_in_db = $wpdb->get_results( 
				"
				SELECT * 
				FROM " . $ens_main_table
			);


			if ( $ens_site_data_in_db )
			{
				foreach ( $ens_site_data_in_db as $ens_site_row )
				{
		   			$ens_site_data[] = $ens_site_row;
		   		}
		   	}

		   	else {
				echo 'There is no data to display';
				exit();
			}

			echo '<H1>Site Stats</H1><br/>';			

			echo '
				<table border="1">
				';

			echo '
				<tr>
					<td>Blog ID</td>
					<td>Blog Name</td>
					<td>Blog URL</td>
					<td>Privacy</td>
					<td>Current Theme</td>
					<td>Admin Email</td>
					<td>Total Users</td>
					<td>Active Plugins</td>';
			
			if ( $this -> is_plugin_network_activated(SSW_PLUGIN_DIR)) {
				echo '
					<td>Site Type</td>';
			}

			echo '
				</tr>
				';
		
			foreach ($ens_site_data as $site_data) {
				echo '<tr>';
		    	foreach ($site_data as $site_data_field) {
		    		echo '<td>' . $site_data_field . '</td>';
		    	}
			    echo '</tr>';
			}

			echo '
			</table>';


			if ($this->is_plugin_network_activated(MSP_PLUGIN_DIR)) {

				echo '<br /><br /><br /> 
						<strong>Privacy: </strong><br />
						1 : I would like my blog to be visible to everyone, including search engines (like Google, Sphere, Technorati) and archivers. (default) <br />
						0 : I would like to block search engines, but allow normal visitors. <br />
						-1: Visitors must have a login - anyone that is a registered user of Web Publishing @ NYU can gain access. <br />
						-2: Only registered users of this blogs can have access - anyone found under Users > All Users can have access. <br />
				    	-3: Only administrators can visit - good for testing purposes before making it live. <br />
				    ';
			} 
			else {

				echo '<br /><br /><br /> 
						<strong>Privacy: </strong><br />
						1 : I would like my blog to be visible to everyone, including search engines (like Google, Sphere, Technorati) and archivers. (default) <br />
						0 : I would like to block search engines, but allow normal visitors. <br />
				    ';	
			}		
			

			$ens_current_theme = $wpdb->get_results( 
				"
				SELECT current_theme, count(*) As Number_Sites 
				FROM " . $ens_main_table . " GROUP by current_theme", ARRAY_A
			);


			echo 'SELECT current_theme, count(*)  
				FROM ' . $ens_main_table . ' GROUP by current_theme';

			echo "<br />";

			$chart_labels = array();
			$chart_data = array();

			//print_r($ens_current_theme);

			if ( $ens_current_theme )
			{
				foreach ( $ens_current_theme as $ens_current_theme_row )
				{
					$chart_labels[] = $ens_current_theme_row['current_theme'];
					$chart_data[] = $ens_current_theme_row['Number_Sites'];
				}
		   	}



		   	//print_r($chart_data);
		   	//echo implode(",",$chart_data);
		   	echo "<table>
		   			<tr>
		   			";
		   	$atts = array('title' => "Theme Distribution", 'data' => implode(",",$chart_data), 'labels' => implode(",",$chart_labels));
		   	ens_charts_shortcode($atts);

		   	echo "</tr>
		   			<tr>";
		   	$atts = array('title' => "Data Distribution", 'data' => "100,200,300", 'labels' => "a,b,c");
		   	ens_charts_shortcode($atts);

		   	echo "</tr>
		   			</table>";

		}

		function ens_print_plugin_stats() {

			$transient = 'update_plugins';
			$update_plugins_list = get_transient( $transient );

			var_dump($update_plugins_list);


			$ens_plugin_data = $this->ens_plugin_data();
			echo '<H1>Plugin Stats</H1><br/>';

			echo '
				<table border="1">
				';

			echo '
				<tr>
					<td>Plugin</td>
					<td>Number of Sites</td>
				</tr>';

			foreach ($ens_plugin_data as $plugin_data) {
				echo '<tr>';
		    	foreach ($plugin_data as $plugin_data_field) {
		    		echo '<td>' . $plugin_data_field . '</td>';
		    	}
			    echo '</tr>';
			}

			echo '
			</table>';

			

		}

	    /* Helper Functions ***********************************************************/


	    function ens_export_data($data = "site_data") {

	    	$delimiter=",";
	    	$filename = $data . '.csv';

/*
			if ( !current_user_can( 'manage_network' ) ) {
				wp_die( 'You do not have permission to run this script.' );
			}
*/
			if ($data == 'site_data') {
				$ens_export_data = $this->ens_site_data();
			}
			elseif ($data == 'plugin_data') {
				$ens_export_data = $this->ens_plugin_data();
			}
			else {
				echo $data;
				echo 'You Lost? Huh?';
				exit();
			}

			header('Content-Type: application/csv');
			header('Content-Disposition: attachement; filename="'.$filename.'";');

			// open the "output" stream
			// see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
			$f = fopen('php://output', 'w');

			foreach ($ens_export_data as $line) {
				fputcsv($f, $line, $delimiter);
			}
			fclose($f);
		}   


		function is_plugin_network_activated($plugin) {
		
			return is_plugin_active_for_network($plugin);
		}


	    // Get the database prefix
	    static function get_blog_prefix( $blog_id=null ) {
	        global $wpdb;

	        if ( null === $blog_id ) {
	            $blog_id = $wpdb->blogid;
	        }
	        $blog_id = (int) $blog_id;

	        if ( defined( 'MULTISITE' ) && ( 0 == $blog_id || 1 == $blog_id ) ) {
	            return $wpdb->base_prefix;
	        } else {
	            return $wpdb->base_prefix . $blog_id . '_';
	        }
	    }

	    // Get the list of blogs
	    static function get_network_blog_list( ) {
	        global $wpdb;
	        $blog_list = array();

	        $args = array(
	            'limit'  => 10000 // use the wp_is_large_network upper limit
	        );

	        if ( function_exists( 'wp_get_sites' ) && function_exists( 'wp_is_large_network' ) ) {
	            // If wp_is_large_network() returns TRUE, wp_get_sites() will return an empty array.
	            // By default wp_is_large_network() returns TRUE if there are 10,000 or more sites or users in your network.
	            // This can be filtered using the wp_is_large_network filter.
	            if ( ! wp_is_large_network( 'sites' ) ) {
	                $blog_list = wp_get_sites( $args );
	            }

	        } else {
	            // Fetch the list from the transient cache if available
	            $blog_list = get_site_transient( 'auditor_blog_list' );
	            if ( self::use_transient !== true || $blog_list === false ) {
	                $blog_list = $wpdb->get_results( "SELECT blog_id, domain FROM " . $wpdb->base_prefix . "blogs", ARRAY_A );

	                // Store for one hour
	                set_transient( 'auditor_blog_list', $blog_list, 3600 );
	            }
	        }

	        //error_log( print_r( $blog_list, true ) );
	        return $blog_list;
	    }

	    /* Plugin Helpers */

	    // Determine if the given plugin is active on a list of blogs
	    static function is_plugin_active_on_blogs( $plugin_file ) {
	        // Get the list of blogs
	        $blog_list = self::get_network_blog_list( );

	        if ( isset( $blog_list ) && $blog_list != false ) {
	            // Fetch the list from the transient cache if available
	            $auditor_active_plugins = get_site_transient( 'auditor_active_plugins' );
	            if ( ! is_array( $auditor_active_plugins ) ) {
	                $auditor_active_plugins = array();
	            }
	            $transient_name = self::get_transient_friendly_name( $plugin_file );

	            if ( self::use_transient !== true || ! array_key_exists( $transient_name, $auditor_active_plugins ) ) {
	                // We're either not using or don't have the transient index
	                $active_on = array();

	                // Gather the list of blogs this plugin is active on
	                foreach ( $blog_list as $blog ) {
	                    // If the plugin is active here then add it to the list
	                    if ( self::is_plugin_active( $blog['blog_id'], $plugin_file ) ) {
	                        array_push( $active_on, $blog['blog_id'] );
	                    }
	                }

	                // Store our list of blogs
	                $auditor_active_plugins[$transient_name] = $active_on;

	                // Store for one hour
	                set_site_transient( 'auditor_active_plugins', $auditor_active_plugins, 3600 );

	                return $active_on;

	            } else {
	                // The transient index is available, return it.
	                $active_on = $auditor_active_plugins[$transient_name];

	                return $active_on;
	            }
	        }

	        return false;
	    }

	    // Given a blog id and plugin path, determine if that plugin is active.
	    static function is_plugin_active( $blog_id, $plugin_file ) {
	        // Get the active plugins for this blog_id
	        $plugins_active_here = self::get_active_plugins( $blog_id );

	        // Is this plugin listed in the active blogs?
	        if ( isset( $plugins_active_here ) && strpos( $plugins_active_here, $plugin_file ) > 0 ) {
	            return true;
	        } else {
	            return false;
	        }
	    }

	    // Get the list of active plugins for a single blog
	    static function get_active_plugins( $blog_id ) {
	        global $wpdb;

	        $blog_prefix = self::get_blog_prefix( $blog_id );

	        $active_plugins = $wpdb->get_var( "SELECT option_value FROM " . $blog_prefix . "options WHERE option_name = 'active_plugins'" );

	        return $active_plugins;
	    }


		static function get_transient_friendly_name( $file_name ) {
		    $transient_name = substr( $file_name, 0, strpos( $file_name, '/' ) );
		    if ( $transient_name == false ) {
		        $transient_name = $file_name;
		    }
		    if ( strlen( $transient_name ) >= 45 ) {
		        $transient_name = substr( $transient_name, 0, 44 );
		    }
		    return esc_sql( $transient_name );
		}

		function clear_plugin_transient( $plugin, $network_deactivating ) {
		    delete_site_transient( 'auditor_active_plugins' );
		    return;
		}

	}
}


if(class_exists('Export_Network_Stats')) {
	// instantiate the plugin class
	$export_netwok_stats = new Export_Network_Stats();

}

?>