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

function plugin_exportcsv_install() {
	api_plugin_register_hook('exportcsv', 'poller_output', 'exportcsv_poller_output', 'export.php'); 
	api_plugin_register_hook('exportcsv', 'poller_bottom', 'exportcsv_poller_bottom', 'export.php'); 
}

function plugin_exportcsv_uninstall () {
}

function exportcsv_version () {
	return array(
		'name'		=> 'exportcsv',
		'version'	=> '0.2',
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

?>
