<?php
/*
Plugin Name: WP Visitors Counter
Plugin URI: https://github.com/sofyansitorus/WP-Visitors-Counter
Description: WordPress Visitors Counter Plugin
Version: 1.0.0
Author: Sofyan Sitorus
Author Email: sofyansitorus@gmail.com
Author URI: https://github.com/sofyansitorus
*/

define('WPVC_TBL_NAME', 'visitors_counter');
define('WPVC_PATH', plugin_dir_path( __FILE__ ));
define('WPVC_URL', plugin_dir_url( __FILE__ ));

require_once( WPVC_PATH . '/widget/widget.php' );

class WPVisitorsCounter {

  private $wpdb;
  private $table_name;
  
  /**
   * Constructor
   */
  function __construct() {

    global $wpdb;

    $this->wpdb = &$wpdb;
    $this->table_name = $this->wpdb->prefix . WPVC_TBL_NAME;

		//register an activation hook for the plugin
		register_activation_hook( __FILE__, array( &$this, 'install_wp_visitors_counter' ) );

		//Hook up to the init action
		add_action( 'init', array( &$this, 'init_wp_visitors_counter' ) );
		
		//Hook up to the init action
		add_action( 'widgets_init', array( &$this, 'widgets_init_wp_visitors_counter' ) );

		//Hook up to the init action
		add_action( 'template_redirect', array( &$this, 'count_visitor' ) );
  }
  
  /**
   * Runs when the plugin is activated
   */  
  function install_wp_visitors_counter() {
  	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

  	$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  ip_address varchar(55) DEFAULT '' NOT NULL,
		  first_visit datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  last_visit datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  hits INT DEFAULT '1' NOT NULL,
		  UNIQUE KEY id (id)
		) $charset_collate;";
		dbDelta( $sql );

  }
  
  /**
   * Runs when the plugin is initialized
   */
  function init_wp_visitors_counter() {
	// Setup localization
	load_plugin_textdomain( 'wpvc_td', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );


		/*
		 * TODO: Define custom functionality for your plugin here
		 *
		 * For more information: 
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		//add_action( 'your_action_here', array( &$this, 'action_callback_method_name' ) );
		//add_filter( 'your_filter_here', array( &$this, 'filter_callback_method_name' ) );    
  }

  /**
   * Runs when the widget is initialized
   */
  function widgets_init_wp_visitors_counter() {
		register_widget('WPVC_Widget');
  }

  function count_visitor(){
  	$visitor_ip = $this->get_ip();
  	$current_time_mysql = current_time('mysql');

  	$sql = "SELECT * FROM $this->table_name vc WHERE vc.ip_address = %s";
  	$result = $this->wpdb->get_row(
  		$this->wpdb->prepare(
  			$sql,
  			$visitor_ip
  		)
  	);

  	if(!$result){
			$this->wpdb->insert( 
         $this->table_name, 
         array( 
           'ip_address' => $visitor_ip, 
           'first_visit' => $current_time_mysql, 
           'last_visit' => $current_time_mysql
         ), 
         array( 
           '%s', 
           '%s', 
           '%s'
         ) 
       );
  	}else{
  		$this->wpdb->update( 
         $this->table_name,
         array( 
           'hits' => $result->hits + 1,
           'last_visit' => $current_time_mysql
         ), 
         array(
           'id' => $result->id
         ), 
         array( 
           '%d',
           '%s'
         ), 
         array( 
           '%d'
         )
       );
  	}
  }

  private function get_ip() {

		//Just get the headers if we can or else use the SERVER global
		if ( function_exists( 'apache_request_headers' ) ) {

			$headers = apache_request_headers();

		} else {

			$headers = $_SERVER;

		}

		//Get the forwarded IP if it exists
		if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

			$the_ip = $headers['X-Forwarded-For'];

		} elseif ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )
		) {

			$the_ip = $headers['HTTP_X_FORWARDED_FOR'];

		} else {
			
			$the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );

		}

		if(!$the_ip){
			$the_ip = $_SERVER['REMOTE_ADDR'];
		}

		return $the_ip;

	}
  
} // end class
new WPVisitorsCounter();