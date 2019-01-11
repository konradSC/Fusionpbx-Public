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
	Copyright (C) 2014-2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
include "root.php";

//define the provision class
	class provision_zoiperdir {
		public $db;
		public $domain_uuid;
		public $domain_name;
		public $template_dir;
		public $mac;

		public function __construct() {
			//get the database object
				global $db;
				$this->db = $db;
			//set the default template directory
				if (PHP_OS == "Linux") {
					//set the default template dir
						if (strlen($this->template_dir) == 0) {
							if (file_exists('/etc/fusionpbx/resources/templates/provision')) {
								$this->template_dir = '/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				} elseif (PHP_OS == "FreeBSD") {
					//if the FreeBSD port is installed use the following paths by default.
						if (file_exists('/usr/local/etc/fusionpbx/resources/templates/provision')) {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = '/usr/local/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
						else {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				} elseif (PHP_OS == "NetBSD") {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				} elseif (PHP_OS == "OpenBSD") {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				} else {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				}

		}

		public function __destruct_zoiperdir() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function get_domain_uuid_zoiperdir() {
			return $this->domain_uuid;
		}

		public function render_zoiperdir() {

			//debug
				$debug = $_REQUEST['debug']; // array

			//get the variables
				$domain_uuid = $this->domain_uuid;
				$device_template = $this->device_template;
				$template_dir = $this->template_dir;
				$mac = $this->mac;
				$file = $this->file;

			//set the mac address to lower case to be consistent with the database
				$mac = strtolower($mac);

			//get the device template
				if (strlen($_REQUEST['template']) > 0) {
					$device_template = $_REQUEST['template'];
					$search = array('..', '/./');
					$device_template = str_replace($search, "", $device_template);
					$device_template = str_replace('//', '/', $device_template);
				}

			//remove ../ and slashes in the file name
				$search = array('..', '/', '\\', '/./', '//');
				$file = str_replace($search, "", $file);

			//get the domain_name
				if (strlen($domain_name) == 0) {
					$sql = "SELECT domain_name FROM v_domains ";
					$sql .= "WHERE domain_uuid=:domain_uuid ";

						//	echo "<pre>".$sql."<pre>\n";
						//	exit;
					$prep_statement = $this->db->prepare(check_sql($sql));
					if ($prep_statement) {
						//use the prepared statement
							$prep_statement->bindParam(':domain_uuid', $domain_uuid);
							$prep_statement->execute();
							$row = $prep_statement->fetch();
							unset($prep_statement);
						//set the variables from values in the database
							$domain_name = $row["domain_name"];
					}
				}
//	echo "<pre>".print_r($row)."<pre>\n";
//	exit;
			//build the provision array
				$provision = Array();
				if (is_array($_SESSION['provision'])) {
					foreach($_SESSION['provision'] as $key=>$val) {
						if (strlen($val['var']) > 0) { $value = $val['var']; }
						if (strlen($val['text']) > 0) { $value = $val['text']; }
						if (strlen($val['boolean']) > 0) { $value = $val['boolean']; }
						if (strlen($val['numeric']) > 0) { $value = $val['numeric']; }
						if (strlen($value) > 0) { $provision[$key] = $value; }
						unset($value);
					}
				}


			//initialize a template object
				$view = new template();
				if (strlen($_SESSION['provision']['template_engine']['text']) > 0) {
					$view->engine = $_SESSION['provision']['template_engine']['text']; //raintpl, smarty, twig
				}
				else {
					$view->engine = "smarty";
				}
				$view->template_dir = $template_dir ."/".$device_template."/";
				$view->cache_dir = $_SESSION['server']['temp']['dir'];
				$view->init();


				//get the extensions and add them to the contacts array
/*
						//get the number of extensions
							$sql = "select count(*) as num_rows from v_extensions where domain_uuid = '".$domain_uuid."' ";
							$prep_statement = $this->db->prepare($sql);
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {							
								$numeric_extensions = $row['num_rows'];
							}
							unset($prep_statement, $result);	
*/
						//get contacts from the database
							$sql = "select extension_uuid as contact_uuid, directory_first_name, directory_last_name, ";
							$sql .= "effective_caller_id_name, effective_caller_id_number, ";
							$sql .= "call_group, ";
							$sql .= "number_alias, extension ";
							$sql .= "from v_extensions ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and enabled = 'true' ";
						//	if (intval($numeric_extensions) > 99) {
						//		$sql .= "and call_group = '".$call_group."' ";
						//	}	
							$sql .= "order by number_alias, extension asc ";
							$prep_statement = $this->db->prepare($sql);
							if ($prep_statement) {
								$prep_statement->execute();
								$extensions = $prep_statement->fetchAll(PDO::FETCH_NAMED);
								if (is_array($extensions)) {
									foreach ($extensions as $row) {
										//get the contact_uuid
											$uuid = $row['contact_uuid'];
										//get the names
											if (strlen($row['directory_last_name']) > 0) {
												$contact_name_given = $row['directory_first_name'];
												$contact_name_family = $row['directory_last_name'];
											} else {
												$name_array = explode(" ", $row['effective_caller_id_name']);
												$contact_name_given = array_shift($name_array);
												$contact_name_family = trim(implode(' ', $name_array));
											}
										//get the phone_extension
											if (is_numeric($row['extension'])) {
												$phone_extension = $row['extension'];
											}
											else {
												$phone_extension = $row['number_alias'];
											}
										//save the contact array values
											$contacts[$uuid]['category'] = 'extensions';
											$contacts[$uuid]['contact_uuid'] = $row['contact_uuid'];
											$contacts[$uuid]['contact_category'] = 'extensions';
											$contacts[$uuid]['contact_name_given'] = $contact_name_given;
											$contacts[$uuid]['contact_name_family'] = $contact_name_family;
											$contacts[$uuid]['phone_extension'] = $phone_extension;
										//unset the variables
											unset($name_array, $contact_name_given, $contact_name_family, $phone_extension);
									}
								}
							}

//	echo "<pre>".print_r($contacts)."<pre>\n";
//	exit;
				//assign the contacts array to the template
					if (is_array($contacts)) {
						$view->assign("contacts", $contacts);
						unset($contacts);
					}

				//debug information
					if ($debug == "array") {
						echo "<pre>\n";
						print_r($device_keys);
						echo "<pre>\n";
						exit;
					}

				//set the variables key and values
					$x = 1;
					$variables['domain_name'] = $domain_name;
					$variables['user_id'] = $lines[$x]['user_id'];
					$variables['auth_id'] = $lines[$x]['auth_id'];
					$variables['extension'] = $lines[$x]['extension'];

				//replace the dynamic provision variables that are defined in default, domain, and device settings
					if (is_array($provision)) {
						foreach($provision as $key=>$val) {
							$view->assign($key, $val);
						}
					}

				//set the template directory
					if (strlen($provision["template_dir"]) > 0) {
						$template_dir = $provision["template_dir"];
					}

				//if the domain name directory exists then only use templates from it
					if (is_dir($template_dir.'/'.$domain_name)) {
						$device_template = $domain_name.'/'.$device_template;
					}

				//if $file is not provided then look for a default file that exists
					if (strlen($file) == 0) {
						if (file_exists($template_dir."/".$device_template ."/{\$mac}")) {
							$file = "{\$mac}";
						}
						elseif (file_exists($template_dir."/".$device_template ."/{\$mac}.xml")) {
							$file = "{\$mac}.xml";
						}
						elseif (file_exists($template_dir."/".$device_template ."/{\$mac}.cfg")) {
							$file = "{\$mac}.cfg";
						}
						else {
							echo "file not found";
							exit;
						}
					}
					else {
						//make sure the file exists
						if (!file_exists($template_dir."/".$device_template ."/".$file)) {
							echo "file not found ".$template_dir."/".$device_template ."/".$file;
							if ($_SESSION['provision']['debug']['boolean'] == 'true'){
								echo ":$template_dir/$device_template/$file<br/>";
								echo "template_dir: $template_dir<br/>";
								echo "device_template: $device_template<br/>";
								echo "file: $file";
							}
							exit;
						}
					}

				//output template to string for header processing
					$file_contents = $view->render($file);

				//log file for testing
					if ($_SESSION['provision']['debug']['boolean'] == 'true'){
						$tmp_file = "/tmp/provisioning_log.txt";
						$fh = fopen($tmp_file, 'w') or die("can't open file");
						$tmp_string = $mac."\n";
						fwrite($fh, $tmp_string);
						fclose($fh);
					}

					$this->file = $file;
				//returned the rendered template
					return $file_contents;

		} //end render function

	} //end provision class

?>
