<?php
 /**
  * @package Hermes
  * @version 1.0.0
 */

 /*
 * This program is free software: you can redistribute it and/or modify  
 * it under the terms of the GNU General Public License as published by  
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU 
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License 
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
$option_name = 'hermes-config-group';
 
delete_option($option_name);
 
// for site options in Multisite
delete_site_option($option_name);
 
// Drop the custom database table
global $wpdb;		
$delete = "DROP TABLE IF EXISTS ".$wpdb->base_prefix."messages;";
$wpdb->query($delete );	
		
$delete = "DROP TABLE IF EXISTS ".$wpdb->base_prefix."relationships;";
$wpdb->query($delete );	
?>