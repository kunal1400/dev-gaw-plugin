<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/kunal1400
 * @since      1.0.0
 *
 * @package    Dev_Gaw
 * @subpackage Dev_Gaw/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Dev_Gaw
 * @subpackage Dev_Gaw/includes
 * @author     Kunal Malviya <lucky.kunalmalviya@gmail.com>
 */
class Dev_Gaw_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
        $table_name = $wpdb->prefix.'user_design';
        $charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id INT AUTO_INCREMENT PRIMARY KEY,
			designName VARCHAR(255) NOT NULL,
			productId INT NOT NULL,
			variantId INT default 0,
			designedData LONGTEXT default NULL,
			userIp VARCHAR(255) default NULL,
			slug VARCHAR(255) default NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) $charset_collate;";
  
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

}
