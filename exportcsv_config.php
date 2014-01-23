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
		export_rules_form_save();

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

		export_rules_edit();

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
	html_start_box('', '100%', $colors['header'], '3', 'center', '');
	
	
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
			$export_config_title = '<a class="linkEditMain" href="' . htmlspecialchars($script_url.'?action=edit&id=' . $export_config['id'] . '&page=1') . '" title="' . $export_config['name'] . '">' . $export_config_name . '</a>';
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
	draw_actions_dropdown($export_config_actions);

	print "</form>\n";
	
}

?>
