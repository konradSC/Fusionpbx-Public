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
	Copyright (C) 2008-2016 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	KonradSC <konrd@yahoo.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	//require_once "resources/functions/device_by_polydir.php";

//logging
	openlog("fusion-provisioning", LOG_PID | LOG_PERROR, LOG_LOCAL0);

//set default variables
	$dir_count = 0;
	$file_count = 0;
	$row_count = 0;
	$device_template = '';

//define PHP variables from the HTTP values
	$mac = check_str($_REQUEST['mac']);
	$file = check_str($_REQUEST['file']);
	$ext = check_str($_REQUEST['ext']);

//get the domain_name
	$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
	$domain_name = $domain_array[0];

//get the domain_uuid
	$sql = "SELECT * FROM v_domains ";
	$sql .= "WHERE domain_name = '".$domain_name."' ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $row) {
		$domain_uuid = $row["domain_uuid"];
	}
	unset($result, $prep_statement);

//get the passwords
	$provision["http_auth_username"] = $_SESSION['zoiper']['http_auth_username']['text'];
	$provision["http_auth_password"] = $_SESSION['zoiper']['http_auth_password']['text'];
	$provision["http_auth_type"] = $_SESSION['zoiper']['http_auth_type']['text'];

//http authentication - digest
	if (strlen($provision["http_auth_username"]) > 0 && strlen($provision["http_auth_type"]) == 0) { $provision["http_auth_type"] = "digest"; }
	if (strlen($provision["http_auth_username"]) > 0 && strlen($provision["http_auth_password"]) > 0 && $provision["http_auth_type"] === "digest" && $provision["http_auth_disable"] !== "true") {
		//function to parse the http auth header
			function http_digest_parse($txt) {
				//protect against missing data
				$needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
				$data = array();
				$keys = implode('|', array_keys($needed_parts));
				preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
				foreach ($matches as $m) {
					$data[$m[1]] = $m[3] ? $m[3] : $m[4];
					unset($needed_parts[$m[1]]);
				}
				return $needed_parts ? false : $data;
			}

		//function to request digest authentication
			function http_digest_request($realm) {
				header('HTTP/1.1 401 Authorization Required');
				header('WWW-Authenticate: Digest realm="'.$realm.'", qop="auth", nonce="'.uniqid().'", opaque="'.md5($realm).'"');
				header("Content-Type: text/html");
				$content = 'Authorization Cancelled';
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				die();
			}

		//set the realm
			$realm = $_SESSION['domain_name'];

		//request authentication
			if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
				http_digest_request($realm);
			}

		//check for valid digest authentication details
			if (!($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) || ($data['username'] != $provision["http_auth_username"])) {
				header('HTTP/1.1 401 Unauthorized');
				header("Content-Type: text/html");
				$content = 'Unauthorized '.$__line__;
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				exit;
			}

		//generate the valid response
			$A1 = md5($provision["http_auth_username"] . ':' . $realm . ':' . $provision["http_auth_password"]);
			$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
			$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
			if ($data['response'] != $valid_response) {
				header('HTTP/1.0 401 Unauthorized');
				header("Content-Type: text/html");
				$content = 'Unauthorized '.$__line__;
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				exit;
			}
	}

//http authentication - basic
	if (strlen($provision["http_auth_username"]) > 0 && strlen($provision["http_auth_password"]) > 0 && $provision["http_auth_type"] === "basic" && $provision["http_auth_disable"] !== "true") {
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="'.$_SESSION['domain_name'].'"');
			header('HTTP/1.0 401 Authorization Required');
			header("Content-Type: text/html");
			$content = 'Authorization Required';
			header("Content-Length: ".strval(strlen($content)));
			echo $content;
			exit;
		} else {
			if ($_SERVER['PHP_AUTH_USER'] == $provision["http_auth_username"] && $_SERVER['PHP_AUTH_PW'] == $provision["http_auth_password"]) {
				//authorized
			}
			else {
				//access denied
				syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt but failed http basic authentication for ".check_str($_REQUEST['mac']));
				header('HTTP/1.0 401 Unauthorized');
				header('WWW-Authenticate: Basic realm="'.$_SESSION['domain_name'].'"');
				unset($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
				$content = 'Unauthorized';
				header("Content-Length: ".strval(strlen($content)));
				echo $content;
				exit;
			}
		}
	}

//if password was defined in the system -> variables page then require the password.
	if (strlen($provision['password']) > 0) {
		//deny access if the password doesn't match
		if ($provision['password'] != check_str($_REQUEST['password'])) {
			syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt bad password for ".check_str($_REQUEST['mac']));
			//log the failed auth attempt to the system, to be available for fail2ban.
			openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
			syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt bad password for ".check_str($_REQUEST['mac']));
			closelog();
			echo "access denied";
			return;
		}
	}

//output template to string for header processing
	$prov = new provision_zoiperdir;
	$prov->domain_uuid = $domain_uuid;
	//$prov->mac = $mac;
	$prov->file = $file;
	//$prov->template_dir = "/etc/fusionpbx/resources/templates/provision";
	$prov->device_template = "zoiper";
	$file_contents = $prov->render_zoiperdir();

//deliver the customized config over HTTP/HTTPS
	//need to make sure content-type is correct
	if ($_REQUEST['content_type'] == 'application/octet-stream') {
		//format the mac address and
			$mac = $prov->format_mac($mac, $device_vendor);

		//replace the variable name with the value
			$file_name = str_replace("{\$mac}", $mac, $file);

		//set the headers
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . strlen($file_contents));
	}
	else {
		$cfg_ext = ".cfg";
		if ($device_vendor === "aastra" && strrpos($file, $cfg_ext, 0) === strlen($file) - strlen($cfg_ext)) {
			header("Content-Type: text/plain");
			header("Content-Length: ".strlen($file_contents));
		} else {
			header("Content-Type: text/xml; charset=utf-8");
			header("Content-Length: ".strlen($file_contents));
		}
	}
	echo $file_contents;
	closelog();

?>
