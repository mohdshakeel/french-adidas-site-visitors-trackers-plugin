<?php

/*
 * Plugin Name: Visitors Tracker
 * Version: 2.17
 *
 * Plugin URI: http://www.wpdeft.com/
 * Description: Record and visualize the path of your customers
 * Author: Mark Stanley (wpdeft)
 * Author URI: http://www.wpdeft.com/
 * Requires at least: 3.8
 * Tested up to: 4.9.6
 *
 * @package WordPress
 * @author Mark Stanley (wpdeft)
 * @since 1.0.0
 */


if (!defined('ABSPATH'))
    exit;

register_activation_hook(__FILE__, 'vstr_install');
register_uninstall_hook(__FILE__, 'vstr_uninstall');

global $jal_db_version;
$jal_db_version = "1.0";
require_once ('includes/VsTr_Core.php');
require_once ('includes/VsTr_admin_menu.php');
require_once ('includes/VsTr_VisitsTable.php');

function VisitorsTracker() {
    $version = 2.17;
    //vstr_checkDBUpdates($version);
    $instance = VsTr_Core::instance(__FILE__, $version);
    if (is_null($instance->menu)) {
        $instance->menu = VsTr_admin_menu::instance($instance);
    }

    return $instance;
}

/**
 * Installation. Runs on activation.
 * @access  public
 * @since   1.0.0
 * @return  void
 */
function vstr_install() {
    global $wpdb;
    global $jal_db_version;
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

    // create settings table
    $db_table_name = $wpdb->prefix . "vstr_settings";
    if ($wpdb->get_var("SHOW TABLES LIKE '$db_table_name'") != $db_table_name) {
        if (!empty($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if (!empty($wpdb->collate))
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql = "CREATE TABLE $db_table_name (
		id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                customersTarget VARCHAR(64) NOT NULL,
                removeDays SMALLINT(5) NOT NULL,
                panelPosition VARCHAR(5) NOT NULL,
                filterIP TEXT NOT NULL,
                purchaseCode VARCHAR(250) NOT NULL,
                updated BOOL NOT NULL,
                noBots BOOL NOT NULL,
                dontTrackAdmins BOOL NOT NULL,
                dontTrackTactile BOOL NOT NULL,
		UNIQUE KEY id (id)
		) $charset_collate;";
        dbDelta($sql);
        // insert default settings
        $rows_affected = $wpdb->insert($db_table_name, array('id' => 1, 'customersTarget' => 'all', 'removeDays' => 3));
    }

    // create visits table
    $db_table_name = $wpdb->prefix . "vstr_visits";
    if ($wpdb->get_var("SHOW TABLES LIKE '$db_table_name'") != $db_table_name) {
        if (!empty($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if (!empty($wpdb->collate))
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql = "CREATE TABLE $db_table_name (
		id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                customerID MEDIUMINT(9) NOT NULL,
                date DATETIME NOT NULL,
                screenWidth SMALLINT(5) NOT NULL,
                screenHeight SMALLINT(5) NOT NULL,
                timePast MEDIUMINT(9) NOT NULL,
                userID MEDIUMINT(9) NOT NULL,
                ip VARCHAR(128) NOT NULL,
                browser VARCHAR(128) NOT NULL,
                country VARCHAR(250) NOT NULL,
                city VARCHAR(250) NOT NULL,
		UNIQUE KEY id (id)
		) $charset_collate;";
        dbDelta($sql);
    }

    // create step table
    $db_table_name = $wpdb->prefix . "vstr_steps";
    if ($wpdb->get_var("SHOW TABLES LIKE '$db_table_name'") != $db_table_name) {
        if (!empty($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if (!empty($wpdb->collate))
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql = "CREATE TABLE $db_table_name (
		id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                date DATETIME NOT NULL,
                visitID MEDIUMINT(9) NOT NULL,
                type VARCHAR(32) NOT NULL,
                page VARCHAR(250) NOT NULL,
                page_name VARCHAR(250) NOT NULL,
                domElement TEXT NOT NULL,
                value VARCHAR(32) NOT NULL,
		UNIQUE KEY id (id)
		) $charset_collate;";
        dbDelta($sql);
    }

    add_option("jal_db_version", $jal_db_version);
}


/**
 * Update database
 * @access  public
 * @since   2.0
 * @return  void
 */
function vstr_checkDBUpdates($version) {
    global $wpdb;
    $installed_ver = get_option("vstr_version");
    require_once(ABSPATH . '/wp-admin/includes/upgrade.php');

    if (!$installed_ver || $installed_ver < 2.10) {
        $table_name = $wpdb->prefix . "vstr_settings";
        $sql = "ALTER TABLE " . $table_name . " ADD noBots BOOL NOT NULL;";
        $wpdb->query($sql);
    }
    if (!$installed_ver || $installed_ver < 2.12) {
        $table_name = $wpdb->prefix . "vstr_settings";
        $sql = "ALTER TABLE " . $table_name . " ADD dontTrackAdmins BOOL NOT NULL;";
        $wpdb->query($sql);
    }
    if (!$installed_ver || $installed_ver < 2.13) {
        $table_name = $wpdb->prefix . "vstr_steps";
        $sql = "ALTER TABLE " . $table_name . " ADD value VARCHAR(32) NOT NULL;";
        $wpdb->query($sql);
    }
    if (!$installed_ver || $installed_ver < 2.14) {
        $table_name = $wpdb->prefix . "vstr_settings";
        $sql = "ALTER TABLE " . $table_name . " ADD dontTrackTactile BOOL NOT NULL;";
        $wpdb->query($sql);
    }
                
    
    update_option("vstr_version", $version);
}

/**
 * Uninstallation.
 * @access  public
 * @since   1.0.0
 * @return  void
 */
function vstr_uninstall() {
    global $wpdb;
    global $jal_db_version;
    setcookie('pll_updateV', 0);
    unset($_COOKIE['pll_updateV']);

    $table_name = $wpdb->prefix . "vstr_steps";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    $table_name = $wpdb->prefix . "vstr_visits";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    $table_name = $wpdb->prefix . "vstr_settings";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    $table_name = $wpdb->prefix . "vstr_countries";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

VisitorsTracker();
?>
