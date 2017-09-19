<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('company_directory_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}
	
//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	$order_by = check_str($_REQUEST["order_by"]);
	$order = check_str($_REQUEST["order"]);

//handle search term
	$search = check_str($_REQUEST["search"]);
	if (strlen($search) > 0) {
		$sql_mod = "and ( ";
		$sql_mod .= "e.extension ILIKE '%".$search."%' ";
		$sql_mod .= "or e.call_group ILIKE '%".$search."%' ";
		$sql_mod .= "or e.directory_last_name ILIKE '%".$search."%' ";
		$sql_mod .= "or e.directory_first_name ILIKE '%".$search."%' ";
		$sql_mod .= ") ";
		#$sql_mod = "and e.extension like '%".$search."%' or e.call_group like '%".$search."%' "; 
	}
	if (strlen($order_by) < 1) {
		$order_by = "extension";
		$order = "ASC";
	}

	$domain_uuid = $_SESSION['domain_uuid'];

	
//lookup the domain count

/*	$database = new database;
	$database->table = "v_extensions";
	$where[0]['name'] = 'domain_uuid';
	$where[0]['operator'] = '=';
	$where[0]['value'] = $_SESSION["domain_uuid"];
	$database->where = $where;
	$numeric_extension_count = $database->count();
	unset($database,$result,$where);	*/

	$sql = "select count(*) as num_rows from v_extensions as e\n";
	$sql .= "WHERE domain_uuid = '$domain_uuid' and directory_visible = 'true' \n";
	$sql .= $sql_mod; //add search mod from above"
	//where domain_uuid = '".$_SESSION['domain_uuid']."' ".$sql_mod." ";
	//$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$numeric_extension_count = $row['num_rows'];
		if (($db_type == "pgsql") or ($db_type == "mysql")) {
			$numeric_extension_count = $row['num_rows'];
		}
	}
	unset($prep_statement, $row);


//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($numeric_extension_count, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var_3) = paging($numeric_extension_count, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $_GET['page'];
	
	
//get all the counts from the database
	$sql = "SELECT \n";
	$sql .= "e.directory_first_name, \n";
	$sql .= "e.directory_last_name, \n";
	$sql .= "e.extension, \n";
	$sql .= "e.call_group, \n";

	//destination - put in a place holder
	$sql .= "(\n";
	$sql .= "select dialplan_uuid from v_dialplan_details \n";
	$sql .= "where domain_uuid = '$domain_uuid' \n";
	$sql .= "and dialplan_detail_tag = 'blank' \n";
	$sql .= ") as destination \n";

	$sql .= "FROM v_extensions as e \n";
	$sql .= "WHERE e.domain_uuid = '$domain_uuid' and e.directory_visible = 'true' \n";
	$sql .= $sql_mod; //add search mod from above
	$sql .= "ORDER BY ".$order_by." ".$order." \n";
	if ($export != "true") {$sql .= "limit $rows_per_page offset $offset ";}
	$database = new database;
	$database->select($sql);
	$directory = $database->result;
	unset($database,$result);

//lookup the destinations
	$x = 0;
	foreach ($directory as $key => $row) {
		$sql = "SELECT dialplan_number \n";
		$sql .= "FROM v_dialplans \n";
		$sql .= "WHERE domain_uuid = '$domain_uuid' \n";
		$sql .= "and dialplan_enabled = 'true' \n";
		$sql .= "and dialplan_order = '100' \n";
		$sql .= "and dialplan_xml like '%\"".$row['extension']." XML%' \n";
		$database = new database;
		$database->select($sql);
		$result = $database->result;
		$directory[$key]['destination'] = $result;
		unset($result,$database);
		$x++;
	}



?>