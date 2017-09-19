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
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";
	
//check permissions
	if (permission_exists('company_directory_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}


//perform the db query
	require_once "company_directory_inc.php";
	
//add multi-lingual support
	$language = new text;
	$text = $language->get();


//additional includes
	require_once "resources/header.php";
	$document['title'] = $text['title-company_directory'];

//set the alternating styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	
//javascript to toggle export select box
	echo "<script language='javascript' type='text/javascript'>";
	echo "	var fade_speed = 400;";
	echo "	function toggle_select(select_id) {";
	echo "		$('#'+select_id).fadeToggle(fade_speed, function() {";
	echo "			document.getElementById(select_id).selectedIndex = 0;";
	echo "		});";
	echo "	}";
	echo "</script>";
	
//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['header-company_directory']." (".$numeric_extension_count.")</b><br>\n";
	echo "	</td>\n";
	echo "		<td align='right' width='100%' style='vertical-align: top;'>";
	echo "			<form method='get' action=''>\n";
	echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "				<input type='text' class='formfld' style='width: 150px' name='search' id='search' value='".$search."'>";
	echo "				<input type='submit' class='btn' value='".$text['button-search']."' onclick=\"window.location='company_directory.php?search=".$search."';\">\n";
//	echo "			<nbsp;nbsp;></td>\n";
			echo "&nbsp;</td>\n";
	echo "		</form>\n";
	echo "			<td style='text-align: right; white-space: nowrap;'>\n";
	echo "		<form id='frm_export' method='post' action='company_directory_export.php'>\n";
	echo "				<input type='button' class='btn' value='".$text['button-export']."' onclick=\"toggle_select('export_format');\">\n";
	echo "				<input type='hidden' name='search' value='".$search."'>\n";	
//	echo "			<td style='vertical-align: top;'>";
	echo "				<select class='formfld' style='display: none; width: auto; margin-left: 3px;' name='export_format' id='export_format' onchange=\"display_message('".$text['message-preparing_download']."'); toggle_select('export_format'); document.getElementById('frm_export').submit();\">\n";
	echo "					<option value=''>...</option>\n";
	echo "					<option value='csv'>CSV</option>\n";
	echo "					<option value='pdf'>PDF</option>\n";
	echo "				</select>\n";
	echo "			</td>\n";
	echo "			<td style='text-align: right; white-space: nowrap;'>\n";	
	if ($paging_controls_mini != '') {
		echo 			"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "			</td>\n";
	echo "		</form>\n";	
	echo "  </tr>\n";
	
	
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			".$text['description-company_directory']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

//	echo "<form name='frm' method='post' action=''>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('directory_first_name', $text['label-first_name'], $order_by, $order);
	echo th_order_by('directory_last_name', $text['label-last_name'], $order_by, $order);	
	echo th_order_by('extension', $text['label-extension'], $order_by,$order);
	echo th_order_by('call_group', $text['label-call_group'], $order_by, $order);
	#echo " ".$text['label-number']." ";
	echo th_order_by('destination', $text['label-number'], $order_by, $order);
#	echo th_order_by('destination_count', $text['label-destinations'], $order_by, $order);
	echo "</tr>\n";

	if (isset($directory)) foreach ($directory as $key => $row) {
		echo "	<td valign='top' class='".$row_style[$c]."'>".$row['directory_first_name']."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$row['directory_last_name']."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$row['extension']."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$row['call_group']."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>";
			$x = 0;
			foreach($row['destination'] as $key => $value) {
				if ($x > 0) {
					echo ", ";
				}
				echo format_phone(substr($value['dialplan_number'],-10));
				$x++;
			}
			echo "&nbsp;</td>\n";

		echo "</tr>\n";
		$c = ($c==0) ? 1 : 0;
	}

	echo "</table>";
	echo "</form>";
	
	if (strlen($paging_controls) > 0) {
		echo "<br />";
		echo $paging_controls."\n";
	}

	echo "<br /><br />".((is_array($directory)) ? "<br /><br />" : null);
	
//show the footer
	require_once "resources/footer.php";
?>