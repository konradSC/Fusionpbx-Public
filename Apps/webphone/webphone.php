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
	KonradSC <konrd@yahoo.com>
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions	
	if (permission_exists('webphone_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//verify the id is as uuid then set as a variable
	if (is_uuid($_GET['id'])) {
		$extension_uuid = $_GET['id'];
	}

//get the extension(s)
	if (permission_exists('extension_edit')) {
		//admin user
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and enabled = 'true' ";
		$sql .= "order by extension asc ";
	}
	else {
		//normal user
		$sql = "select e.* ";
		$sql .= "from v_extensions as e, ";
		$sql .= "v_extension_users as eu ";
		$sql .= "where e.extension_uuid = eu.extension_uuid ";
		$sql .= "and eu.user_uuid = :user_uuid ";
		$sql .= "and e.domain_uuid = :domain_uuid ";
		$sql .= "and e.enabled = 'true' ";
		$sql .= "order by e.extension asc ";
		$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
	}
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//include the header
	$document['title'] = $text['title-webphone'];
	require_once "resources/header.php";

	echo "<form name='frm' id='frm' method='get'>\n";
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-webphone']."</b></div>\n";
	echo "	<div class='actions'></div>\n";
	echo "	\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";
	echo $text['title-description-webphone']."\n";
	echo "<br /><br />\n";

	echo "<div style='text-align: center; white-space: nowrap; margin: 10px 0 40px 0;'>";
	echo $text['label-select_extension']."<br />\n";
	echo "<select name='id' class='formfld' onchange='this.form.submit();'>\n";
	echo "	<option value='' >".$text['label-select']."...</option>\n";
	if (is_array($extensions) && @sizeof($extensions) != 0) {
		foreach ($extensions as $row) {
			$selected = $row['extension_uuid'] == $extension_uuid ? "selected='selected'" : null;
			echo "	<option value='".escape($row['extension_uuid'])."' ".$selected.">".escape($row['extension'])." ".escape($row['number_alias'])." ".escape($row['description'])."</option>\n";
		}
	}
	echo "</select>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";
	
//begin the content
	if (strlen($extension_uuid) > 0 ) {
		echo "  <a href=\"phone\" id=\"launchPhone\">".$text['label-webphone_launch']."</a>\n"; 
	}
	echo "  <script>\n"; 
	echo "  var url      = 'phone/index.php?id=".escape($extension_uuid)."',\n";
	echo "      features = 'menubar=no,location=no,resizable=no,scrollbars=no,status=no,addressbar=no,width=320,height=480'\n"; 
	echo "      $('#launchPhone').on('click', function(event) { \n"; 
	echo "          event.preventDefault() \n"; 
	        // This is set when the phone is open and removed on close
	echo "          if (!localStorage.getItem('ctxPhone')) { \n"; 
	echo "              window.open(url, 'ctxPhone', features)\n"; 
	echo "              return false\n"; 
	echo "          } else { \n"; 
	echo "              window.alert('Phone already open.')\n"; 
	echo "          }\n"; 
	echo "      })\n";
	echo "	function updatevariable(data) { \n";
	echo "		value = data;\n";
	echo "  } \n";
	echo "  </script>\n"; 

	echo "</div>\n";	
//show the footer
	require_once "resources/footer.php";
?>