<?php
	
	//application details
		$apps[$x]['name'] = "Company Directory";
		$apps[$x]['uuid'] = "41df0ae4-2188-4c7c-ab43-2bc4663f8426";
		$apps[$x]['category'] = "System";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Wizard";
		
		
	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = "company_directory_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "1db32ec2-85de-378b-c7b2-e0caf8a4e44f";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$apps[$x]['permissions'][$y]['groups'][] = "admin";
		$apps[$x]['permissions'][$y]['groups'][] = "user";
		$y++;		
		

?>