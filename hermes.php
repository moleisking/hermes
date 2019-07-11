<?php
 /*
 * Plugin Name: Hermes
 * Plugin URI: https://www.espeaky.com
 * Description: A wordpress plugin that adds the ability to send messages to users by user id. This enables the page to hide the user contact details on the client side. This plugin requires a configured SMTP server to send messages of type email, Skype, WeChat and WhatsApp privided the client has these applications setup.
 * Author: Scott Johnston
 * Author URI: https://www.linkedin.com/in/scott8johnston/
 * Version: 1.0.0
 * License: GPLv2 or later
 */

 /**
  * @author Scott Johnston
  * @license https://www.gnu.org/licenses/gpl-3.0.html
  * @package Hermes
  * @version 1.0.0
 */

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class Hermes{	

	public function __construct(){		
		register_activation_hook(__FILE__, array($this,'plugin_activate')); 
		register_deactivation_hook(__FILE__, array($this,'plugin_deactivate')); 				
	}		

	public function plugin_activate(){
		flush_rewrite_rules();	
		Hermes::create_table();			
	}

	public function plugin_deactivate(){
		flush_rewrite_rules();
		//Hermes::delete_table();		
	}

	private static function create_table(){		
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		
		//create messages table
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$createMessages = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."messages (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,	
			text varchar(255) NOT NULL,		
			type varchar(5) NOT NULL,
			state varchar(5) NOT NULL,		
			senderId bigint NOT NULL,	
			receiverId bigint NOT NULL,		
			ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		  ) ".$charset_collate.";";
		dbDelta($createMessages);	

		//create relations table
		$createRelationships = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."relationships (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,				
			type varchar(10) NOT NULL,		
			senderId bigint NOT NULL,	
			receiverId bigint NOT NULL,		
			ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		  ) ".$charset_collate.";";
		dbDelta($createRelationships);	
	}	
	
	/*private static function delete_table(){	
		global $wpdb;		
		$delete = "DROP TABLE IF EXISTS ".$wpdb->base_prefix."messages;";
		$wpdb->query($delete );	
		
		$delete = "DROP TABLE IF EXISTS ".$wpdb->base_prefix."relationships;";
		$wpdb->query($delete );	
	}*/
}

include(plugin_dir_path(__FILE__) . 'hermes-api.php');

include(plugin_dir_path(__FILE__) . 'hermes-shortcode.php');

include(plugin_dir_path(__FILE__) . 'hermes-widget.php');

include(plugin_dir_path(__FILE__) . 'hermes-admin.php');

$hermes = new Hermes;
?>