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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
//if (permission_exists('xml_cdr_view')) {
	//access granted
//}
//else {
//	echo "access denied";
//	exit;
//}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

$export = "true";

//additional includes
//	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	require_once "company_directory_inc.php";

//get the format
	$export_format = check_str($_REQUEST['export_format']);

//export the csv
	if ($export_format == 'csv') {

		//set the headers
			header('Content-type: application/octet-binary');
			header("Content-Disposition: attachment; filename=company-directory_" . date("Y-m-d") . ".csv");

		//show the column names on the first line
			$z = 0;
			foreach($directory[1] as $key => $val) {
				if ($z == 0) {
					echo '"'.$key.'"';
				}
				else {
					echo ',"'.$key.'"';
				}
				$z++;
			}
			echo "\n";
		
		//add the values to the csv
			$x = 0;
			
			foreach($directory as $key => $row){
				echo '"'.$row['directory_first_name'].'"';
				echo ',"'.$row['directory_last_name'].'"';
				echo ',"'.$row['extension'].'"';
				echo ',"'.$row['call_group'].'"';
				foreach($row['destination'] as $key => $value) {
					echo ',"'.format_phone(substr($value['dialplan_number'],-10)).'"';
				}
			
				echo "\n";
				$x++;
			}

			exit;
	}

//export as a PDF
	if ($export_format == 'pdf') {

		//load pdf libraries
		require_once("resources/tcpdf/tcpdf.php");
		require_once("resources/fpdi/fpdi.php");

		$page_width = 8.5; //in
		$page_height = 11; //in

		// initialize pdf
		$pdf = new FPDI('L', 'in');
		$pdf -> SetAutoPageBreak(false);
		$pdf -> setPrintHeader(false);
		$pdf -> setPrintFooter(false);
		$pdf -> SetMargins(0.5, 0.5, 0.5, true);

		//set default font
		$pdf -> SetFont('helvetica', '', 7);
		//add new page
		$pdf -> AddPage('L', array($page_width, $page_height));

		$chunk = 0;

		//write the table column headers
		$data_start = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		$data_end = '</table>';

		$data_head = '<tr>';
		$data_head .= '<td width="20%"><b>'.$text['label-first_name'].'</b></td>';
		$data_head .= '<td width="20%"><b>'.$text['label-last_name'].'</b></td>';
		$data_head .= '<td width="20%"><b>'.$text['label-extension'].'</b></td>';
		$data_head .= '<td width="20%"><b>'.$text['label-call_group'].'</b></td>';
		$data_head .= '<td width="40%"><b>'.$text['label-number'].'</b></td>';
		$data_head .= '</tr>';
		$data_head .= '<tr><td colspan="12"><hr></td></tr>';

		//write the row cells
		$z = 0; // total counter
		$p = 0; // per page counter
		if (sizeof($directory) > 0) {
			foreach($directory as $key => $row) {
				$data_body[$p] .= '<tr>';
				$data_body[$p] .= '<td width="20%">'.$row['directory_first_name'].'</td>';
				$data_body[$p] .= '<td width="20%">'.$row['directory_last_name'].'</td>';
				$data_body[$p] .= '<td width="20%">'.$row['extension'].'</td>';
				$data_body[$p] .= '<td width="20%">'.$row['call_group'].'</td>';
				$x = 0;
				foreach($row['destination'] as $key => $value) {
					if ($x > 0) {
						$destination .= ", ";
					}
					$destination .= format_phone(substr($value['dialplan_number'],-10));
					$x++;
				}
				$data_body[$p] .= '<td width="20%">'.$destination.'</td>';
				unset($destination);

				$data_body[$p] .= '</tr>';

				$z++;
				$p++;

				if ($p == 60) {
					//output data
					$data_body_chunk = $data_start.$data_head;
					foreach ($data_body as $data_body_row) {
						$data_body_chunk .= $data_body_row;
					}
					$data_body_chunk .= $data_end;
					$pdf -> writeHTML($data_body_chunk, true, false, false, false, '');
					unset($data_body_chunk);
					unset($data_body);
					$p = 0;

					//add new page
					$pdf -> AddPage('L', array($page_width, $page_height));
				}

			}

		}

		//write divider
		$data_footer = '<tr><td colspan="12"></td></tr>';


		//add last page
		if ($p >= 55) {
			$pdf -> AddPage('L', array($page_width, $page_height));
		}
		//output remaining data
		$data_body_chunk = $data_start.$data_head;
		foreach ($data_body as $data_body_row) {
			$data_body_chunk .= $data_body_row;
		}
		$data_body_chunk .= $data_footer.$data_end;
		$pdf -> writeHTML($data_body_chunk, true, false, false, false, '');
		unset($data_body_chunk);

		//define file name
		$pdf_filename = "Company_Directory_".$_SESSION['domain_name']."_".date("Ymd_His").".pdf";

		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="'.$pdf_filename.'"');
		header("Content-Type: application/pdf");
		header('Accept-Ranges: bytes');
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past

		// push pdf download
		$pdf -> Output($pdf_filename, 'D');	// Display [I]nline, Save to [F]ile, [D]ownload

	}
	
?>