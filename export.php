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

function exportcsv_poller_output($rrd_update_array) {
	
	// create temporary file
	global $exportcsv_file;
	if(!isset($exportcsv_file)) $exportcsv_file = tempnam(__DIR__, 'exportcsv-');
	
	$data_info_sql = "SELECT 
	dl.snmp_query_id, 
	dl.snmp_index, 
	dl.host_id, 
	ht.name AS host_template, 
	h.description, 
	dt.name AS data_template, 
	dtd.rrd_step AS step 
FROM data_local dl 
JOIN `host` h 
	ON dl.host_id=h.id 
JOIN host_template ht 
	ON h.host_template_id=ht.id 
JOIN data_template dt 
	ON dl.data_template_id = dt.id 
JOIN data_template_data dtd 
	ON dl.id=dtd.local_data_id 
WHERE dl.id = %d;";
	
	$suggested_values_sql = "SELECT 
	sqgrs.text 
FROM snmp_query_graph_rrd_sv sqgrs 
JOIN data_input_data did 
	ON (did.value = sqgrs.snmp_query_graph_id) 
JOIN data_input_fields dif 
	ON (dif.id = did.data_input_field_id AND dif.type_code = 'output_type') 
JOIN data_template_data dtd 
	ON (dtd.id = did.data_template_data_id) 
WHERE 
	field_name = 'exportcsv_title' 
	AND dtd.local_data_id = %d 
ORDER BY sqgrs.sequence ASC;";
	
	// open csv file
	$fp = fopen($exportcsv_file, 'a');
	
	foreach ($rrd_update_array as $rrd_array) {
		
		// get data info
		$data_info = db_fetch_row(sprintf($data_info_sql, $rrd_array['local_data_id']));
		
		// create title
		$title = $data_info["data_template"];
		if($data_info['snmp_query_id'] != 0){
			// search for substitution based on suggested value
			foreach(db_fetch_assoc(sprintf($suggested_values_sql, $rrd_array['local_data_id'])) as $suggested_value){
				
				$title = substitute_snmp_query_data($suggested_value['text'], $data_info['host_id'], $data_info['snmp_query_id'], $data_info['snmp_index']);
				if(!substr_count($title, "|query")) break;
			}
			
			// fallback to "data_template - snmp_index"
			if( substr_count($title, "|query") || $title == $data_info["data_template"] ) $title = $data_info["data_template"].' - '.$data_info['snmp_index'];
		}
		
		// add items to csv
		foreach ($rrd_array['times'] as $curtime => $aval) {
			foreach($aval as $key => $val) {				
				fputcsv($fp, array(
					$curtime,
					$data_info['step'],
					$data_info['description'],
					$title,
					$key,
					$val,
				), ';' );
			}
		}
	}
	
	// close file
	fclose($fp);
	
	return $rrd_update_array;
}

function exportcsv_poller_bottom() {
	global $exportcsv_file;
	
	if(!isset($exportcsv_file)) return;
	
	#$poller_interval = (int) read_config_option('exportcsv_poller_interval');
	#$poller_runs = (int) read_config_option('exportcsv_poller_runs');
			
	$now = strftime("%Y%m%d%H%M",time());
	
	$export_config_sql = "SELECT * FROM plugin_exportcsv_config WHERE enabled = 'on';";
	
	$export_config = db_fetch_assoc($export_config_sql);
	/*
	$export_config[] = array(
		'type'		=> EXPORTCSV_TYPE_POLLER,
		'method'	=> 'cp',
		'host'		=> '',
		'port'		=> '',
		'user'		=> '',
		'path'		=> '/tmp/exportcsv',
		'prefix'	=> 'GWOS-IPTD-5m_',
	);
	*/
	foreach($export_config as $export){
		
		cacti_log('Starting export '.$export['name'].' using method '.$export['method'], false, 'EXPORTCSV');
		
		$success = false;
		switch($export['method']){
			case 'cp':
				// move file to new destination
				$success = copy($exportcsv_file, $export['path'].DIRECTORY_SEPARATOR.$export['prefix'].$now.'.csv');
				
				break;
			case 'php-scp':
				if (!extension_loaded('ssh2')){
					cacti_log('ERROR: required ssh2 extention is not loaded.', false, 'EXPORTCSV');
					break;
				}

				// connect
				$session = ssh2_connect($export['host'], $export['port']);
				if(!$session){
					cacti_log(sprintf('ERROR: Could not make ssh connection to host %s at port %d.', $export['host'], $export['port']), false, 'EXPORTCSV');
					break;
				}
				// authenticate
				if(!ssh2_auth_agent($session, $export['user'])){
					cacti_log(sprintf('ERROR: Could not authenticate as user %s.', $export['user']), false, 'EXPORTCSV');
					break;
				}

				// send file
				$success = ssh2_scp($session, $exportcsv_file, $export['path'].DIRECTORY_SEPARATOR.$export['prefix'].$now.'.csv');
				break;
			case 'cmd-scp':

				// prepare options
				$options = '';
				if($export['port'] != 22) $options .= sprintf('-P %d ', $export['port']);

				// prepare full scp command
				$command = sprintf('/usr/local/bin/scp %s %s@%s:%s',
					$options.$exportcsv_file,
					escapeshellarg($export['user']),
					escapeshellarg($export['host']),
					escapeshellarg($export['path'].DIRECTORY_SEPARATOR.$export['prefix'].$now.'.csv')
				);

				// execute command
				$success = system($command) !== false;
				break;
			case 'php-sftp':
				// TODO
				break;
		}

		// TODO: debug output on succes/failure
		if(!$success){
			cacti_log('ERROR: Problem while exporting '.$export['name'].' using method '.$export['method'], false, 'EXPORTCSV');
		}else{
			cacti_log('Export '.$export['name'].' using method '.$export['method'].' completed.', false, 'EXPORTCSV');
		}
	
	}
	
	if(!@unlink($exportcsv_file)){
		// TODO: debug output
	}
}

?>
