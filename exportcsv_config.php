<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2013 Super-Visions BVBA                                   |
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

chdir('../../');
include('./include/auth.php');
include_once('./lib/data_query.php');

define('MAX_DISPLAY_PAGES', 21);

$script_url = $config['url_path'].'plugins/exportcsv/exportcsv_config.php';

switch (get_request_var_request('action')) {
	case 'save':
		export_rule_form_save();

		break;
	case 'actions':
		export_rules_form_actions();

		break;
 	case 'remove':
		export_rules_remove();

		header('Location: '.$script_url);
		break;
	case 'edit':
		include_once($config['include_path'] . '/top_header.php');

		export_rule_edit();

		include_once($config['include_path'] . '/bottom_footer.php');
		break;
	default:
		include_once($config['include_path'] . '/top_header.php');

		export_rules();

		include_once($config['include_path'] . '/bottom_footer.php');
		break;
}

function export_rules(){
	global $colors, $script_url;
	
	$sort_options = array(
		'name'		=> array('Export Title', 'ASC'),
		'id'		=> array('Export Id', 'ASC'),
		'method'	=> array('Method', 'ASC'),
		'enabled'	=> array('Enabled', 'ASC'),
	);
	
	// load page and sort settings
	$page = (int) get_request_var_request('page', 1);
	$per_page = (int) get_request_var_request('per_page', read_config_option('num_rows_device'));
	if(isset($sort_options[get_request_var_request('sort_column','name')])) $sort_column = get_request_var_request('sort_column', 'name');
	if(in_array(get_request_var_request('sort_direction', 'ASC'), array('ASC','DESC'))) $sort_direction = get_request_var_request('sort_direction', 'ASC');
	
	// extra validation
	if($page < 1) $page = 1;
	
	$total_rows = db_fetch_cell('SELECT COUNT(*) FROM plugin_exportcsv_config;');

	$config_list_sql = sprintf('SELECT 
	config.id, 
	config.name, 
	config.method, 
	config.enabled 
FROM plugin_exportcsv_config config 
ORDER BY %s %s 
LIMIT %d OFFSET %d;',
		$sort_column,
		$sort_direction,
		$per_page, ($page-1)*$per_page);
	
	$config_list = db_fetch_assoc($config_list_sql);
	
	print '<form name="chk" method="post" action="'.$script_url.'">';
	html_start_box('<strong>Export Rules</strong>', '100%', $colors['header'], '3', 'center', $script_url.'?action=edit');
	
	/* generate page list */
	$url_page_select = get_page_list($page, MAX_DISPLAY_PAGES, $per_page, $total_rows, $script_url.'?');

	$nav = '<tr bgcolor="#' . $colors["header"] . '">
		<td colspan="11">
			<table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td align="left" class="textHeaderDark">
						<strong>&lt;&lt; ';
	// previous page
	if ($page > 1) $nav .= '<a class="linkOverDark" href="'.$script_url.'?page=' . ($page-1) . '">';
	$nav .= 'Previous'; 
	if ($page > 1) $nav .= '</a>';

	$nav .= '</strong>
					</td>
					<td align="center" class="textHeaderDark">
						Showing Rows ' . (($per_page*($page-1))+1) .' to '. ((($total_rows < $per_page) || ($total_rows < ($per_page*$page))) ? $total_rows : ($per_page*$page)) .' of '. $total_rows .' ['. $url_page_select .']
					</td>
					<td align="right" class="textHeaderDark">
						<strong>'; 
	// next page
	if (($page * $per_page) < $total_rows) $nav .= '<a class="linkOverDark" href="'.$script_url.'?page=' . ($page+1) . '">';
	$nav .= 'Next'; 
	if (($page * $per_page) < $total_rows) $nav .= '</a>';

	$nav .= ' &gt;&gt;</strong>
					</td>
				</tr>
			</table>
		</td>
	</tr>';

	print $nav;

	// display column names
	html_header_sort_checkbox($sort_options, $sort_column, $sort_direction, false);
	
	$i = 0;
	if (sizeof($config_list) > 0) {
		foreach ($config_list as $export_config) {
			
			form_alternate_row_color($colors['alternate'], $colors['light'], $i, 'line' . $export_config['id']); $i++;
			
			// export name
			$export_config_name = title_trim($export_config['name'], read_config_option('max_title_graph'));
			$export_config_title = '<a class="linkEditMain" href="' . htmlspecialchars($script_url.'?action=edit&id=' . $export_config['id'] ) . '" title="' . $export_config['name'] . '">' . $export_config_name . '</a>';
			form_selectable_cell($export_config_title, $export_config['id']);
			
			form_selectable_cell($export_config['id'], $export_config['id']);
			form_selectable_cell( $export_config['method'], $export_config['id']);

			form_selectable_cell($export_config['enabled'] == 'on' ? 'Enabled' : 'Disabled', $export_config['id']);
			form_checkbox_cell($export_config['name'], $export_config['id']);

			form_end_row();
		}
		print $nav;
	}else{
		print '<tr><td><em>No Report Rules</em></td></tr>';
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown(array(
		EXPORTCSV_ACTION_ENABLE => 'Enable',
		EXPORTCSV_ACTION_DISABLE => 'Disable',
		EXPORTCSV_ACTION_DELETE => 'Delete',
	));

	print "</form>\n";
	
}

function export_rule_edit() {
	global $script_url, $colors, $fields_exportcsv_rules_create, $fields_exportcsv_rules_edit;
	
	$rule_id = (int) get_request_var_request('id', 0);
	
	if (!empty($rule_id)) {
		/* display whole rule */
		$form_array = $fields_exportcsv_rules_create + $fields_exportcsv_rules_edit;
	} else {
		/* display first part of rule only and request user to proceed */
		$form_array = $fields_exportcsv_rules_create;
	}
	
	$rule_sql = sprintf('SELECT * FROM plugin_exportcsv_config WHERE id = %d;', $rule_id);
	$rule = db_fetch_row($rule_sql);
	if($rule['method'] == 'cp') $form_array['path']['method'] = 'dirpath';
	
	print '<form method="post" action="' . $script_url . '" name="exportcsv_rule_edit">';
	html_start_box('<strong>Export Rule</strong> ' . $rule['name'], '100%', $colors['header'], 3, 'center', '');
	
	draw_edit_form(array(
		'config' => array('no_form_tag' => true),
		'fields' => inject_form_variables($form_array, $rule),
	));

	html_end_box();
	form_hidden_box('id', $rule_id, '');
//	form_hidden_box('item_id', (isset($rule['item_id']) ? $rule['item_id'] : '0'), '');
	form_hidden_box('save_component_exportcsv_rule', 1, '');
	
	form_save_button($script_url);
}

function export_rule_form_save(){
	global $script_url;
	
	$rule_id = (int) get_request_var_post('id', 0);
	
	$save['id'] = $rule_id;
	$save['name'] = form_input_validate(get_request_var_post('name'), 'name', '', false);
	$save['method'] = form_input_validate(get_request_var_post('method'), 'method', '^(cp|php-(scp|sftp)|cmd-scp)$', false);
	
	if($save['method'] !== 'cp' && !empty($rule_id)){
		$save['host'] = form_input_validate(get_request_var_post('host'), 'host', '', false);
		$save['port'] = form_input_validate(get_request_var_post('port'), 'port', '[0-9]{1,5}', true);
		$save['user'] = form_input_validate(get_request_var_post('user'), 'user', '', false);
	}
	
	$save['path'] = form_input_validate(get_request_var_post('path'), 'path', '', true);
	$save['prefix'] = form_input_validate(get_request_var_post('prefix'), 'prefix', '', true);
	$save['enabled'] = get_request_var_post('enabled') ? 'on' : '';
	
	if (!is_error_message()) {
		$rule_id = sql_save($save, 'plugin_exportcsv_config');

		if ($rule_id) {
			raise_message(1);
		}else{
			raise_message(2);
		}
	}
	
	header('Location: ' . $script_url . '?action=edit&id=' . $rule_id);
}

function export_rules_form_actions(){
	global $script_url;
	
	$action = get_request_var_post('drp_action', 0);	
	$rules = array();
	foreach(array_keys($_POST) as $var) if(preg_match('/^chk_([0-9]+)$/', $var, $matches)){
		$rules[] = (int) $matches[1];
	}
	$rule_ids = implode(',', $rules);

	switch($action){
		case EXPORTCSV_ACTION_DELETE:
			$result = db_execute(sprintf('DELETE FROM plugin_exportcsv_config WHERE id IN(%s);', $rule_ids));
			break;
		case EXPORTCSV_ACTION_ENABLE:
			$result = db_execute(sprintf("UPDATE plugin_exportcsv_config SET enabled = 'on' WHERE id IN(%s);", $rule_ids));
			break;
		case EXPORTCSV_ACTION_DISABLE:
			$result = db_execute(sprintf("UPDATE plugin_exportcsv_config SET enabled = '' WHERE id IN(%s);", $rule_ids));
			break;
	}
	
	header('Location: ' . $script_url);
}

?>
