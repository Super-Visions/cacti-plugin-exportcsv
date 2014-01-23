<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2012-2013 Super-Visions BVBA                              |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 */

define('EXPORTCSV_SCP_COMMAND', '/usr/local/bin/scp %s %s@%s:%s');

function plugin_exportcsv_install() {
	api_plugin_register_hook('exportcsv', 'poller_output', 'exportcsv_poller_output', 'export.php');
	api_plugin_register_hook('exportcsv', 'poller_bottom', 'exportcsv_poller_bottom', 'export.php');
	api_plugin_register_hook('exportcsv', 'config_arrays', 'exportcsv_config_arrays', 'setup.php');
	api_plugin_register_hook('exportcsv', 'draw_navigation_text', 'exportcsv_draw_navigation_text', 'setup.php');
	
	api_plugin_register_realm('exportcsv', 'exportcsv_config.php', 'Plugin ExportCSV -> Maintain Exports', true);
	
	$data = array();
	$data['columns'][] = array('name' => 'id', 		'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name',	'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'method', 	'type' => 'char(8)', 'NULL' => false, 'default' => 'cp');
	$data['columns'][] = array('name' => 'host',	'type' => 'varchar(255)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'port',	'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => true);
	$data['columns'][] = array('name' => 'user',	'type' => 'varchar(255)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'path',	'type' => 'varchar(255)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'prefix',	'type' => 'varchar(255)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'enabled', 'type' => 'char(2)', 'NULL' => true,  'default' => '');
	$data['primary'] = 'id';
	$data['keys'][] = array('name'=> 'enabled', 'columns' => 'enabled');
	$data['type'] = 'MyISAM';
	$data['comment'] = 'ExportCSV Config';
	api_plugin_db_table_create ('exportcsv', 'plugin_exportcsv_config', $data);
}

function plugin_exportcsv_uninstall () {
}

function exportcsv_version () {
	return array(
		'name'		=> 'exportcsv',
		'version'	=> '0.3',
		'longname'	=> 'Export Performance Data to CSV',
		'author'	=> 'Christophe Fonteyne',
		'homepage'	=> 'http://www.super-visions.com',
		'email'		=> 'christophe.fonteyne@super-visions.com',
	);
}

function plugin_exportcsv_version() {
	return exportcsv_version();
}

function plugin_exportcsv_check_config() {
	return true;
}

function exportcsv_config_arrays() {
	
	# menu titles
	global $menu;
	$menu["Management"]['plugins/exportcsv/exportcsv_config.php'] = "Exports";

}

function exportcsv_draw_navigation_text($nav) {
	// Displayed navigation text under the blue tabs of Cacti
	$nav["exportcsv_config.php:"]			= array("title" => "Exports", "mapping" => "index.php:", "url" => "exportcsv_config.php", "level" => "1");
	$nav["exportcsv_config.php:edit"]		= array("title" => "(Edit)", "mapping" => "index.php:,exportcsv_config.php:", "url" => "", "level" => "2");
	$nav["exportcsv_config.php:actions"]	= array("title" => "Actions", "mapping" => "index.php:,exportcsv_config.php:", "url" => "", "level" => "2");
	
    return $nav;
}

?>
