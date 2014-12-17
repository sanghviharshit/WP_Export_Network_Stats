<?php
/**
 * Plugin Name: Export Network Stats
 * Plugin URI: http://github.io/sanghviharshit
 * Description: Exports various site stats such as admins of each sites in multisite network
 * Version: 1.1
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
// SSW Plugin Main table name
define('SSW_MAIN_TABLE', 'ssw_main_nsd');


if(!class_exists('Export_Network_Stats')) {
	class Export_Network_Stats {
		
		/* All public variables that will be used for dynamic programming */	
		public $multiste;		
		const use_transient = false;

    	/*	Construct the plugin object	*/
		public function __construct() {

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
				array( $this, 'ens_export' ), plugins_url('ens/images/icon.png'), '2.74');
			/* Adding First Sub menu item in the ENS Plugin to reflect the Create Site functionality in the sub menu */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Export', 'Export', 'read', ENS_EXPORT_SLUG, 
				array($this, 'ens_export') );
			/* Adding Options page in the Network Dashboard below the Create Site menu item */
			add_submenu_page(ENS_EXPORT_SLUG, 'Network Stats - Options', 'Options', 'manage_network', 
				ENS_OPTIONS_PAGE_SLUG, array($this, 'ens_options_page') );
		}

		/*	Menu function to display Site Network Stats -> Export in the Network Admin's Dashboard	*/
		public function ens_network_menu() {
			/*	Adding Menu item "Export" in Network Dashboard, allowing it to be displayed for all super admin users 
				with "manage_network" capability and displaying it with position "1.74"
			*/
			add_menu_page('Network Stats', 'Network Stats', 'manage_network', ENS_EXPORT_SLUG, 
				array($this, 'ens_export'), plugins_url('ens/images/icon.png'), '2.74');
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
			$tablename = SSW_MAIN_TABLE;
			$ssw_main_table = $wpdb->base_prefix.$tablename;
    		
			$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}'"));
			//TODO: Take care of spam, deleted and archived sites
			/*
			AND spam = '0'
			    AND deleted = '0'
			    AND archived = '0'
   			    AND mature = '0' 
			*/


			echo '<br /><br /><H1>Site Stats</H1><br/>';
			

			echo '
			<table border="1">
				<tr>
					<td>Blog ID</td>
					<td>Blog Name</td>
					<td>Blog URL</td>
					<td>Privacy</td>
					<td>Current Theme</td>
					<td>Admin Email</td>
					<td>Total Users</td>
					<td>Active Plugins</td>
					<td>Site Type</td>
				</tr>
			';

			// Get List of all plugins using get_plugins()
			if ( ! function_exists( 'is_plugin_active_for_network' ) || ! function_exists( 'get_plugins' ) ) {
		    	require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins=get_plugins();

			//$site_admins_list = '';

			foreach ($blogs as $blog) 
			{
			    switch_to_blog( $blog->blog_id );
			    $users_query = new WP_User_Query( array( 
			                'role' => 'administrator', 
			                'orderby' => 'display_name'
			                ) );
				//$results = $users_query->get_results();

				$result = count_users();


				//http://codex.wordpress.org/Function_Reference/get_blog_details
			    $blog_details = get_blog_details($blog->blog_id);
			    //echo 'Blog '.$blog_details->blog_id.' is called '.$blog_details->blogname.'.';

				/*	blog_public:
					1 : I would like my blog to be visible to everyone, including search engines (like Google, Sphere, Technorati) and archivers. (default)
					0 : I would like to block search engines, but allow normal visitors.
					-1: Visitors must have a login - anyone that is a registered user of Web Publishing @ NYU can gain access.
					-2: Only registered users of this blogs can have access - anyone found under Users > All Users can have access. 
			    	-3: Only administrators can visit - good for testing purposes before making it live. 
				*/

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

				 

			    echo '
			    	<tr>
			    		<td>' . $blog->blog_id . '</td>
			    		<td>' . $blog_details->blogname .'</td>
			    		<td>' . $blog_details->path .'</td>
			    		<td>' . $option_privacy .'</td>
			    		<td>' . $option_theme .'</td>
			    		<td>' . $option_admin_email .'</td>
			    		<td>' . $result['total_users'] .'</td>
			    		<td>' . $count_active_plugins . '</td>
			    		<td>' . $site_type . '</td>
			    	</tr>
			    ';

			    // To print list of admins in each row.
			    /*
			    foreach($results as $user)
			    {
			    	$site_admins_list .= $blog->blog_id . ',';
			    	$site_admins_list .= $blog_details->blogname . ',';
			    	$site_admins_list .= $blog_details->path . ',';
					$site_admins_list .= $option_privacy . ',';
					$site_admins_list .= $option_theme . ',';
			        $site_admins_list .= $user->user_email . '<br />';
			    }
				*/
			    
			}

			restore_current_blog();

			echo '
			</table>';

			echo '<br /><br /><br /> 
					<strong>Privacy: </strong><br />
					1 : I would like my blog to be visible to everyone, including search engines (like Google, Sphere, Technorati) and archivers. (default) <br />
					0 : I would like to block search engines, but allow normal visitors. <br />
					-1: Visitors must have a login - anyone that is a registered user of Web Publishing @ NYU can gain access. <br />
					-2: Only registered users of this blogs can have access - anyone found under Users > All Users can have access. <br />
			    	-3: Only administrators can visit - good for testing purposes before making it live. <br />
			    ';

			// Print Plugin Stats
			self::print_plugin_data();

		}

		public function print_plugin_data() {

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


			echo '<br/><br/><H1>Plugin Stats</H1><br/>';
			
			echo '
				<table border="1">
				<tr>
					<td>Plugin</td>
					<td>Number of Sites</td>
				</tr>';

    		
			foreach( $all_plugins as $plugin_file => $plugin_data ) {
        		$active_on_network = is_plugin_active_for_network( $plugin_file );
				echo '
					<tr>
						<td>' . $plugin_data[ 'Title' ] . '</td>';


				if ( $active_on_network ) {
				    // We don't need to check any further for network active plugins
				    echo '<td>Network Activated</td>
				    	</tr>';

				} 
				else {
		            // Is this plugin Active on any blogs in this network?
		            $active_on_blogs = self::is_plugin_active_on_blogs( $plugin_file );
		            if ( is_array( $active_on_blogs ) ) {
		            	$count_blogs_plugin_is_active = count($active_on_blogs);
		            	echo '<td>'.$count_blogs_plugin_is_active.'</td>
		            		</tr>';

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
			}

			echo '</table>';
			
		}


	    /* Helper Functions ***********************************************************/

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